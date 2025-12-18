<?php
require_once 'config.php';
if (!isset($_SESSION['user']['is_admin']) || !$_SESSION['user']['is_admin']) { sendJson(['error' => 'Interdit'], 403); }

try {
    $stmt = $pdo->query("SELECT id, nom, prenom, email as mail, tel FROM users WHERE demande_en_cours = 1");
    sendJson($stmt->fetchAll());
} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>