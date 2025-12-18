<?php
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) { http_response_code(403); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$msgId = $input['message_id'] ?? 0;
$myId = $_SESSION['user']['id'];

if (!$msgId) exit;

// On vérifie que le message appartient bien à l'utilisateur connecté
$sql = "DELETE FROM messages WHERE id = ? AND sender_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$msgId, $myId]);

echo json_encode(['success' => $stmt->rowCount() > 0]);
?>