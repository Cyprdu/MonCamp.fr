<?php
// Fichier: api/get_child_full_details.php
require_once 'config.php';

if (!isset($_SESSION['user']['id']) || !$_SESSION['user']['is_directeur']) {
    sendJson(['error' => 'Accès interdit'], 403);
}

$inscriptionId = $_GET['id'] ?? 0;

try {
    // On récupère TOUT via l'ID de l'inscription pour vérifier le lien avec le camp du directeur
    $stmt = $pdo->prepare("
        SELECT 
            e.*, 
            c.nom as camp_nom,
            i.date_inscription,
            i.montant_paye,
            i.statut_paiement
        FROM inscriptions i
        JOIN enfants e ON i.enfant_id = e.id
        JOIN camps c ON i.camp_id = c.id
        JOIN organisateurs o ON c.organisateur_id = o.id
        WHERE i.id = ? AND o.user_id = ?
    ");
    
    $stmt->execute([$inscriptionId, $_SESSION['user']['id']]);
    $data = $stmt->fetch();

    if (!$data) {
        sendJson(['error' => 'Dossier introuvable ou accès refusé.'], 404);
    }

    sendJson($data);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>