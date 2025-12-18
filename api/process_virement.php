<?php
// api/process_virement.php

// 1. CONFIG & SÉCURITÉ
require_once '../api/config.php';

// Le script doit être accessible uniquement aux directeurs via POST
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_directeur']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    header('Location: ../index.php');
    exit;
}

// PARAMÈTRES DE LA COMMISSION
$COMISSION_RATE = 1.00; // 1.00%
$MIN_AMOUNT = 10.00; // Montant minimum pour le virement

// 2. Récupération et validation des données POST
$organisateurId = filter_input(INPUT_POST, 'organisateur_id', FILTER_VALIDATE_INT);
$montantTotalDemande = filter_input(INPUT_POST, 'montant_total_demande', FILTER_VALIDATE_FLOAT); // Montant brut variable
$dateVirementEstime = trim($_POST['date_virement_estime'] ?? '');

// Les informations bancaires sont celles soumises
$iban = trim($_POST['iban'] ?? '');
$bic_swift = trim($_POST['bic_swift'] ?? '');
$email_organisme = trim($_POST['email_organisme'] ?? '');
$tel_organisme = trim($_POST['tel_organisme'] ?? '');

$userId = $_SESSION['user']['id'];

// Validation de base
if (!$organisateurId || $montantTotalDemande === false || empty($iban) || empty($bic_swift) || $montantTotalDemande < $MIN_AMOUNT) {
    $error_msg = "Données de virement manquantes, IBAN/BIC requis, ou montant minimum (" . number_format($MIN_AMOUNT, 2) . "€) non atteint.";
    header('Location: ../dashboard_organisme.php?organisateur_id=' . $organisateurId . '&error=' . urlencode($error_msg));
    exit;
}

try {
    // Début de la transaction
    $pdo->beginTransaction();

    // A. Récupérer les données critiques de l'organisateur et de l'utilisateur pour l'audit et la vérification
    $stmtOrga = $pdo->prepare("SELECT nom, portefeuille FROM organisateurs WHERE id = ? AND user_id = ?");
    $stmtOrga->execute([$organisateurId, $userId]);
    $organisateur = $stmtOrga->fetch(PDO::FETCH_ASSOC);

    $stmtUser = $pdo->prepare("SELECT nom, prenom, email FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$organisateur || !$user) {
        $pdo->rollBack();
        header('Location: ../public_infos.php?error=' . urlencode('Organisme ou utilisateur introuvable.'));
        exit;
    }
    
    // Vérification de la disponibilité des fonds dans le portefeuille
    $dbMontantTotal = floatval($organisateur['portefeuille']);

    if ($montantTotalDemande > $dbMontantTotal) {
        $pdo->rollBack();
        $error_msg = "Le montant demandé (" . number_format($montantTotalDemande, 2) . "€) est supérieur au solde disponible (" . number_format($dbMontantTotal, 2) . "€).";
        // Rediriger vers la page d'info pour afficher l'erreur clairement
        header('Location: ../demande_de_virement_info.php?organisateur_id=' . $organisateurId . '&error=' . urlencode($error_msg));
        exit;
    }

    // B. Recalculer la commission sur le montant demandé
    $commission = round($montantTotalDemande * ($COMISSION_RATE / 100), 2);
    $montantApresCommission = $montantTotalDemande - $commission;
    
    // C. Création du Token unique (60 caractères)
    $token = bin2hex(random_bytes(30));

    // D. Insertion dans la table virements
    $insertSql = "INSERT INTO virements (
                    token, organisateur_id, user_id, montant_total, commission_rate, montant_apres_commission, 
                    nom_organisme, iban, bic_swift, email_organisme, tel_organisme, 
                    nom_user, prenom_user, email_user, date_virement_estime
                  ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                  )";
    
    $stmtInsert = $pdo->prepare($insertSql);
    $stmtInsert->execute([
        $token, 
        $organisateurId, 
        $userId, 
        $montantTotalDemande, // Montant demandé
        $COMISSION_RATE, 
        $montantApresCommission, // Montant net calculé
        $organisateur['nom'], 
        $iban, 
        $bic_swift, 
        $email_organisme, 
        $tel_organisme,
        $user['nom'], 
        $user['prenom'], 
        $user['email'],
        $dateVirementEstime
    ]);

    // E. Mise à jour du portefeuille : soustraire le montant demandé
    $nouveauPortefeuille = $dbMontantTotal - $montantTotalDemande;
    $updatePortefeuilleSql = "UPDATE organisateurs SET portefeuille = ? WHERE id = ?";
    $stmtUpdate = $pdo->prepare($updatePortefeuilleSql);
    // Le portefeuille peut devenir négatif si vous avez des frais ou une logique plus complexe, mais ici on le met à jour
    // Si $montantTotalDemande était égal à $dbMontantTotal, $nouveauPortefeuille sera 0.00
    $stmtUpdate->execute([$nouveauPortefeuille, $organisateurId]);

    $pdo->commit();
    
    // F. Redirection vers la page de confirmation
    header('Location: ../virement.php?t=' . $token);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur de virement: " . $e->getMessage());
    $error_msg = "Erreur interne lors de la création de la demande de virement. Contactez l'administrateur.";
    header('Location: ../dashboard_organisme.php?organisateur_id=' . $organisateurId . '&error=' . urlencode($error_msg));
    exit;
}
?>