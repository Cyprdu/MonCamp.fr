<?php
require_once 'config.php';

if (!isset($_SESSION['user']['id']) || !$_SESSION['user']['is_animateur']) {
    sendJson([]); // Renvoie vide si pas animateur
}

try {
    $userId = $_SESSION['user']['id'];
    
    // 1. Récupérer les infos de l'animateur connecté (Age, Sexe, BAFA)
    $stmtUser = $pdo->prepare("SELECT date_naissance, sexe, bafa FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch();
    
    $age = 0;
    if ($user['date_naissance']) {
        $age = (new DateTime($user['date_naissance']))->diff(new DateTime())->y;
    }

    // 2. Récupérer les camps ouverts aux animateurs
    $sql = "SELECT * FROM camps 
            WHERE gestion_animateur = 1 
            AND valide = 1 
            AND date_debut > CURDATE()";
            
    // Filtre de recherche par nom
    if (!empty($_GET['name'])) {
        $sql .= " AND nom LIKE :name";
    }

    $stmt = $pdo->prepare($sql);
    if (!empty($_GET['name'])) {
        $stmt->execute(['name' => '%' . $_GET['name'] . '%']);
    } else {
        $stmt->execute();
    }
    
    $allCamps = $stmt->fetchAll();
    $eligibleCamps = [];

    foreach ($allCamps as $camp) {
        // Filtre Age
        if ($camp['anim_plus_18'] && $age < 18) continue;

        // Filtre BAFA
        if ($camp['bafa_obligatoire'] && !$user['bafa']) continue;

        // Filtre Quotas (On compte les candidatures acceptées pour ce camp)
        $stmtCount = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN u.sexe = 'Homme' THEN 1 ELSE 0 END) as hommes,
                SUM(CASE WHEN u.sexe = 'Femme' THEN 1 ELSE 0 END) as femmes
            FROM candidatures c
            JOIN users u ON c.user_id = u.id
            WHERE c.camp_id = ? AND c.statut = 'Accepté'
        ");
        $stmtCount->execute([$camp['id']]);
        $counts = $stmtCount->fetch();

        // Vérification des places restantes
        if ($camp['quota_max_anim'] > 0 && $counts['total'] >= $camp['quota_max_anim']) continue;
        if ($user['sexe'] === 'Homme' && $camp['quota_anim_garcon'] > 0 && $counts['hommes'] >= $camp['quota_anim_garcon']) continue;
        if ($user['sexe'] === 'Femme' && $camp['quota_anim_fille'] > 0 && $counts['femmes'] >= $camp['quota_anim_fille']) continue;

        // Si tout est bon, on ajoute le camp
        $eligibleCamps[] = [
            'id' => $camp['id'],
            'nom' => $camp['nom'],
            'ville' => $camp['ville'],
            'prix' => $camp['prix_anim'],
            'age_min' => $camp['age_min'],
            'age_max' => $camp['age_max'],
            'date_debut' => $camp['date_debut'],
            'image_url' => $camp['image_url']
        ];
    }

    sendJson($eligibleCamps);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>