<?php
require_once 'config.php';
if (!isset($_SESSION['user']['id'])) { sendJson(['error' => 'Non connecté'], 403); }

$input = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $pdo->prepare("INSERT INTO candidatures (user_id, camp_id, motivation, statut) VALUES (?, ?, ?, 'En attente')");
    $stmt->execute([
        $_SESSION['user']['id'],
        $input['campId'],
        $input['motivation']
    ]);
    sendJson(['success' => 'Candidature envoyée !'], 201);
} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>