<?php
// Fichier: /api/get_themes.php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Récupération des thèmes triés par nom
    $stmt = $pdo->query("SELECT id, name FROM camp_types ORDER BY name ASC");
    $themes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($themes);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur DB: ' . $e->getMessage()]);
}
?>