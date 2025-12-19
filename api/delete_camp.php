<?php
// Fichier: api/delete_camp.php
require_once 'config.php';
session_start();

header('Content-Type: application/json');

// 1. Vérification sécurité
if (!isset($_SESSION['user']['id']) || empty($_SESSION['user']['is_directeur'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// 2. Récupération Token
$token = $_POST['token'] ?? '';

if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Token manquant']);
    exit;
}

try {
    // 3. Vérification que le camp appartient bien à l'utilisateur connecté
    // On vérifie via la table organisateurs liée au user
    $stmt = $pdo->prepare("
        SELECT c.id 
        FROM camps c 
        JOIN organisateurs o ON c.organisateur_id = o.id 
        WHERE c.token = ? AND o.user_id = ?
    ");
    $stmt->execute([$token, $_SESSION['user']['id']]);
    $camp = $stmt->fetch();

    if (!$camp) {
        echo json_encode(['success' => false, 'message' => 'Séjour introuvable ou droits insuffisants.']);
        exit;
    }

    // 4. SOFT DELETE (On marque comme supprimé au lieu d'effacer)
    // On désactive aussi l'affichage public (prive = 1) par sécurité
    $updateStmt = $pdo->prepare("UPDATE camps SET supprime = 1, prive = 1 WHERE id = ?");
    $result = $updateStmt->execute([$camp['id']]);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur technique : ' . $e->getMessage()]);
}
?>