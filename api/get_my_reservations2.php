<?php
// Fichier: /api/get_my_reservations2.php
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) { sendJson(['error' => 'Non connecté'], 403); }

try {
    // Récupération des réservations via les inscriptions
    $sql = "SELECT 
                c.id as camp_id, 
                c.nom as camp_nom, 
                c.image_url as camp_image_url, 
                c.date_debut,
                e.id as enfant_id,
                e.prenom as enfant_nom
            FROM inscriptions i
            JOIN enfants e ON i.enfant_id = e.id
            JOIN camps c ON i.camp_id = c.id
            WHERE e.parent_id = ?
            ORDER BY c.date_debut ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user']['id']]);
    
    sendJson($stmt->fetchAll());

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>