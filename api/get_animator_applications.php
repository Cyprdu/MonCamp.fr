<?php
require_once 'config.php';
if (!isset($_SESSION['user']['is_directeur']) || !$_SESSION['user']['is_directeur']) { sendJson(['error' => 'Interdit'], 403); }

try {
    // Récupérer les candidatures pour les camps gérés par les orgs de l'utilisateur
    $sql = "SELECT cand.*, c.nom as camp_nom, u.nom as user_nom, u.prenom as user_prenom, u.email as user_mail, u.tel as user_tel
            FROM candidatures cand
            JOIN camps c ON cand.camp_id = c.id
            JOIN organisateurs o ON c.organisateur_id = o.id
            JOIN users u ON cand.user_id = u.id
            WHERE o.user_id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user']['id']]);
    $apps = $stmt->fetchAll();

    // Formatage pour le front
    $output = array_map(function($app) {
        return [
            'id' => $app['id'],
            'candidat_nom' => $app['user_prenom'] . ' ' . $app['user_nom'],
            'candidat_mail' => $app['user_mail'],
            'candidat_tel' => $app['user_tel'],
            'camp_nom' => $app['camp_nom'],
            'motivation' => $app['motivation'],
            'statut' => $app['statut'],
            'date' => $app['created_at']
        ];
    }, $apps);

    sendJson($output);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>