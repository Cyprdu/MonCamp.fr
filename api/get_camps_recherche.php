<?php
// Fichier: /api/get_camps_recherche.php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // --- 1. Récupération & Nettoyage des Paramètres ---
    $name = isset($_GET['name']) ? trim($_GET['name']) : '';
    $city = isset($_GET['city']) ? trim($_GET['city']) : '';
    $max_price = !empty($_GET['max_price']) ? floatval($_GET['max_price']) : 3000;
    $age = !empty($_GET['age']) ? intval($_GET['age']) : null;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';
    
    // Géolocalisation
    $lat = !empty($_GET['lat']) ? floatval($_GET['lat']) : null;
    $lng = !empty($_GET['lng']) ? floatval($_GET['lng']) : null;
    $radius = !empty($_GET['radius']) ? intval($_GET['radius']) : 50;

    // Thèmes
    $theme_ids = [];
    if (isset($_GET['themes'])) {
        if (is_array($_GET['themes'])) {
            $theme_ids = array_map('intval', $_GET['themes']);
        } elseif (is_string($_GET['themes']) && strpos($_GET['themes'], ',') !== false) {
            $theme_ids = array_map('intval', explode(',', $_GET['themes']));
        } elseif (is_numeric($_GET['themes'])) {
            $theme_ids = [intval($_GET['themes'])];
        }
    }

    // --- 2. Construction de la Requête SQL ---
    
    $sql = "SELECT c.*, ct.name as type_name";
    $params = [];

    // Formule Haversine (si coordonnées dispos)
    $geo_enabled = ($lat !== null && $lng !== null);

    if ($geo_enabled) {
        $sql .= ", (6371 * acos(cos(radians(?)) * cos(radians(c.latitude)) * cos(radians(c.longitude) - radians(?)) + sin(radians(?)) * sin(radians(c.latitude)))) AS distance";
        $params[] = $lat;
        $params[] = $lng;
        $params[] = $lat;
    } else {
        $sql .= ", NULL as distance";
    }

    // JOINTURES ET CONDITIONS DE BASE
    // Ajout des conditions : prive = 0 ET date_limite >= aujourd'hui
    $sql .= " FROM camps c 
              LEFT JOIN camp_types ct ON c.type_id = ct.id 
              WHERE c.valide = 1 
              AND c.en_attente = 0 
              AND c.refuse = 0
              AND c.prive = 0 
              AND c.date_limite_inscription >= CURDATE()";

    // --- 3. Application des Filtres ---

    // Nom / Ville
    if (!empty($name)) {
        $sql .= " AND (c.nom LIKE ? OR c.ville LIKE ?)";
        $params[] = "%$name%";
        $params[] = "%$name%";
    }
    
    // Ville spécifique
    if (!empty($city) && strpos($city, 'Ma position') === false) {
        $sql .= " AND (c.ville LIKE ? OR c.code_postal LIKE ?)";
        $params[] = "%$city%";
        $params[] = "$city%";
    }

    // Prix Max
    if ($max_price > 0) {
        $sql .= " AND c.prix <= ?";
        $params[] = $max_price;
    }

    // Âge
    if ($age !== null && $age > 0) {
        $sql .= " AND c.age_min <= ? AND c.age_max >= ?";
        $params[] = $age;
        $params[] = $age;
    }

    // Thèmes
    if (!empty($theme_ids)) {
        $placeholders = implode(',', array_fill(0, count($theme_ids), '?'));
        $sql .= " AND c.type_id IN ($placeholders)";
        foreach ($theme_ids as $tid) {
            $params[] = $tid;
        }
    }

    // Filtre Rayon (HAVING)
    if ($geo_enabled && $radius > 0) {
        $sql .= " HAVING distance < ?";
        $params[] = $radius;
    }

    // --- 4. Tri ---
    switch ($sort) {
        case 'price_asc': $sql .= " ORDER BY c.prix ASC"; break;
        case 'price_desc': $sql .= " ORDER BY c.prix DESC"; break;
        case 'date': $sql .= " ORDER BY c.date_debut ASC"; break;
        default: // 'relevance'
            if ($geo_enabled) {
                $sql .= " ORDER BY distance ASC";
            } else {
                $sql .= " ORDER BY c.boost_points DESC, c.date_debut ASC";
            }
    }

    $sql .= " LIMIT 100";

    // --- 5. Exécution ---
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $camps = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($camps);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>