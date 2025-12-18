<?php
// Fichier: /api/get_favorites.php
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) {
    sendJson(['error' => 'Non connecté'], 403);
}

try {
    $sql = "SELECT c.* FROM camps c
            JOIN favoris f ON c.id = f.camp_id
            WHERE f.user_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user']['id']]);
    $camps = $stmt->fetchAll();

    $formatted = array_map(function($camp) {
        return [
            'id' => $camp['id'],
            'token' => $camp['token'], // AJOUT INDISPENSABLE
            'nom' => $camp['nom'],
            'ville' => $camp['ville'],
            'prix' => $camp['prix'],
            'age_min' => $camp['age_min'],
            'age_max' => $camp['age_max'],
            'date_debut' => $camp['date_debut'],
            'image_url' => $camp['image_url']
        ];
    }, $camps);

    sendJson($formatted);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>