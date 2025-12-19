<?php
// Fichier: api/apply_boost.php
require_once 'config.php';
header('Content-Type: application/json');
session_start();

// 1. Vérifs de base
if (!isset($_SESSION['user']['id']) || empty($_SESSION['user']['is_directeur'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$type = $input['type'] ?? '';

if (!$token || !$type) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 2. Récupérer le camp et l'organisateur (verrouillage de ligne avec FOR UPDATE pour éviter double dépense)
    $stmt = $pdo->prepare("
        SELECT c.id as camp_id, o.id as orga_id, o.solde_points 
        FROM camps c 
        JOIN organisateurs o ON c.organisateur_id = o.id 
        WHERE c.token = ? 
        FOR UPDATE
    ");
    $stmt->execute([$token]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        throw new Exception("Séjour introuvable.");
    }

    $cost = 0;
    $sqlAction = ""; // La requête SQL spécifique au boost

    // 3. Définir le coût et l'action selon la stratégie
    switch ($type) {
        case 'bump':
            $cost = 10;
            // On met à jour la date_bump à MAINTENANT pour qu'il passe devant tout le monde
            $sqlAction = "UPDATE camps SET date_bump = NOW() WHERE id = ?";
            break;

        case 'vedette':
            $cost = 100;
            // On ajoute 7 jours à la date de fin (ou on part de maintenant si c'est déjà expiré)
            $sqlAction = "UPDATE camps SET boost_vedette_fin = DATE_ADD(GREATEST(NOW(), COALESCE(boost_vedette_fin, NOW())), INTERVAL 7 DAY) WHERE id = ?";
            break;

        case 'urgence':
            $cost = 50;
            // On ajoute 3 jours
            $sqlAction = "UPDATE camps SET boost_urgence_fin = DATE_ADD(GREATEST(NOW(), COALESCE(boost_urgence_fin, NOW())), INTERVAL 3 DAY) WHERE id = ?";
            break;

        default:
            throw new Exception("Type de boost invalide.");
    }

    // 4. Vérifier le solde
    if ($data['solde_points'] < $cost) {
        throw new Exception("Solde de points insuffisant ($cost requis, " . $data['solde_points'] . " dispo).");
    }

    // 5. Débiter les points
    $stmtDebit = $pdo->prepare("UPDATE organisateurs SET solde_points = solde_points - ? WHERE id = ?");
    $stmtDebit->execute([$cost, $data['orga_id']]);

    // 6. Appliquer le boost
    $stmtBoost = $pdo->prepare($sqlAction);
    $stmtBoost->execute([$data['camp_id']]);

    // 7. (Optionnel) Historiser la transaction
    // INSERT INTO transactions_points ...

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>