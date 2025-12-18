<?php
// Fichier: /partials/header.php

// CORRECTION : On ne démarre la session que si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// On définit les statuts de l'utilisateur pour simplifier le code HTML
$is_logged_in = isset($_SESSION['user']);
$is_director = $is_logged_in && ($_SESSION['user']['is_directeur'] ?? false);
$is_admin = $is_logged_in && ($_SESSION['user']['is_admin'] ?? false);
$is_animateur = $is_logged_in && ($_SESSION['user']['is_animateur'] ?? false);

// On prépare l'URL de l'avatar par défaut avec la première lettre du prénom.
$initial = 'U'; // Initiale par défaut
if ($is_logged_in && !empty($_SESSION['user']['prenom'])) {
    $initial = strtoupper(substr($_SESSION['user']['prenom'], 0, 1));
}
$placeholder_url_desktop = "https://placehold.co/36x36/e2e8f0/2563eb?text=" . urlencode($initial);
$placeholder_url_mobile = "https://placehold.co/48x48/e2e8f0/2563eb?text=" . urlencode($initial);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ColoMap - Trouvez le camp parfait</title>
    <link rel="icon" type="image/png" href="favico.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> 
        /* --- Nouvelle Direction Artistique & Thème Clair/Sombre --- */

        /* Variables pour le Thème Clair (Défaut) */
        :root, [data-theme="light"] {
            /* Couleurs de la DA: Propre, Noir, Blanc, Bleu d'accent */
            --color-primary: #007bff;          /* Bleu d'accentuation (boutons, liens) */
            --color-primary-hover: #0056b3;    /* Bleu plus foncé au survol */
            --color-background: #f8f9fa;       /* Arrière-plan global (très clair) */
            --color-card-background: #ffffff;  /* Fonds de bloc (blanc) */
            --color-text-primary: #212529;     /* Texte principal (noir/très foncé) */
            --color-text-secondary: #6c757d;   /* Texte secondaire/gris */
            --color-border: #e2e8f0;           /* Bordures légères */
            --color-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.075), 0 1px 2px 0 rgba(0, 0, 0, 0.02); /* Ombre douce */
        }

        /* Variables pour le Thème Sombre */
        [data-theme="dark"] {
            --color-primary: #5bc0de;          /* Bleu plus clair pour contraste */
            --color-primary-hover: #318fb1;    
            --color-background: #121212;       /* Arrière-plan très sombre (minimaliste) */
            --color-card-background: #1e1e1e;  /* Fonds de bloc (gris foncé) */
            --color-text-primary: #f8f9fa;     /* Texte principal (blanc) */
            --color-text-secondary: #adb5bd;   /* Texte secondaire/gris clair */
            --color-border: #2c2c2c;           /* Bordures sombres */
            --color-shadow: 0 1px 3px 0 rgba(255, 255, 255, 0.08), 0 1px 2px 0 rgba(255, 255, 255, 0.05); /* Ombre douce inversée */
        }

        /* Application des variables aux utilitaires Tailwind existants et de base */
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--color-background) !important;
            color: var(--color-text-primary) !important;
            transition: background-color 0.3s, color 0.3s;
        } 
        
        /* Overrides Tailwind pour un design universel (Clair/Sombre) */
        .bg-white, .bg-gray-100 {
            background-color: var(--color-card-background) !important; 
        }

        .text-gray-900, .text-gray-800, .text-gray-700 {
            color: var(--color-text-primary) !important; 
        }

        .text-gray-500, .text-gray-600 {
            color: var(--color-text-secondary) !important; 
        }
        
        .border-gray-300 {
            border-color: var(--color-border) !important;
        }
        
        .shadow-sm {
            box-shadow: var(--color-shadow) !important;
        }
        
        /* Logo (DA: Couleur primaire unie, plus de dégradé) */
        .logo-text {
            color: var(--color-primary) !important;
            background-image: none !important;
            -webkit-background-clip: unset !important;
            -webkit-text-fill-color: unset !important;
        }

        /* Boutons d'action (Couleur primaire) */
        .bg-blue-600, .bg-blue-500 {
            background-color: var(--color-primary) !important;
            color: white !important;
        }

        .hover\:bg-blue-700:hover, .hover\:bg-blue-600:hover {
            background-color: var(--color-primary-hover) !important;
        }

        .ring-blue-500 {
            /* Utilisez une transparence sur la couleur primaire pour l'anneau de l'avatar */
            --tw-ring-color: rgba(0, 123, 255, 0.5); 
        }

        /* Styles existants conservés */
        .loader { border: 4px solid var(--color-border); border-top: 4px solid var(--color-primary); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; } 
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } } 
        .body-no-scroll { overflow: hidden; } 
    </style>
