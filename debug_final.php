<?php
// Fichier: debug_final.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Configuration DB manuelle pour voir les erreurs de connexion brutes si config.php échoue
$host = 'localhost';
$db   = 'colomap';
$user = 'root';
$pass = '';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Debug SQL Final</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
<div class="bg-white p-8 rounded shadow max-w-4xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold">Diagnostique SQL</h1>

    <div class="border-b pb-4">
        <h2 class="text-xl font-semibold text-blue-600">1. Session PHP</h2>
        <pre class="bg-gray-100 p-2 text-sm"><?php print_r($_SESSION); ?></pre>
        <?php if(!isset($_SESSION['user']['id'])): ?>
            <p class="text-red-500 font-bold">ATTENTION: Utilisateur non connecté.</p>
        <?php endif; ?>
    </div>

    <div class="border-b pb-4">
        <h2 class="text-xl font-semibold text-blue-600">2. Connexion Base de Données</h2>
        <?php
        try {
            $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
            echo "<p class='text-green-600'>Connexion réussie à la base <strong>$db</strong>.</p>";
        } catch (\PDOException $e) {
            echo "<p class='text-red-600'>Erreur de connexion : " . htmlspecialchars($e->getMessage()) . "</p>";
            die(); // Arrêt si pas de DB
        }
        ?>
    </div>

    <div class="border-b pb-4">
        <h2 class="text-xl font-semibold text-blue-600">3. Enfants du Parent connecté</h2>
        <?php
        if(isset($_SESSION['user']['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM enfants WHERE parent_id = ?");
            $stmt->execute([$_SESSION['user']['id']]);
            $enfants = $stmt->fetchAll();
            
            if(count($enfants) > 0) {
                echo "<ul class='list-disc pl-5'>";
                foreach($enfants as $e) {
                    echo "<li>ID: {$e['id']} - {$e['prenom']} ({$e['sexe']})</li>";
                }
                echo "</ul>";
            } else {
                echo "<p class='text-orange-500'>Aucun enfant trouvé pour ce parent.</p>";
            }
        } else {
            echo "<p class='text-gray-400'>Test ignoré (non connecté).</p>";
        }
        ?>
    </div>
</div>
</body>
</html>