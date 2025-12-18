<?php
// Fichier: /api/get_refused_requests.php
require_once 'config.php';

if (!isset($_SESSION['user']['is_admin']) || !$_SESSION['user']['is_admin']) { 
    sendJson(['error' => 'Accès non autorisé.'], 403); 
}

try {
    $sql = "SELECT c.id, c.nom, c.ville, o.nom as organisateur_nom 
            FROM camps c 
            LEFT JOIN organisateurs o ON c.organisateur_id = o.id
            WHERE c.refuse = 1";
            
    $stmt = $pdo->query($sql);
    sendJson($stmt->fetchAll());

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>