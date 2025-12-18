<?php
// Fichier: /api/get_inscription_data.php
require_once 'config.php';

// Masquer les erreurs HTML pour ne pas casser le JSON
ini_set('display_errors', 0);
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    sendJson(['error' => 'Non connecté'], 403);
}

$token = $_GET['t'] ?? '';

if (empty($token) || $token === 'undefined') {
    sendJson(['error' => 'Token invalide'], 400);
}

try {
    $userId = $_SESSION['user']['id'];

    // 1. Récupérer le camp
    $stmt = $pdo->prepare("SELECT id, nom, date_debut, date_fin, ville, age_min, age_max, remise_fratrie, quota_global FROM camps WHERE token = ?");
    $stmt->execute([$token]);
    $camp = $stmt->fetch();

    if (!$camp) { sendJson(['error' => 'Séjour introuvable'], 404); }

    // 2. Compter les inscriptions PRÉCÉDENTES de ce parent sur ce camp (pour la logique fratrie)
    $stmtCount = $pdo->prepare("
        SELECT COUNT(*) 
        FROM inscriptions i
        JOIN enfants e ON i.enfant_id = e.id
        WHERE i.camp_id = ? AND e.parent_id = ? AND i.statut_paiement != 'ANNULE'
    ");
    $stmtCount->execute([$camp['id'], $userId]);
    $nbDejaInscrits = $stmtCount->fetchColumn();

    // 3. Récupérer les tarifs
    $stmtTarifs = $pdo->prepare("
        SELECT t.id, t.nom, t.prix, t.montant_libre 
        FROM tarifs t
        JOIN camps_tarifs ct ON t.id = ct.tarif_id
        WHERE ct.camp_id = ?
    ");
    $stmtTarifs->execute([$camp['id']]);
    $tarifs = $stmtTarifs->fetchAll();

    // 4. Récupérer les enfants et vérifier s'ils sont DÉJÀ inscrits
    $stmtEnfants = $pdo->prepare("SELECT * FROM enfants WHERE parent_id = ?");
    $stmtEnfants->execute([$userId]);
    $enfantsRaw = $stmtEnfants->fetchAll();

    $now = new DateTime();
    $enfantsProcessed = [];

    foreach ($enfantsRaw as $enfant) {
        // Calcul Âge
        $age = 0;
        if ($enfant['date_naissance']) {
            $age = (new DateTime($enfant['date_naissance']))->diff($now)->y;
        }

        // Vérification si DÉJÀ inscrit
        $stmtCheck = $pdo->prepare("SELECT id FROM inscriptions WHERE camp_id = ? AND enfant_id = ? AND statut_paiement != 'ANNULE'");
        $stmtCheck->execute([$camp['id'], $enfant['id']]);
        $isAlreadyRegistered = $stmtCheck->fetch() ? true : false;

        // Éligibilité Âge
        $isAgeOk = ($age >= $camp['age_min'] && $age <= $camp['age_max']);
        
        $reason = "";
        if ($isAlreadyRegistered) $reason = "Déjà inscrit";
        elseif (!$isAgeOk) $reason = "Âge incompatible ($age ans)";

        $enfantsProcessed[] = [
            'id' => $enfant['id'],
            'prenom' => $enfant['prenom'],
            'nom' => $enfant['nom'],
            'age' => $age,
            'sexe' => $enfant['sexe'],
            // Infos complètes pour l'affichage validation
            'infos_sante' => $enfant['infos_sante'],
            'regime_alimentaire' => $enfant['regime_alimentaire'],
            'medecin_nom' => $enfant['medecin_nom'],
            'medecin_tel' => $enfant['medecin_tel'],
            'carnet_token' => $enfant['carnet_sante_token'],
            'fiche_token' => $enfant['fiche_sanitaire_token'],
            'resp1_nom' => $enfant['resp1_nom'],
            'resp1_tel' => $enfant['resp1_tel'],
            
            'is_eligible' => ($isAgeOk && !$isAlreadyRegistered),
            'already_registered' => $isAlreadyRegistered,
            'reason' => $reason
        ];
    }

    sendJson([
        'camp' => [
            'id' => $camp['id'],
            'nom' => $camp['nom'],
            'dates' => date('d/m/Y', strtotime($camp['date_debut'])) . ' au ' . date('d/m/Y', strtotime($camp['date_fin'])),
            'remise_fratrie' => $camp['remise_fratrie'], // Pour le JS
            'nb_deja_inscrits_famille' => $nbDejaInscrits // Pour le calcul
        ],
        'tarifs' => $tarifs,
        'enfants' => $enfantsProcessed
    ]);

} catch (Exception $e) {
    sendJson(['error' => 'Erreur serveur'], 500);
}
?>