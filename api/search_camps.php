<?php
// api/search_camps.php
require_once '../config/db.php'; 

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$filters = $input ?? [];

$sql_filters = " c.is_private = 0 AND c.date_fin_inscription >= CURDATE() ";
$params = [];
$types = "";

// Filtre par Âge Manuel
if (!empty($filters['age'])) {
    $sql_filters .= " AND ? BETWEEN c.age_min AND c.age_max ";
    $params[] = $filters['age'];
    $types .= "i";
}

// Filtre par Prix Max
if (!empty($filters['priceMax']) && $filters['priceMax'] < 2000) { // Assumons 2000 est l'infini
    $sql_filters .= " AND c.prix <= ? ";
    $params[] = $filters['priceMax'];
    $types .= "d"; // Decimal/double
}

// Filtre par Type de Camp
if (!empty($filters['typeId'])) {
    $sql_filters .= " AND c.type_id = ? ";
    $params[] = $filters['typeId'];
    $types .= "i";
}

// Recherche Intelligente par Nom (Gestion des accents)
if (!empty($filters['name'])) {
    $search_term = '%' . $filters['name'] . '%';
    // Utiliser la fonction REPLACE ou une collation non sensible aux accents dans la BDD
    // Si la collation est utf8_general_ci (non sensible), un simple LIKE suffit.
    // Sinon, on peut utiliser des expressions régulières ou une fonction de normalisation côté PHP.
    
    // Pour une solution robuste et portable (accent-insensitive search):
    $sql_filters .= " AND c.nom LIKE ? COLLATE utf8mb4_general_ci ";
    $params[] = $search_term;
    $types .= "s";
}


// Construction de la requête finale
$sql = "SELECT c.*, t.name as type_name
        FROM camps c
        LEFT JOIN camp_types t ON c.type_id = t.id
        WHERE {$sql_filters}
        ORDER BY c.boost_points DESC, c.nom ASC"; // Priorité au boost

try {
    $stmt = $conn->prepare($sql);
    
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $camps = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'camps' => $camps]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?>