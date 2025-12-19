<?php
require_once 'config.php';
session_start();

// Vérif droits
if (!isset($_SESSION['user']['is_directeur']) || !$_SESSION['user']['is_directeur']) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$token = $_POST['token'] ?? '';
$prive = isset($_POST['prive']) ? intval($_POST['prive']) : 1;

try {
    $stmt = $pdo->prepare("UPDATE camps SET prive = ? WHERE token = ?");
    $result = $stmt->execute([$prive, $token]);
    echo json_encode(['success' => $result]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>