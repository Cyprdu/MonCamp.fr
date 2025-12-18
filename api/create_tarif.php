<?php
require_once 'config.php';

if (!isset($_SESSION['user']['id']) || !$_SESSION['user']['is_directeur']) {
    sendJson(['error' => 'Accès interdit'], 403);
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['nom']) || empty($input['organisateur_id'])) {
    sendJson(['error' => 'Données incomplètes.'], 400);
}

try {
    $stmt = $pdo->prepare("INSERT INTO tarifs (nom, prix, montant_libre, organisateur_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $input['nom'],
        $input['prix'] ?? 0,
        !empty($input['montant_libre']) ? 1 : 0,
        $input['organisateur_id']
    ]);
    
    $newId = $pdo->lastInsertId();

    // RÉPONSE PROPRE
    sendJson([
        'id' => $newId,
        'nom' => $input['nom'],
        'prix' => $input['prix'] ?? 0,
        'montant_libre' => !empty($input['montant_libre']) ? 1 : 0,
        'organisateur_id' => $input['organisateur_id']
    ], 201);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>