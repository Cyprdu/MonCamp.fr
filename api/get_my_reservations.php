<?php
// Fichier: /api/get_my_reservations.php
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) {
    sendJson(['error' => 'Accès non autorisé.'], 403);
}

try {
    $userId = $_SESSION['user']['id'];

    // On sélectionne les infos de l'inscription, du camp et de l'enfant
    // en joignant les tables. On filtre sur le parent_id de l'enfant.
    $sql = "SELECT 
                i.id as inscription_id,
                c.id as camp_id,
                c.nom as camp_nom,
                c.image_url as camp_image_url,
                c.date_debut,
                e.id as enfant_id,
                e.prenom as enfant_nom
            FROM inscriptions i
            JOIN enfants e ON i.enfant_id = e.id
            JOIN camps c ON i.camp_id = c.id
            WHERE e.parent_id = :userId
            ORDER BY c.date_debut ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['userId' => $userId]);
    $reservations = $stmt->fetchAll();

    // Si pas de résultats, renvoyer tableau vide
    if (!$reservations) {
        sendJson([]);
    }

    sendJson($reservations);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>