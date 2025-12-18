<?php
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) { sendJson(['error' => 'Non connecté'], 403); }

try {
    // On récupère les orgs + la liste des noms de camps associés (GROUP_CONCAT)
    $sql = "SELECT o.*, GROUP_CONCAT(c.nom SEPARATOR ', ') as camps_noms 
            FROM organisateurs o 
            LEFT JOIN camps c ON c.organisateur_id = o.id
            WHERE o.user_id = ?
            GROUP BY o.id";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user']['id']]);
    $orgs = $stmt->fetchAll();

    $output = array_map(function($o) {
        return [
            'id' => $o['id'],
            'nom' => $o['nom'],
            'tel' => $o['tel'],
            'mail' => $o['email'],
            'web' => $o['web'],
            'portefeuille' => $o['portefeuille'],
            'camps' => $o['camps_noms'] ? explode(', ', $o['camps_noms']) : []
        ];
    }, $orgs);

    sendJson($output);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>