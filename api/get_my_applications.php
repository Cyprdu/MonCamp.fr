<?php
require_once 'config.php';
if (!isset($_SESSION['user']['id'])) { sendJson(['error' => 'Non connecté'], 403); }

try {
    $sql = "SELECT cand.statut, cand.created_at as date, c.nom as camp_nom, c.ville as camp_ville, c.image_url as camp_image_url, 
                   o.nom as org_nom, o.email as org_mail
            FROM candidatures cand
            JOIN camps c ON cand.camp_id = c.id
            LEFT JOIN organisateurs o ON c.organisateur_id = o.id
            WHERE cand.user_id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user']['id']]);
    $apps = $stmt->fetchAll();

    $pending = [];
    $accepted = [];

    foreach ($apps as $app) {
        $data = [
            'camp_nom' => $app['camp_nom'],
            'camp_ville' => $app['camp_ville'],
            'camp_image_url' => $app['camp_image_url'],
            'organisateur_nom' => $app['org_nom'],
            'organisateur_mail' => $app['org_mail'],
            'statut' => $app['statut'],
            'inscrits_enfants' => 0, // À implémenter si besoin
            'inscrits_animateurs' => 0 // À implémenter si besoin
        ];

        if ($app['statut'] === 'Accepté') {
            $accepted[] = $data;
        } else {
            $pending[] = $data;
        }
    }

    sendJson(['pending' => $pending, 'accepted' => $accepted]);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>