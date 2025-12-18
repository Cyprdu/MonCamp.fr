<?php
// Fichier: /api/delete_camp.php
require_once 'config.php';

if (!isset($_SESSION['user']['id']) || !$_SESSION['user']['is_directeur']) {
    sendJson(['error' => 'Accès non autorisé.'], 403);
}

$input = json_decode(file_get_contents('php://input'), true);
$campId = $input['id'] ?? null;

if (!$campId) {
    sendJson(['error' => 'ID manquant.'], 400);
}

try {
    // Vérification propriété (Sécurité importante)
    $stmtCheck = $pdo->prepare("
        SELECT c.id FROM camps c 
        JOIN organisateurs o ON c.organisateur_id = o.id 
        WHERE c.id = ? AND o.user_id = ?
    ");
    $stmtCheck->execute([$campId, $_SESSION['user']['id']]);
    
    if (!$stmtCheck->fetch()) {
        sendJson(['error' => 'Impossible de supprimer ce camp (introuvable ou non autorisé).'], 403);
    }

    // Suppression (le ON DELETE CASCADE dans la BDD s'occupera des inscriptions liées)
    $stmtDel = $pdo->prepare("DELETE FROM camps WHERE id = ?");
    $stmtDel->execute([$campId]);

    sendJson(['success' => true, 'message' => 'Camp supprimé.']);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>