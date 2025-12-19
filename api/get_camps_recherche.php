<?php
// Fichier: api/get_camps_recherche.php
require_once 'config.php';

// Désactivation des erreurs PHP brutes pour protéger le JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

// Fonction pour nettoyer les chaînes
function clean($str) {
    return htmlspecialchars_decode($str ?? '');
}

try {
    $today = date('Y-m-d');
    $isFallback = false; 

    // -----------------------------------------------------------
    // 1. FILTRES DE SÉCURITÉ (CLAUSE WHERE COMMUNE)
    // -----------------------------------------------------------
    // CORRECTION DATE : Si date_limite est NULL, on vérifie date_debut.
    // COALESCE(a, b) prend 'a' si existe, sinon 'b'.
    
    $baseWhere = " WHERE c.valide = 1 
                   AND c.en_attente = 0 
                   AND c.refuse = 0 
                   AND c.supprime = 0 
                   AND c.prive = 0 
                   AND COALESCE(c.date_limite_inscription, c.date_debut) >= :today ";

    $params = [':today' => $today];
    
    // Construction du Scoring (+50, +30, +20, +12, +3)
    $relevanceField = "0 as relevance_score";
    
    if (!empty($_GET['name'])) {
        $term = trim($_GET['name']);
        $relevanceField = "(
            (CASE WHEN c.nom LIKE :term THEN 50 ELSE 0 END) +
            (CASE WHEN c.type LIKE :term THEN 30 ELSE 0 END) +
            (CASE WHEN c.theme LIKE :term THEN 20 ELSE 0 END) +
            (CASE WHEN c.ville LIKE :term THEN 12 ELSE 0 END) +
            (CASE WHEN c.description LIKE :term THEN 3 ELSE 0 END)
        ) as relevance_score";
        $params[':term'] = '%' . $term . '%';
    }

    // -----------------------------------------------------------
    // 2. REQUÊTE PRINCIPALE (RECHERCHE PRÉCISE)
    // -----------------------------------------------------------
    $sql = "SELECT c.*, 
            o.nom as organisateur_nom,
            COALESCE((SELECT MIN(prix) FROM camps_tarifs ct JOIN tarifs t ON ct.tarif_id = t.id WHERE ct.camp_id = c.id), c.prix) as prix_affiche,
            (c.boost_vedette_fin > NOW()) as is_vedette,
            (c.boost_urgence_fin > NOW()) as is_urgence,
            $relevanceField
            FROM camps c 
            JOIN organisateurs o ON c.organisateur_id = o.id 
            $baseWhere";

    // Application des filtres utilisateur
    if (!empty($_GET['name'])) {
        $sql .= " AND (c.nom LIKE :term OR c.type LIKE :term OR c.theme LIKE :term OR c.ville LIKE :term OR c.description LIKE :term)";
    }
    if (!empty($_GET['city']) && $_GET['city'] !== "Ma position actuelle") {
        $sql .= " AND (c.ville LIKE :city OR c.code_postal LIKE :city)";
        $params[':city'] = '%' . $_GET['city'] . '%';
    }
    if (!empty($_GET['max_price'])) {
        $sql .= " AND c.prix <= :max";
        $params[':max'] = $_GET['max_price'];
    }
    if (!empty($_GET['age'])) {
        $sql .= " AND c.age_min <= :age AND c.age_max >= :age";
        $params[':age'] = $_GET['age'];
    }
    if (!empty($_GET['themes']) && is_array($_GET['themes'])) {
        $tParts = [];
        foreach($_GET['themes'] as $k => $v) {
            $tParts[] = "(c.theme LIKE :th_$k OR c.type LIKE :th_$k)";
            $params[":th_$k"] = '%' . $v . '%';
        }
        if($tParts) $sql .= " AND (" . implode(' OR ', $tParts) . ")";
    }

    // Tri (Vedette > Score > Date)
    $sort = $_GET['sort'] ?? 'relevance';
    $orderBy = "(c.boost_vedette_fin > NOW()) DESC"; // Vedettes toujours en premier

    if ($sort === 'price_asc') $orderBy .= ", prix_affiche ASC";
    elseif ($sort === 'price_desc') $orderBy .= ", prix_affiche DESC";
    elseif ($sort === 'date') $orderBy .= ", c.date_debut ASC";
    else {
        // Pertinence
        if (!empty($_GET['name'])) $orderBy .= ", relevance_score DESC, c.date_bump DESC";
        else $orderBy .= ", c.date_bump DESC";
    }
    
    $sql .= " ORDER BY $orderBy";

    // Exécution Principale
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $camps = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // -----------------------------------------------------------
    // 3. LOGIQUE DE FALLBACK (SI AUCUN RÉSULTAT EXACT)
    // -----------------------------------------------------------
    if (empty($camps)) {
        $isFallback = true;

        // On cherche SEULEMENT les camps Vedettes valides qui respectent les critères de sécurité
        // (Date non passée, non supprimé, etc.)
        $sqlFallback = "SELECT c.*, 
            o.nom as organisateur_nom,
            COALESCE((SELECT MIN(prix) FROM camps_tarifs ct JOIN tarifs t ON ct.tarif_id = t.id WHERE ct.camp_id = c.id), c.prix) as prix_affiche,
            1 as is_vedette, 
            (c.boost_urgence_fin > NOW()) as is_urgence,
            0 as relevance_score
            FROM camps c 
            JOIN organisateurs o ON c.organisateur_id = o.id 
            $baseWhere 
            AND c.boost_vedette_fin > NOW() 
            ORDER BY c.date_bump DESC 
            LIMIT 6";

        // On réutilise uniquement le paramètre :today
        $stmtFb = $pdo->prepare($sqlFallback);
        $stmtFb->execute([':today' => $today]);
        $camps = $stmtFb->fetchAll(PDO::FETCH_ASSOC);
    }

    // -----------------------------------------------------------
    // 4. FORMATAGE DE SORTIE
    // -----------------------------------------------------------
    foreach ($camps as &$camp) {
        $camp['image_url'] = !empty($camp['image_url']) ? $camp['image_url'] : 'assets/img/default-camp.jpg';
        $camp['prix'] = (float)$camp['prix_affiche'];
        $camp['is_vedette'] = (bool)$camp['is_vedette'];
        $camp['is_urgence'] = (bool)$camp['is_urgence'];
        $camp['nom'] = clean($camp['nom']);
        $camp['ville'] = clean($camp['ville']);
        $camp['type_name'] = clean($camp['type'] ?? 'Séjour');
    }

    echo json_encode([
        'status' => 'success',
        'is_fallback' => $isFallback,
        'count' => count($camps),
        'camps' => $camps
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>