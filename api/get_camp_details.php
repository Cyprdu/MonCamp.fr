<?php
// Fichier: api/get_camp_details.php

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once 'config.php';

$id = $_GET['id'] ?? 0;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID manquant']);
    exit;
}

try {
    // 1. GESTION VUES
    if (session_status() === PHP_SESSION_NONE) session_start();
    $sessionKey = 'viewed_camp_' . $id;
    if (!isset($_SESSION[$sessionKey])) {
        $pdo->prepare("UPDATE camps SET vues = vues + 1 WHERE id = ?")->execute([$id]);
        $_SESSION[$sessionKey] = true;
    }

    // 2. REQUÊTE CORRIGÉE (o.web au lieu de o.site_web)
    $sql = "
        SELECT 
            c.*, 
            o.nom as orga_nom, 
            o.email as orga_email,
            o.web as orga_website,  /* <-- C'est ici que c'était bloqué */
            o.user_id as organisateur_user_id,
            (SELECT COUNT(*) FROM favoris WHERE camp_id = c.id) as total_likes,
            (SELECT COUNT(*) FROM inscriptions WHERE camp_id = c.id) as nb_inscrits_reel
        FROM camps c
        LEFT JOIN organisateurs o ON c.organisateur_id = o.id
        WHERE c.id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $camp = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($camp) {
        // CALCULS
        $inscrits = intval($camp['nb_inscrits_reel']);
        $quota = intval($camp['quota_global']);
        if ($quota <= 0) $quota = 1;

        $pourcentage = round(($inscrits / $quota) * 100);
        $camp['percent_filled'] = min(100, $pourcentage);
        $camp['places_restantes'] = max(0, $quota - $inscrits);
        
        $camp['date_debut_fmt'] = date('d/m/Y', strtotime($camp['date_debut']));
        $camp['date_fin_fmt'] = date('d/m/Y', strtotime($camp['date_fin']));

        echo json_encode($camp);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Camp introuvable']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur : ' . $e->getMessage()]);
}
?>