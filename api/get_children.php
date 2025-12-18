<?php
// Fichier: /api/get_children.php
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) {
    sendJson(['error' => 'Accès non autorisé.'], 403);
}

try {
    $userId = $_SESSION['user']['id'];

    // On récupère les enfants liés au parent connecté
    // On calcule l'âge directement en PHP après récupération
    $stmt = $pdo->prepare("SELECT * FROM enfants WHERE parent_id = ?");
    $stmt->execute([$userId]);
    $children = $stmt->fetchAll();

    $output = [];
    $today = new DateTime();

    foreach ($children as $child) {
        $age = null;
        if (!empty($child['date_naissance'])) {
            $dob = new DateTime($child['date_naissance']);
            $age = $dob->diff($today)->y;
        }

        // Pour la compatibilité avec le front, on recrée la structure attendue
        // On vérifie aussi si l'enfant est inscrit quelque part via la table inscriptions
        $stmtInsc = $pdo->prepare("SELECT camp_id FROM inscriptions WHERE enfant_id = ?");
        $stmtInsc->execute([$child['id']]);
        $campsInscrits = $stmtInsc->fetchAll(PDO::FETCH_COLUMN);

        $output[] = [
            'id' => $child['id'],
            'prenom' => $child['prenom'],
            'nom' => $child['nom'],
            'age' => $age,
            'sexe' => $child['sexe'],
            'registeredCamps' => $campsInscrits
        ];
    }

    sendJson($output);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>