</head>
<body class="flex flex-col min-h-screen" data-theme="light"> 

    <header class="bg-white shadow-sm sticky top-0 z-40">
        <nav class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
            <div class="flex items-center justify-between h-16">
                <div class="flex-shrink-0">
                    <a href="index.php" class="text-2xl font-bold logo-text">ColoMap</a>
                </div>

                <div class="hidden md:block">
                    <div class="ml-10 flex items-center space-x-6">
                        <a href="index.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Accueil</a>

                        <?php if ($is_animateur): ?>
                            <a href="espace-animation.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Espace Animation</a>
                        <?php endif; ?>

                        <?php if ($is_director): ?>
                            <a href="organisateurs.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Organisateurs</a>
                        <?php endif; ?>

                        <?php if ($is_logged_in && !$is_animateur): ?>
                            <a href="favorites.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Mes Favoris</a>
                            <a href="reservations.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Réservations</a>
                        <?php endif; ?>
                        
                        <?php if ($is_logged_in): ?>
                            <a href="messagerie.php" class="relative text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                                <span>Messagerie</span>
                                <span id="unread-badge-desktop" class="absolute top-1 right-0 w-4 h-4 flex items-center justify-center bg-red-600 text-white text-xs font-bold rounded-full hidden"></span>
                            </a>
                        <?php endif; ?>

                        <a href="aide.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Aide</a>
                    </div>
                </div>

                <div class="hidden md:flex items-center">
                    <button id="theme-toggle-desktop" class="theme-toggle p-2 rounded-full text-gray-500 hover:bg-gray-100 transition-colors mr-4" title="Basculer le thème clair/sombre">
                        <span id="theme-toggle-icon-desktop" class="w-6 h-6 flex items-center justify-center text-xl">🌙</span> 
                    </button>

                    <?php if ($is_logged_in): ?>
                        <a href="profile.php" class="relative mr-3" title="Accéder à mon profil"><img class="h-9 w-9 rounded-full object-cover ring-2 ring-offset-2 ring-blue-600/50" src="<?php echo htmlspecialchars($_SESSION['user']['photo_url'] ?? $placeholder_url_desktop); ?>" alt="Photo de profil"></a>
                        <a href="api/logout.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300 text-sm">Déconnexion</a>
                    <?php else: ?>
                        <a href="login.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300 shadow-md">Connexion</a>
                    <?php endif; ?>
                </div>
                <div class="md:hidden flex items-center">
                    <button id="theme-toggle-mobile-trigger" class="theme-toggle inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none mr-2" title="Basculer le thème clair/sombre">
                         <span id="theme-toggle-icon-mobile-trigger" class="w-6 h-6 flex items-center justify-center text-xl">🌙</span>
                    </button>
                    <button id="open-menu-button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none"><svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg></button>
                </div>
            </div>
        </nav>
    </header>

    <div id="mobile-menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>
    <div id="mobile-menu" class="fixed top-0 right-0 h-full w-4/5 max-w-sm bg-white z-50 transform translate-x-full transition-transform duration-300 ease-in-out">
        <div class="p-4">
            <div class="flex items-center justify-between mb-8">
                <a href="index.php" class="text-2xl font-bold logo-text">ColoMap</a>
                <button id="close-menu-button" class="p-2 rounded-md text-gray-400"><svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
            </div>
            <nav class="flex flex-col space-y-2">
                <?php if ($is_logged_in): ?>
                    <a href="profile.php" class="flex items-center p-3 rounded-lg mb-4 bg-gray-100 hover:bg-gray-200 transition-colors"><img class="h-12 w-12 rounded-full object-cover mr-4" src="<?php echo htmlspecialchars($_SESSION['user']['photo_url'] ?? $placeholder_url_mobile); ?>" alt="Photo de profil"><div><div class="text-base font-bold text-gray-900"><?php echo htmlspecialchars($_SESSION['user']['prenom']) . ' ' . htmlspecialchars($_SESSION['user']['nom']); ?></div><div class="text-sm font-medium text-blue-600">Voir mon profil</div></div></a>
                <?php endif; ?>
                
                <button id="theme-toggle-mobile" class="theme-toggle flex items-center justify-start p-3 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                    <span id="theme-toggle-icon-mobile" class="w-6 h-6 mr-3 flex items-center justify-center text-xl">🌙</span>
                    <span>Basculer le Thème (Clair/Sombre)</span>
                </button>
                <hr class="my-2 border-gray-200">
                
                <a href="index.php" class="text-gray-700 hover:bg-gray-100 block px-3 py-3 rounded-md text-base font-medium">Accueil</a>
                
                <?php if ($is_animateur): ?>
                    <a href="espace-animation.php" class="text-gray-700 hover:bg-gray-100 block px-3 py-3 rounded-md text-base font-medium">Espace Animation</a>
                <?php endif; ?>

                <?php if ($is_director): ?>
                    <a href="organisateurs.php" class="text-gray-700 hover:bg-gray-100 block px-3 py-3 rounded-md text-base font-medium">Organisateurs</a>
                <?php endif; ?>
                
                <?php if ($is_logged_in && !$is_animateur): ?>
                    <a href="favorites.php" class="text-gray-700 hover:bg-gray-100 block px-3 py-3 rounded-md text-base font-medium">Mes Favoris</a>
                    <a href="reservations.php" class="text-gray-700 hover:bg-gray-100 block px-3 py-3 rounded-md text-base font-medium">Réservations</a>
                <?php endif; ?>
                
                <?php if ($is_logged_in): ?>
                    <a href="messagerie.php" class="relative text-gray-700 hover:bg-gray-100 block px-3 py-3 rounded-md text-base font-medium"><span>Messagerie</span><span id="unread-badge-mobile" class="absolute top-2 left-24 w-4 h-4 flex items-center justify-center bg-red-600 text-white text-xs font-bold rounded-full hidden"></span></a>
                <?php endif; ?>
                <a href="aide.php" class="text-gray-700 hover:bg-gray-100 block px-3 py-3 rounded-md text-base font-medium">Aide</a>
                <hr class="my-4 border-gray-200">
                <?php if ($is_logged_in): ?>
                    <a href="api/logout.php" class="bg-gray-100 text-gray-700 hover:bg-gray-200 block px-3 py-3 rounded-md text-base font-medium text-center">Déconnexion</a>
                <?php else: ?>
                    <a href="login.php" class="bg-blue-500 text-white hover:bg-blue-600 block px-3 py-3 rounded-md text-base font-medium text-center shadow-md">Connexion / Inscription</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>

