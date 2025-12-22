<?php
// Fichier: api/apply_boost.php
require_once 'config.php';
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user']['id']) || empty($_SESSION['user']['is_directeur'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée']);
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

    // 1. On récupère le camp ET l'organisateur lié
    // Le "FOR UPDATE" bloque la ligne pour éviter qu'on dépense les points 2 fois en même temps
    $stmt = $pdo->prepare("
        SELECT c.id as camp_id, o.id as orga_id, o.solde_points 
        FROM camps c 
        JOIN organisateurs o ON c.organisateur_id = o.id 
        WHERE c.token = ? 
        FOR UPDATE
    ");
    $stmt->execute([$token]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) throw new Exception("Séjour introuvable.");

    $cost = 0;
    $sqlAction = "";

    switch ($type) {
        case 'bump':
            $cost = 10;
            $sqlAction = "UPDATE camps SET date_bump = NOW() WHERE id = ?";
            break;
        case 'vedette':
            $cost = 100;
            // Ajoute 7 jours à la date existante ou à maintenant
            $sqlAction = "UPDATE camps SET boost_vedette_fin = DATE_ADD(GREATEST(NOW(), COALESCE(boost_vedette_fin, NOW())), INTERVAL 7 DAY) WHERE id = ?";
            break;
        case 'urgence':
            $cost = 50;
            // Ajoute 3 jours
            $sqlAction = "UPDATE camps SET boost_urgence_fin = DATE_ADD(GREATEST(NOW(), COALESCE(boost_urgence_fin, NOW())), INTERVAL 3 DAY) WHERE id = ?";
            break;
        default:
            throw new Exception("Type de boost inconnu.");
    }

    // 2. Vérification du Solde GLOBAL
    if ($data['solde_points'] < $cost) {
        throw new Exception("Solde insuffisant (" . $data['solde_points'] . " pts disponibles).");
    }

    // 3. Débit du Portefeuille GLOBAL
    $stmtDebit = $pdo->prepare("UPDATE organisateurs SET solde_points = solde_points - ? WHERE id = ?");
    $stmtDebit->execute([$cost, $data['orga_id']]);

    // 4. Application du Boost sur le CAMP spécifique
    $stmtBoost = $pdo->prepare($sqlAction);
    $stmtBoost->execute([$data['camp_id']]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>