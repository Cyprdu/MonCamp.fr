<?php
require_once 'config.php';
if (!isset($_SESSION['user']['id'])) { sendJson(['error' => 'Non connecté'], 403); }

$campId = $_GET['camp_id'];
$childId = $_GET['child_id'];

try {
    // Vérification appartenance enfant
    $stmtChild = $pdo->prepare("SELECT prenom FROM enfants WHERE id = ? AND parent_id = ?");
    $stmtChild->execute([$childId, $_SESSION['user']['id']]);
    $child = $stmtChild->fetch();
    
    if (!$child) sendJson(['error' => 'Enfant non trouvé'], 404);

    // Infos Camp + Organisateur
    $sqlCamp = "SELECT c.nom, c.adresse, c.date_debut, o.nom as org_nom, o.email as org_mail, o.tel as org_tel
                FROM camps c
                LEFT JOIN organisateurs o ON c.organisateur_id = o.id
                WHERE c.id = ?";
    $stmtCamp = $pdo->prepare($sqlCamp);
    $stmtCamp->execute([$campId]);
    $camp = $stmtCamp->fetch();

    sendJson([
        'enfant' => ['prenom' => $child['prenom']],
        'camp' => [
            'nom' => $camp['nom'],
            'adresse' => $camp['adresse'],
            'date_debut' => $camp['date_debut']
        ],
        'organisateur' => [
            'nom' => $camp['org_nom'],
            'mail' => $camp['org_mail'],
            'tel' => $camp['org_tel']
        ]
    ]);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>