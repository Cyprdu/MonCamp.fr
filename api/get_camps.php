<?php
// api/get_camps.php

// Inclusion de la configuration de la base de données
require_once 'config.php'; 

// Définition du header pour renvoyer du JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // --- 1. RÉCUPÉRATION DES PARAMÈTRES (GET) ---

    // Pagination (Défauts : Page 1, 40 résultats)
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 40;

    // Sécurité : Limiter le max de résultats par appel
    if ($limit > 100) $limit = 100; 
    if ($page < 1) $page = 1;

    // Calcul de l'offset (point de départ)
    $offset = ($page - 1) * $limit;

    // Filtres de recherche
    $name = isset($_GET['name']) ? trim($_GET['name']) : '';
    $department = isset($_GET['department']) ? trim($_GET['department']) : '';
    $age = isset($_GET['age']) && is_numeric($_GET['age']) ? (int)$_GET['age'] : null;


    // --- 2. CONSTRUCTION DE LA REQUÊTE SQL ---

    // CONDITIONS DE BASE MISES À JOUR :
    // - valide = 1 : Le camp doit être validé par l'admin
    // - refuse = 0 : Le camp ne doit pas être refusé
    // - prive = 0 : Le camp ne doit pas être privé
    // - date_limite_inscription : Doit être STRICTEMENT supérieure ou égale à aujourd'hui
    //   (On exclut IS NULL et '0000-00-00')
    
    $sql = "SELECT * FROM camps 
            WHERE valide = 1 
            AND refuse = 0 
            AND (prive = 0 OR prive IS NULL)
            AND date_limite_inscription >= CURDATE()
            AND date_limite_inscription != '0000-00-00'
            AND date_limite_inscription IS NOT NULL";
            
    $params = [];

    // Ajout du filtre : Nom
    if (!empty($name)) {
        $sql .= " AND nom LIKE :name";
        $params[':name'] = '%' . $name . '%';
    }

    // Ajout du filtre : Département (Code postal commençant par...)
    if (!empty($department)) {
        $sql .= " AND code_postal LIKE :department";
        $params[':department'] = $department . '%';
    }

    // Ajout du filtre : Âge
    if ($age !== null) {
        $sql .= " AND age_min <= :age AND age_max >= :age";
        $params[':age'] = $age;
    }

    // Tri par date de début
    $sql .= " ORDER BY date_debut ASC";

    // --- 3. AJOUT DE LA PAGINATION ---
    $sql .= " LIMIT :limit OFFSET :offset";


    // --- 4. PRÉPARATION ET EXÉCUTION ---

    $stmt = $pdo->prepare($sql);

    // Bind des paramètres de filtrage
    foreach ($params as $key => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $type);
    }

    // Bind des paramètres de pagination
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    
    // Récupération des résultats
    $camps = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- 5. ENVOI DE LA RÉPONSE ---
    echo json_encode($camps);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des camps: ' . $e->getMessage()]);
}
?>