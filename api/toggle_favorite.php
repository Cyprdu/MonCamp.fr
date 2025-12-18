<?php
// Fichier: /api/toggle_favorite.php
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) {
    sendJson(['error' => 'Non connecté'], 403);
}

$input = json_decode(file_get_contents('php://input'), true);
$campId = $input['campId'] ?? null;
$userId = $_SESSION['user']['id'];

if (!$campId) sendJson(['error' => 'ID manquant'], 400);

try {
    // Vérifier si existe déjà
    $stmtCheck = $pdo->prepare("SELECT 1 FROM favoris WHERE user_id = ? AND camp_id = ?");
    $stmtCheck->execute([$userId, $campId]);
    $exists = $stmtCheck->fetch();

    if ($exists) {
        // Supprimer
        $stmtDel = $pdo->prepare("DELETE FROM favoris WHERE user_id = ? AND camp_id = ?");
        $stmtDel->execute([$userId, $campId]);
        $isFavorited = false;
        
        // Mettre à jour session
        if(($key = array_search($campId, $_SESSION['user']['favorites'])) !== false) {
            unset($_SESSION['user']['favorites'][$key]);
            $_SESSION['user']['favorites'] = array_values($_SESSION['user']['favorites']);
        }
    } else {
        // Ajouter
        $stmtAdd = $pdo->prepare("INSERT INTO favoris (user_id, camp_id) VALUES (?, ?)");
        $stmtAdd->execute([$userId, $campId]);
        $isFavorited = true;
        
        // Mettre à jour session
        $_SESSION['user']['favorites'][] = $campId;
    }

    sendJson(['success' => true, 'isFavorited' => $isFavorited]);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>