<?php
// Fichier: /api/get_camp_requests.php
require_once 'config.php';

// Vérification Admin
if (!isset($_SESSION['user']['is_admin']) || !$_SESSION['user']['is_admin']) { 
    sendJson(['error' => 'Interdit'], 403); 
}

try {
    // MODIFICATION ICI : On ajoute "c.token" dans le SELECT
    $sql = "SELECT c.id, c.nom, c.ville, c.code_postal, c.token, o.nom as organisateur_nom 
            FROM camps c 
            LEFT JOIN organisateurs o ON c.organisateur_id = o.id
            WHERE c.en_attente = 1 AND c.refuse = 0";
            
    $stmt = $pdo->query($sql);
    sendJson($stmt->fetchAll());

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>