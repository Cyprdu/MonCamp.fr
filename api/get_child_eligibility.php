<?php
// Fichier: /api/get_child_eligibility.php
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) { sendJson(['error' => 'Non connecté'], 403); }
$userId = $_SESSION['user']['id'];
$campId = $_GET['camp_id'] ?? 0;

if (empty($campId)) { sendJson(['error' => 'ID camp manquant'], 400); }

try {
    // 1. Récupérer les infos du camp
    $stmtCamp = $pdo->prepare("SELECT * FROM camps WHERE id = ?");
    $stmtCamp->execute([$campId]);
    $camp = $stmtCamp->fetch();
    
    if (!$camp) throw new Exception("Camp introuvable.");

    // 2. Compter les inscrits par genre pour ce camp
    $stmtCount = $pdo->prepare("
        SELECT e.sexe, COUNT(*) as count 
        FROM inscriptions i 
        JOIN enfants e ON i.enfant_id = e.id 
        WHERE i.camp_id = ? 
        GROUP BY e.sexe
    ");
    $stmtCount->execute([$campId]);
    $counts = $stmtCount->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $filles_inscrites = $counts['Femme'] ?? 0;
    $garcons_inscrits = $counts['Homme'] ?? 0;

    // 3. Récupérer les enfants du parent
    $stmtChildren = $pdo->prepare("SELECT * FROM enfants WHERE parent_id = ?");
    $stmtChildren->execute([$userId]);
    $children = $stmtChildren->fetchAll();

    // 4. Vérifier les enfants déjà inscrits à CE camp
    $stmtInscrits = $pdo->prepare("SELECT enfant_id FROM inscriptions WHERE camp_id = ?");
    $stmtInscrits->execute([$campId]);
    $inscritsIds = $stmtInscrits->fetchAll(PDO::FETCH_COLUMN);

    $eligibilityData = [];
    $now = new DateTime();

    foreach ($children as $child) {
        $age = null;
        if ($child['date_naissance']) {
            $age = (new DateTime($child['date_naissance']))->diff($now)->y;
        }

        $isSelectable = true;
        $reason = '';

        if (in_array($child['id'], $inscritsIds)) {
            $isSelectable = false;
            $reason = 'Déjà inscrit';
        } elseif ($age < $camp['age_min'] || $age > $camp['age_max']) {
            $isSelectable = false;
            $reason = 'Âge non compatible';
        } else {
            // Vérification des quotas de genre
            if ($child['sexe'] === 'Femme' && $camp['quota_fille'] > 0 && $filles_inscrites >= $camp['quota_fille']) {
                $isSelectable = false;
                $reason = 'Plus de place pour les filles';
            } elseif ($child['sexe'] === 'Homme' && $camp['quota_garcon'] > 0 && $garcons_inscrits >= $camp['quota_garcon']) {
                $isSelectable = false;
                $reason = 'Plus de place pour les garçons';
            }
        }

        $eligibilityData[] = [
            'id' => $child['id'],
            'prenom' => $child['prenom'],
            'age' => $age,
            'sexe' => $child['sexe'],
            'isSelectable' => $isSelectable,
            'reason' => $reason
        ];
    }

    sendJson([
        'campData' => [
            'nom' => $camp['nom'],
            'prix' => $camp['prix'],
            'remise' => $camp['remise_fratrie']
        ],
        'childrenEligibility' => $eligibilityData
    ]);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>