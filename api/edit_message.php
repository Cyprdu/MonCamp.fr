<?php
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) { http_response_code(403); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$msgId = $input['message_id'] ?? 0;
$newContent = trim($input['content'] ?? '');
$myId = $_SESSION['user']['id'];

if (!$msgId || empty($newContent)) exit;

$sql = "UPDATE messages SET content = ? WHERE id = ? AND sender_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$newContent, $msgId, $myId]);

echo json_encode(['success' => $stmt->rowCount() > 0]);
?>