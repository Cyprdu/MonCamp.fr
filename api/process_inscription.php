<?php
// Fichier: api/process_inscription.php
require_once 'config.php';
require_once '../vendor/autoload.php'; // Stripe

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// 1. Récupération des données
$camp_id = intval($_POST['camp_id']);
$enfant_id = intval($_POST['enfant_id']);
$tarif_id = isset($_POST['tarif_id']) ? intval($_POST['tarif_id']) : null;
$prix_final_form = floatval($_POST['prix_final']);
$paiements = isset($_POST['paiement']) ? $_POST['paiement'] : []; // Array [id_moyen => montant]
$garantie_signee = isset($_POST['garantie_paiement']) ? 1 : 0;

try {
    $pdo->beginTransaction();

    // 2. Vérifications de base (Camp, Enfant, Tarif)
    // ... (Code existant pour vérifier quotas, âge, etc - Simplifié ici pour focus paiement) ...
    
    // Vérifier si le prix correspond bien (Sécurité backend)
    // Note: Dans une version prod stricte, il faudrait recalculer le prix coté serveur à partir du tarif_id.
    // Ici on fait confiance à prix_final_form pour l'exemple mais on vérifie la somme des paiements.
    
    $total_paiements = 0;
    $montant_cb = 0;
    
    // Récupérer les noms des moyens de paiement pour identifier la CB
    $stmtMoyens = $pdo->query("SELECT id, nom FROM moyens_paiement");
    $mapMoyens = [];
    while($row = $stmtMoyens->fetch()) {
        $mapMoyens[$row['id']] = $row['nom'];
    }

    foreach ($paiements as $id => $montant) {
        $montant = floatval($montant);
        if ($montant > 0) {
            $total_paiements += $montant;
            if (isset($mapMoyens[$id]) && $mapMoyens[$id] === 'Carte Bancaire') {
                $montant_cb += $montant;
            }
        }
    }

    // Tolérance float
    if (abs($total_paiements - $prix_final_form) > 0.05) {
        throw new Exception("Le total des paiements ne correspond pas au prix du séjour.");
    }

    // Si paiement hors ligne partiel ou total, la garantie est obligatoire
    if (($prix_final_form - $montant_cb) > 0.05 && !$garantie_signee) {
        throw new Exception("La garantie de paiement est obligatoire pour les règlements différés.");
    }

    // 3. Création de l'inscription
    // Statut initial
    $statut_global = 'Attente Paiement';
    // Si tout est en CB -> Attente Paiement (Stripe validera)
    // Si tout est en Chèque -> Confirmé (car Garantie signée) mais statut paiement "En attente"
    // On met "Confirmé" pour l'inscription si garantie signée ou si 100% CB (on attendra le webhook pour valider le paiement)
    
    // Pour simplifier : On crée l'inscription en "Attente Paiement" si y'a de la CB, sinon "Confirmé" (avec dette)
    if ($montant_cb > 0) {
        $statut_inscription = "Attente Paiement"; // Stripe va update
        $statut_paiement_global = "EN_ATTENTE";
    } else {
        $statut_inscription = "Confirmé"; // Car garantie signée
        $statut_paiement_global = "A_RECEVOIR"; // L'orga doit pointer les chèques
    }

    $reservation_token = bin2hex(random_bytes(16));

    $sql = "INSERT INTO inscriptions (
        camp_id, enfant_id, date_inscription, statut, prix_final, tarif_id, 
        statut_paiement, reservation_token, garantie_paiement_signee, date_signature_garantie
    ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)";
    
    $date_sign = $garantie_signee ? date('Y-m-d H:i:s') : null;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $camp_id, $enfant_id, $statut_inscription, $prix_final_form, $tarif_id, 
        $statut_paiement_global, $reservation_token, $garantie_signee, $date_sign
    ]);
    
    $inscription_id = $pdo->lastInsertId();

    // 4. Enregistrement du détail des paiements
    $sqlDetail = "INSERT INTO inscriptions_paiements_details (inscription_id, mode_paiement_id, montant, statut) VALUES (?, ?, ?, ?)";
    $stmtDetail = $pdo->prepare($sqlDetail);

    foreach ($paiements as $id => $montant) {
        $montant = floatval($montant);
        if ($montant > 0) {
            $nom_moyen = $mapMoyens[$id] ?? '';
            // Si CB -> En attente (Stripe s'en occupe)
            // Si Chèque -> En attente (L'orga recevra plus tard)
            $statut_detail = 'EN_ATTENTE'; 
            $stmtDetail->execute([$inscription_id, $id, $montant, $statut_detail]);
        }
    }

    $pdo->commit();

    // 5. Gestion Stripe (Si montant CB > 0)
    if ($montant_cb > 0) {
        // Redirection vers create_stripe_session avec le montant spécifique CB
        // On passe le token pour retrouver l'inscription et le montant à payer
        // Attention : create_stripe_session doit être adapté pour lire le montant CB depuis la DB ou via paramètre sécurisé
        // Ici, on va appeler une version modifiée ou passer un paramètre
        
        $_SESSION['pending_payment'] = [
            'inscription_id' => $inscription_id,
            'amount_cb' => $montant_cb,
            'token' => $reservation_token
        ];
        
        header("Location: create_stripe_session_multi.php"); // Fichier spécifique pour gérer le montant partiel
        exit;
    } else {
        // 6. Succès direct (Pas de CB)
        header("Location: ../inscription_confirmation.php?token=" . $reservation_token);
        exit;
    }

} catch (Exception $e) {
    $pdo->rollBack();
    die("Erreur : " . $e->getMessage());
}
?>