<?php
// api/check_status.php
require_once 'config.php';
header('Content-Type: application/json');

$token = $_GET['t'] ?? '';

if (empty($token)) {
    echo json_encode(['verified' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT is_verified FROM users WHERE url_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['verified' => false, 'deleted' => true]);
    } else {
        echo json_encode(['verified' => ($user['is_verified'] == 1)]);
    }

} catch (Exception $e) {
    echo json_encode(['verified' => false]);
}
?>