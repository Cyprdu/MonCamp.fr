<?php
// Test final et minimaliste
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) {
    die("ERREUR: Session utilisateur non trouvée.");
}

$userId = $_SESSION['user']['id'];
$formula = "{Parent_ID_Unique} = '{$userId}'";

$params = [
    'filterByFormula' => $formula
];

// Appel direct et affichage brut
$result = callAirtable('GET', 'Enfants', $params);

header('Content-Type: application/json');
echo json_encode($result);
?>