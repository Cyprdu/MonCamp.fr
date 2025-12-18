<?php
require_once 'config.php';
if (!isset($_SESSION['user']['is_admin']) || !$_SESSION['user']['is_admin']) { sendJson(['count' => 0]); }

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE demande_en_cours = 1");
    sendJson(['count' => $stmt->fetchColumn()]);
} catch (Exception $e) {
    sendJson(['count' => 0]);
}
?>