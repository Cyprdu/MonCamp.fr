<?php
// Fichier : api/mark_messages_read.php
require_once 'config.php';

// Vérif sécurité
if (!isset($_SESSION['user']['id'])) {
    http_response_code(403);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$convId = $input['conv_id'] ?? 0;
$myId = $_SESSION['user']['id'];

if (!$convId) {
    http_response_code(400);
    exit;
}

try {
    // On met à jour SEULEMENT les messages que je n'ai PAS envoyés (ceux que je reçois)
    // Et qui sont dans cette conversation spécifique
    $sql = "UPDATE messages 
            SET is_read = 1 
            WHERE conversation_id = ? 
            AND sender_id != ? 
            AND is_read = 0";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$convId, $myId]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>