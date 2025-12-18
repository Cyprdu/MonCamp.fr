<?php
// api/get_home_category.php
require_once 'config.php';

// --- FONCTIONS ---
function getApiImageUrl($dbPath) {
    if (empty($dbPath)) return 'https://images.unsplash.com/photo-1506477331477-33d5d8b3dc85?q=80&w=2600&auto=format&fit=crop';
    if (strpos($dbPath, 'uploads/') === 0) return $dbPath;
    return 'uploads/camps/' . $dbPath;
}

// Params
$cat = isset($_GET['cat']) ? $_GET['cat'] : 'default';
$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$lon = isset($_GET['lon']) ? (float)$_GET['lon'] : null;
$user_id = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;

$sql = "SELECT * FROM camps WHERE date_debut > NOW()";
$orderBy = "ORDER BY RAND()"; 
$limit = "LIMIT 10";
$params = [];

// --- LOGIQUE INTELLIGENTE ---
switch ($cat) {

    // 1. RECOMMANDATION INTELLIGENTE (ALGO)
    case 'recommended':
        if ($user_id > 0) {
            // Etape A: Trouver les types de camps que l'user a liké (table favoris ou likes)
            // On suppose une table 'favoris' (user_id, camp_id)
            // On fait une requête complexe qui donne des points
            // 2 points si même TYPE qu'un favori
            // 1 point si même VILLE qu'un favori
            // Exclure les camps déjà likés
            try {
                $sql = "
                SELECT c.*, 
                (
                    (SELECT COUNT(*) FROM favoris f JOIN camps c2 ON f.camp_id = c2.id WHERE f.user_id = :uid AND c2.type = c.type) * 2 +
                    (SELECT COUNT(*) FROM favoris f JOIN camps c2 ON f.camp_id = c2.id WHERE f.user_id = :uid AND c2.ville = c.ville) * 1
                ) as score
                FROM camps c
                WHERE c.date_debut > NOW()
                AND c.id NOT IN (SELECT camp_id FROM favoris WHERE user_id = :uid)
                HAVING score > 0
                ORDER BY score DESC, c.boost_points DESC";
                
                $params[':uid'] = $user_id;
                $orderBy = ""; // Déjà géré
            } catch (Exception $e) { $sql = "SELECT * FROM camps WHERE 1=0"; } // Fail safe
        } else {
            // Si pas connecté, fallback sur les plus populaires (ceux avec le plus de boost)
            $sql .= " ORDER BY boost_points DESC";
            $orderBy = "";
        }
        break;

    // 2. FAVORIS DE L'USER
    case 'favorites':
        if ($user_id > 0) {
            $sql = "SELECT c.* FROM camps c JOIN favoris f ON c.id = f.camp_id WHERE f.user_id = :uid AND c.date_debut > NOW()";
            $params[':uid'] = $user_id;
            $orderBy = "ORDER BY f.id DESC";
        } else {
            $sql = "SELECT * FROM camps WHERE 1=0"; // Vide
        }
        break;

    // 3. GEOLOCALISATION (Autour de chez moi)
    case 'nearby':
        if ($lat && $lon) {
            // Formule Haversine (Distance en KM)
            // Requiert que les colonnes 'latitude' et 'longitude' existent (float/double)
            $sql = "SELECT *, ( 6371 * acos( cos( radians(:lat) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(:lon) ) + sin( radians(:lat) ) * sin( radians( latitude ) ) ) ) AS distance 
                    FROM camps 
                    WHERE date_debut > NOW() 
                    HAVING distance < 150 
                    ORDER BY distance ASC";
            $params[':lat'] = $lat;
            $params[':lon'] = $lon;
            $orderBy = ""; 
        }
        break;

    // 4. ITINÉRANCE (Colonne Spécifique)
    case 'itinerant':
        // On vérifie d'abord si la colonne itinerance existe, ou on fait confiance à l'user
        // On check itinerance = 1 OU le mot clé dans le type au cas où
        $sql .= " AND (Itinérance = 1 OR type LIKE '%Itinéran%' OR type LIKE '%Voyage%')"; 
        break;

    // 5. CATÉGORIES CLASSIQUES (MOTS CLÉS ÉLARGIS)
    case 'cheap': // Petits prix
        $sql .= " AND prix >= 45 AND prix <= 400"; // Minimum 45 demandé
        $orderBy = "ORDER BY prix ASC";
        break;

    case 'last_minute':
        $sql .= " AND date_fin_inscription BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 20 DAY)";
        $orderBy = "ORDER BY date_fin_inscription ASC";
        break;

    case 'nature':
        $sql .= " AND (type REGEXP 'Nature|Forêt|Foret|Eco|Verte|Campagne|Arbre')";
        break;
    
    case 'sea':
        $sql .= " AND (type REGEXP 'Mer|Océan|Ocean|Plage|Sable|Marin')";
        break;

    case 'water':
        $sql .= " AND (type REGEXP 'Voile|Surf|Cano|Kayak|Nautique|Plongée|Paddle')";
        break;

    case 'mountain':
        $sql .= " AND (type REGEXP 'Montagne|Alpin|Rando|Escalade|Sommet')";
        break;

    case 'cooking':
        $sql .= " AND (type REGEXP 'Cuisine|Gastro|Pâtiss|Chef|Repas')";
        break;

    case 'science':
        $sql .= " AND (type REGEXP 'Science|Chimie|Astro|Espace|Biologie')";
        break;

    case 'gaming':
        $sql .= " AND (type REGEXP 'Game|Jeu|Vidéo|Geek|Code|Robot|Numérique')";
        break;

    case 'horse':
        $sql .= " AND (type REGEXP 'Cheval|Equitation|Poney|Cavalier')";
        break;

    case 'arts':
        $sql .= " AND (type REGEXP 'Art|Peinture|Dessin|Danse|Spectacle|Cirque')";
        break;

    case 'music':
        $sql .= " AND (type REGEXP 'Musi|Chant|Instrument|Orchestre|Rock')";
        break;

    case 'theater':
        $sql .= " AND (type REGEXP 'Théâtre|Theatre|Cinéma|Cinema|Film|Acteur')";
        break;

    case 'kids': // 6-10 ans
        $sql .= " AND age_min <= 10 AND age_min >= 4";
        break;

    case 'teens': // 14-17 ans
        $sql .= " AND age_max >= 14";
        break;

    case 'long_stay': // +7 jours
        $sql .= " AND DATEDIFF(date_fin, date_debut) >= 7";
        break;

    case 'religion':
        $sql .= " AND (type REGEXP 'Dieu|Prière|Priêre|Pélé|Pèle|Spi|Catho|Foi')";
        break;

    case 'abroad':
        $sql .= " AND (pays NOT LIKE 'France' AND pays IS NOT NULL AND pays != '')";
        break;

    case 'winter':
        $sql .= " AND (type REGEXP 'Ski|Neige|Hiver|Luge|Snow')";
        break;
        
    case 'survival':
        $sql .= " AND (type REGEXP 'Survie|Bushcraft|Aventure|Bivouac')";
        break;
        
    case 'solidarity':
        $sql .= " AND (type REGEXP 'Humanitaire|Solidaire|Social|Aide')";
        break;
        
    case 'language':
        $sql .= " AND (type REGEXP 'Langue|Anglais|Espagnol|Allemand|Linguistique')";
        break;
}

// Assemblage final
$sql .= " " . $orderBy . " " . $limit;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $camps = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($camps)) {
        echo '<div style="padding:20px; color:#999; font-style:italic;">Aucun séjour trouvé pour le moment.</div>';
        exit;
    }

    foreach ($camps as $camp) {
        $img = getApiImageUrl($camp['image_url'] ?? '');
        $nom = htmlspecialchars($camp['nom']);
        $ville = htmlspecialchars($camp['ville'] ?? 'France');
        $prix = number_format($camp['prix'], 0, ',', ' ');
        $id = $camp['id'];

        echo '
        <a href="camp_details.php?id='.$id.'" class="camp-card">
            <div class="camp-img-wrapper">
                <img src="'.$img.'" alt="'.$nom.'" class="camp-img" loading="lazy">
            </div>
            <div class="camp-info">
                <div class="camp-title">'.$nom.'</div>
                <div class="camp-meta">'.$ville.'</div>
                <div class="camp-price">à partir de '.$prix.'€</div>
            </div>
        </a>';
    }

} catch (PDOException $e) {
    // En cas d'erreur (ex: colonne itinerance manquante), on renvoie vide pour ne pas casser le site
    echo ''; 
}
?>