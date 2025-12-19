<?php
require_once 'partials/header.php';
http_response_code(404);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page introuvable</title>
    <script>
        // Vérifie chaque seconde si l'utilisateur est toujours sur 404.php
        setInterval(() => {
            const currentPage = window.location.pathname.split('/').pop();
            if (currentPage !== '404.php') {
                window.location.href = 'https://moncamp.fr/';
            }
        }, 500); // vérifie toutes les 0,5s
    </script>
</head>
<body class="flex flex-col items-center justify-center min-h-screen bg-gray-50 text-center px-4">

    <!-- Illustration -->
    <img 
        src="uploads/error404.gif" 
        alt="Erreur 404"
        class="w-64 mb-8 rounded-md"
    >

    <!-- Titres -->
    <h1 class="text-7xl font-extrabold text-gray-900 mb-2">404</h1>
    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Page introuvable</h2>
    <p class="text-gray-600 max-w-md mb-8">
        La page que vous recherchez n’existe pas ou a été déplacée.
    </p>

    <!-- Bouton Retour à l’accueil -->
    <a 
        href="https://moncamp.fr/"
        target="_top"
        class="inline-block px-6 py-3 text-white font-semibold bg-gray-900 rounded-lg hover:bg-gray-700 transition"
    >
        Retour à l’accueil
    </a>

</body>
</html>
