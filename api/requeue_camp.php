<?php
require_once 'config.php';
if (!isset($_SESSION['user']['is_admin']) || !$_SESSION['user']['is_admin']) { sendJson(['error' => 'Interdit'], 403); }

$input = json_decode(file_get_contents('php://input'), true);
$campId = $input['campId'] ?? null;

try {
    $pdo->prepare("UPDATE camps SET refuse = 0, en_attente = 1 WHERE id = ?")->execute([$campId]);
    sendJson(['success' => true]);
} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>