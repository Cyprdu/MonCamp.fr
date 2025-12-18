<?php
require_once 'config.php';
if (!isset($_SESSION['user']['id'])) { sendJson(['error' => 'Non connecté'], 403); }

try {
    $pdo->prepare("UPDATE users SET demande_en_cours = 1 WHERE id = ?")->execute([$_SESSION['user']['id']]);
    $_SESSION['user']['demande_en_cours'] = true; // Mise à jour session
    sendJson(['success' => 'Demande envoyée !']);
} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>