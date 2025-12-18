<?php
// Fichier: /api/get_camp_stats.php
require_once 'config.php';

// 1. Vérification session
if (!isset($_SESSION['user']['id']) || !$_SESSION['user']['is_directeur']) {
    sendJson(['error' => 'Accès interdit'], 403);
}

$token = $_GET['t'] ?? '';

if (empty($token)) {
    sendJson(['error' => 'Token manquant'], 400);
}

try {
    $userId = $_SESSION['user']['id'];

    // 2. SÉCURITÉ : On récupère le camp ET on vérifie le propriétaire en une seule requête
    // On joint 'organisateurs' pour vérifier que 'user_id' correspond à celui de la session
    $stmtCheck = $pdo->prepare("
        SELECT c.*, o.user_id as owner_id
        FROM camps c 
        JOIN organisateurs o ON c.organisateur_id = o.id 
        WHERE c.token = ?
    ");
    $stmtCheck->execute([$token]);
    $camp = $stmtCheck->fetch();

    // 3. Vérifications strictes
    if (!$camp) {
        sendJson(['error' => 'Camp introuvable'], 404);
    }

    if ($camp['owner_id'] != $userId) {
        // Tentative d'accès à un camp qui n'est pas le sien -> 403 Forbidden
        sendJson(['error' => 'Vous n\'êtes pas le propriétaire de ce camp.'], 403);
    }

    // --- À partir d'ici, on a le feu vert, on charge les données sensibles ---

    // 4. Récupérer la liste des inscrits
    // Note: On utilise 'infos_sante' et 'regime_alimentaire' qui doivent exister en BDD maintenant
    $stmtInscrits = $pdo->prepare("
        SELECT 
            i.id as inscription_id,
            i.date_inscription,
            i.montant_paye,
            i.statut_paiement,
            e.prenom, e.nom, e.date_naissance, e.sexe, e.infos_sante, e.regime_alimentaire,
            u.email as parent_email, u.tel as parent_tel, u.nom as parent_nom_famille, u.prenom as parent_prenom,
            t.nom as tarif_nom
        FROM inscriptions i
        JOIN enfants e ON i.enfant_id = e.id
        JOIN users u ON e.parent_id = u.id
        LEFT JOIN tarifs t ON i.tarif_id = t.id
        WHERE i.camp_id = ?
        ORDER BY i.date_inscription DESC
    ");
    $stmtInscrits->execute([$camp['id']]);
    $inscrits = $stmtInscrits->fetchAll();

    // 5. Calculs KPI (inchangés)
    $totalRecettes = 0;
    $totalFilles = 0;
    $totalGarcons = 0;
    $ages = [];
    $inscriptionsParJour = [];

    foreach ($inscrits as $inscrit) {
        $totalRecettes += $inscrit['montant_paye'];
        
        if ($inscrit['sexe'] === 'Femme') $totalFilles++;
        else $totalGarcons++;

        if ($inscrit['date_naissance']) {
            $ages[] = (new DateTime($inscrit['date_naissance']))->diff(new DateTime())->y;
        }

        $dateKey = date('Y-m-d', strtotime($inscrit['date_inscription']));
        if (!isset($inscriptionsParJour[$dateKey])) $inscriptionsParJour[$dateKey] = 0;
        $inscriptionsParJour[$dateKey]++;
    }

    $ageMoyen = count($ages) > 0 ? round(array_sum($ages) / count($ages), 1) : 0;
    $nbInscrits = count($inscrits);
    $tauxRemplissage = $camp['quota_global'] > 0 ? round(($nbInscrits / $camp['quota_global']) * 100, 1) : 0;

    ksort($inscriptionsParJour);

    sendJson([
        'camp' => [
            'id' => $camp['id'], // ID interne utile pour l'édit si besoin
            'nom' => $camp['nom'],
            'dates' => date('d/m/Y', strtotime($camp['date_debut'])) . ' au ' . date('d/m/Y', strtotime($camp['date_fin'])),
            'quota' => $camp['quota_global']
        ],
        'kpi' => [
            'chiffre_affaires' => $totalRecettes,
            'nombre_inscrits' => $nbInscrits,
            'taux_remplissage' => $tauxRemplissage,
            'age_moyen' => $ageMoyen,
            'parite' => ['filles' => $totalFilles, 'garcons' => $totalGarcons]
        ],
        'liste_inscrits' => $inscrits,
        'chart' => [
            'labels' => array_keys($inscriptionsParJour),
            'data' => array_values($inscriptionsParJour)
        ]
    ]);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>