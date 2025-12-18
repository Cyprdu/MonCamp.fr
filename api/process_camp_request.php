<?php
require_once 'config.php';
if (!isset($_SESSION['user']['is_admin']) || !$_SESSION['user']['is_admin']) { sendJson(['error' => 'Interdit'], 403); }

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$campId = $input['campId'] ?? 0;

try {
    if ($action === 'approve') {
        $sql = "UPDATE camps SET valide = 1, en_attente = 0, refuse = 0 WHERE id = ?";
    } elseif ($action === 'deny') {
        $sql = "UPDATE camps SET valide = 0, en_attente = 0, refuse = 1 WHERE id = ?";
    } else {
        throw new Exception("Action inconnue");
    }
    
    $pdo->prepare($sql)->execute([$campId]);
    sendJson(['success' => true]);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>