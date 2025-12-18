<?php
// Fichier: /api/get_my_camps.php
require_once 'config.php';

if (!isset($_SESSION['user']['id']) || !$_SESSION['user']['is_directeur']) {
    sendJson(['error' => 'Accès interdit'], 403);
}

try {
    // MODIFICATION IMPORTANTE : Ajout de "c.token" dans le SELECT
    $sql = "SELECT c.id, c.nom, c.date_debut, c.date_fin, c.image_url, c.valide, c.refuse, c.en_attente, c.token 
            FROM camps c
            JOIN organisateurs o ON c.organisateur_id = o.id
            WHERE o.user_id = ?
            ORDER BY c.id DESC"; // Tri par le plus récent

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user']['id']]);
    $camps = $stmt->fetchAll();

    sendJson($camps);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>