<?php
// Exemple download_health.php
require 'api/config.php';
// ... Vérification que $_SESSION['user']['id'] est bien le parent de l'enfant ...
$file = '../uploads/sante/' . $enfant['carnet_sante_token'];
if (file_exists($file)) {
    header('Content-Type: application/pdf'); // ou image/jpeg selon le fichier
    readfile($file);
}
?>