<?php
// Fichier: /api/mark_conversation_as_read.php
require_once 'config.php';

session_start();
header('Content-Type: application/json');

// Vérification de sécurité
if (!isset($_SESSION['user']['id'])) { 
    http_response_code(403); 
    exit; 
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
    http_response_code(405); 
    exit; 
}

$input = json_decode(file_get_contents('php://input'), true);
$conversationId = $input['conversationId'] ?? null;
$userId = $_SESSION['user']['id'];

if (empty($conversationId)) { 
    http_response_code(400); 
    exit; 
}

try {
    // On met à jour la table de liaison pour dire que cet utilisateur a lu cette conversation
    $stmt = $pdo->prepare("UPDATE conversation_participants SET has_read = 1 WHERE conversation_id = ? AND user_id = ?");
    $stmt->execute([$conversationId, $userId]);
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>