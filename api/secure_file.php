<?php
// Fichier: api/secure_file.php
require_once 'config.php';

// 1. Sécurité : Directeur uniquement
if (!isset($_SESSION['user']['id']) || !$_SESSION['user']['is_directeur']) {
    die("Accès interdit.");
}

$tokenFile = $_GET['file'] ?? '';

// Sécurité anti-traversée de dossier (interdire les ".." ou "/" dans le nom)
if (empty($tokenFile) || !preg_match('/^[a-zA-Z0-9.]+$/', $tokenFile)) {
    die("Nom de fichier invalide.");
}

// 2. Chemin ABSOLU sécurisé
// __DIR__ donne le dossier actuel (api/), on remonte d'un cran pour aller dans uploads/sante/
$baseDir = __DIR__ . '/../uploads/sante/';
$filePath = $baseDir . $tokenFile;

// 3. Vérification
if (file_exists($filePath)) {
    // Détection du type MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    // Envoi du fichier
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="carnet_sante.' . pathinfo($filePath, PATHINFO_EXTENSION) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: private, max-age=0, must-revalidate');
    
    // Nettoyage du buffer de sortie pour éviter de corrompre le fichier
    if (ob_get_level()) ob_end_clean();
    
    readfile($filePath);
    exit;
} else {
    // Message d'erreur précis pour le débogage (à retirer en prod si souhaité)
    die("Erreur : Le fichier est introuvable sur le disque.\nChemin cherché : " . realpath($baseDir) . DIRECTORY_SEPARATOR . $tokenFile);
}
?>