<script>
    // Ajout de la logique de bascule de thème et de persistance
    function toggleTheme() {
        const body = document.body;
        const currentTheme = body.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        body.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeIcons(newTheme);
    }

    function updateThemeIcons(theme) {
        // Icônes de soleil ☀️ et lune 🌙 (vous pouvez les remplacer par des SVGs)
        const iconDesktop = document.getElementById('theme-toggle-icon-desktop');
        const iconMobileTrigger = document.getElementById('theme-toggle-icon-mobile-trigger');
        const iconMobile = document.getElementById('theme-toggle-icon-mobile');

        const newIcon = theme === 'dark' ? '☀️' : '🌙';
        
        if (iconDesktop) iconDesktop.textContent = newIcon;
        if (iconMobileTrigger) iconMobileTrigger.textContent = newIcon;
        if (iconMobile) iconMobile.textContent = newIcon;
    }

    document.addEventListener('DOMContentLoaded', function () {
        // --- LOGIQUE DARK/LIGHT MODE ---
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.body.setAttribute('data-theme', savedTheme);
        updateThemeIcons(savedTheme);

        // Attacher les écouteurs d'événements aux boutons de bascule de thème
        document.getElementById('theme-toggle-desktop')?.addEventListener('click', toggleTheme);
        document.getElementById('theme-toggle-mobile-trigger')?.addEventListener('click', toggleTheme);
        document.getElementById('theme-toggle-mobile')?.addEventListener('click', (e) => {
            // Empêcher la propagation pour ne pas fermer le menu mobile si cet élément est cliqué
            e.stopPropagation(); 
            toggleTheme();
        });


        // --- LOGIQUE MENU MOBILE (EXISTANTE) ---
        const openMenuButton = document.getElementById('open-menu-button');
        const closeMenuButton = document.getElementById('close-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        const overlay = document.getElementById('mobile-menu-overlay');
        const body = document.body;
        
        // La logique d'ouverture/fermeture du menu a été conservée
        if(openMenuButton) { openMenuButton.addEventListener('click', () => { mobileMenu.classList.remove('translate-x-full'); overlay.classList.remove('hidden'); body.classList.add('body-no-scroll'); }); }
        if(closeMenuButton) { closeMenuButton.addEventListener('click', () => { mobileMenu.classList.add('translate-x-full'); overlay.classList.add('hidden'); body.classList.remove('body-no-scroll'); }); }
        if(overlay) { overlay.addEventListener('click', () => { mobileMenu.classList.add('translate-x-full'); overlay.classList.add('hidden'); body.classList.remove('body-no-scroll'); }); }

        <?php if ($is_logged_in): ?>
        // --- LOGIQUE MESSAGERIE (EXISTANTE) ---
        async function fetchUnreadCount() {
            try {
                const response = await fetch('api/get_unread_messages_count.php');
                if (!response.ok) return;
                const data = await response.json();
                const count = data.count || 0;
                const badgeDesktop = document.getElementById('unread-badge-desktop');
                const badgeMobile = document.getElementById('unread-badge-mobile');
                if (count > 0) {
                    if(badgeDesktop) { badgeDesktop.textContent = count; badgeDesktop.classList.remove('hidden'); }
                    if(badgeMobile) { badgeMobile.textContent = count; badgeMobile.classList.remove('hidden'); }
                } else {
                    if(badgeDesktop) badgeDesktop.classList.add('hidden');
                    if(badgeMobile) badgeMobile.classList.add('hidden');
                }
            } catch (error) { console.error("Erreur de notif:", error); }
        }
        fetchUnreadCount();
        setInterval(fetchUnreadCount, 60000);
        <?php endif; ?>
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>