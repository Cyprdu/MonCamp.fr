<?php
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) { sendJson([], 403); }
$input = json_decode(file_get_contents('php://input'), true);

$convId = $input['conv_id'] ?? 0;
$content = trim($input['content'] ?? '');

if (!$convId || empty($content)) { sendJson(['error' => 'Vide'], 400); }

try {
    // 1. Insérer le message
    $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$convId, $_SESSION['user']['id'], $content]);

    // 2. Mettre à jour la date de la conversation (pour qu'elle remonte en haut de la liste)
    $pdo->prepare("UPDATE conversations SET last_message_at = NOW() WHERE id = ?")->execute([$convId]);

    sendJson(['success' => true]);
} catch (Exception $e) { sendJson(['error' => $e->getMessage()], 500); }
?>