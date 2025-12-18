<?php
// Fichier: /partials/header.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function clean_url($url) {
    return str_replace(".php", "", $url);
}

$is_logged_in = isset($_SESSION['user']);
$is_director = $is_logged_in && ($_SESSION['user']['is_directeur'] ?? false);
$is_admin = $is_logged_in && ($_SESSION['user']['is_admin'] ?? false);
$is_animateur = $is_logged_in && ($_SESSION['user']['is_animateur'] ?? false);

// Avatar
$initial = 'U';
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
    
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3659884670016121" crossorigin="anonymous"></script>
    
    <link rel="icon" type="image/png" href="favico.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style> 
        body { font-family: 'Inter', sans-serif; } 
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; } 
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } } 
        .body-no-scroll { overflow: hidden; } 
        /* Animation du dropdown */
        .dropdown-menu { display: none; }
        .group:hover .dropdown-menu { display: block; animation: fadeIn 0.2s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 flex flex-col min-h-screen">

    <header class="bg-white shadow-sm sticky top-0 z-40">
        <nav class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
            <div class="flex items-center justify-between h-16 gap-4">
                
                <div class="flex-shrink-0 flex items-center p-1 z-10">
                    <a href="<?php echo clean_url('../'); ?>" class="text-2xl font-bold block whitespace-nowrap"><span class="bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 bg-clip-text text-transparent">ColoMap</span></a>
                </div>

                <div id="search-wrapper" class="hidden md:block flex-1 max-w-md transition-all duration-500 ease-in-out mx-auto px-4">
                    <form action="recherche" method="GET" class="relative group h-full flex items-center">
                        <input type="text" id="desktop-search-input" name="name" placeholder="Rechercher une colo..." autocomplete="off"
                               class="w-full bg-gray-100 border border-transparent focus:border-blue-300 rounded-full py-2 pl-4 pr-10 focus:ring-4 focus:ring-blue-100 focus:bg-white transition-all duration-300 text-sm shadow-sm relative z-20">
                        <button type="submit" class="absolute right-0 top-1/2 transform -translate-y-1/2 mr-3 text-gray-400 hover:text-blue-600 transition-colors z-20">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                        <div id="history-dropdown" class="absolute top-12 left-0 w-full bg-white shadow-xl rounded-2xl border border-gray-100 hidden overflow-hidden z-10 animate-fade-in-down"></div>
                    </form>
                </div>

                <div id="nav-links" class="hidden md:flex items-center space-x-2 flex-shrink-0 transition-all duration-500 ease-in-out overflow-hidden whitespace-nowrap opacity-100">
                    <a href="<?php echo clean_url('../'); ?>" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Accueil</a>

                    <?php if ($is_animateur): ?>
                        <a href="espace-animation" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Animation</a>
                    <?php endif; ?>

                    <?php if ($is_director): ?>
                        <a href="organisateurs" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Organisateurs</a>
                    <?php endif; ?>

                    <?php if ($is_logged_in && !$is_animateur): ?>
                        <a href="favorites" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Favoris</a>
                        <a href="reservations" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Réservations</a>
                    <?php endif; ?>

                    <a href="aide" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Aide</a>
                </div>

                <div class="hidden md:flex items-center gap-4 flex-shrink-0 pl-2 border-l border-transparent transition-all duration-300" id="user-actions">
                    <?php if ($is_logged_in): ?>
                        <a href="messagerie" class="relative text-gray-500 hover:text-gray-900 p-2 rounded-full hover:bg-gray-100 transition-colors" title="Messagerie">
                            <i class="fa-regular fa-envelope text-xl"></i>
                            <span id="unread-badge-desktop" class="absolute top-0 right-0 w-4 h-4 flex items-center justify-center bg-red-600 text-white text-[10px] font-bold rounded-full hidden"></span>
                        </a>
                        
                        <div class="relative group py-2">
                            <a href="profile" class="relative block">
                                <img class="h-9 w-9 rounded-full object-cover ring-2 ring-gray-100 transition-transform duration-300 transform group-hover:scale-110" src="<?php echo htmlspecialchars($_SESSION['user']['photo_url'] ?? $placeholder_url_desktop); ?>" alt="Profil">
                            </a>

                            <div class="dropdown-menu absolute right-0 top-full mt-2 w-72 bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden z-50">
                                
                                <div class="px-4 py-3 border-b border-gray-50 bg-gray-50">
                                    <p class="text-sm font-bold text-gray-900 truncate"><?php echo htmlspecialchars($_SESSION['user']['prenom'] . ' ' . $_SESSION['user']['nom']); ?></p>
                                    <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($_SESSION['user']['email']); ?></p>
                                </div>

                                <div class="py-1">
                                    <a href="edit_profile" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600">
                                        <i class="fa-solid fa-user-pen w-6 text-center mr-2 text-gray-400"></i> Modifier mes informations
                                    </a>
                                    <a href="children" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600">
                                        <i class="fa-solid fa-children w-6 text-center mr-2 text-gray-400"></i> Gérer mes enfants
                                    </a>
                                    <a href="favorites" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600">
                                        <i class="fa-solid fa-heart w-6 text-center mr-2 text-gray-400"></i> Favoris
                                    </a>
                                    <a href="reservations" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600">
                                        <i class="fa-solid fa-file-invoice w-6 text-center mr-2 text-gray-400"></i> Réservations
                                    </a>
                                    <a href="messagerie" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600">
                                        <i class="fa-regular fa-envelope w-6 text-center mr-2 text-gray-400"></i> Messagerie
                                    </a>
                                </div>

                                <?php if ($is_director): ?>
                                <div class="border-t border-gray-100 py-1">
                                    <div class="px-4 py-1 text-xs font-bold text-gray-400 uppercase tracking-wider">Espace Organisateur</div>
                                    <a href="public_infos" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">
                                        <i class="fa-solid fa-building w-6 text-center mr-2 text-purple-400"></i> Mes organismes
                                    </a>
                                    <a href="mes_camps" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">
                                        <i class="fa-solid fa-campground w-6 text-center mr-2 text-purple-400"></i> Gérer mes camps
                                    </a>
                                    <a href="create_camp" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">
                                        <i class="fa-solid fa-plus-circle w-6 text-center mr-2 text-purple-400"></i> Créer un camp
                                    </a>
                                    <a href="demandes-animation" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">
                                        <i class="fa-solid fa-users-gear w-6 text-center mr-2 text-purple-400"></i> Gestion des animateurs
                                    </a>
                                </div>
                                <?php endif; ?>

                                <?php if ($is_admin): ?>
                                <div class="border-t border-gray-100 py-1">
                                    <div class="px-4 py-1 text-xs font-bold text-red-400 uppercase tracking-wider">Administration</div>
                                    <a href="admin.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-bold">
                                        <i class="fa-solid fa-gauge-high w-6 text-center mr-2"></i> Dashboard Admin
                                    </a>
                                    <a href="admin_messages" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-bold">
                                        <i class="fa-solid fa-envelope-open-text w-6 text-center mr-2"></i> Réponse Contact
                                    </a>
                                </div>
                                <?php endif; ?>

                                <div class="border-t border-gray-100 py-1">
                                    <a href="aide" class="flex items-center px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                                        <i class="fa-regular fa-circle-question w-6 text-center mr-2"></i> Aide
                                    </a>
                                    <a href="api/logout" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-bold">
                                        <i class="fa-solid fa-arrow-right-from-bracket w-6 text-center mr-2"></i> Déconnexion
                                    </a>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <a href="login" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300 text-sm whitespace-nowrap">Connexion</a>
                    <?php endif; ?>
                </div>

                <div class="md:hidden flex items-center gap-2">
                    <button id="mobile-search-trigger" class="p-2 rounded-md text-gray-500 hover:bg-gray-100 transition-colors">
                        <i class="fa-solid fa-magnifying-glass text-lg"></i>
                    </button>
                    <button id="open-menu-button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                </div>
            </div>
        </nav>
    </header>

    <div id="mobile-menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden transition-opacity"></div>
    <div id="mobile-menu" class="fixed top-0 right-0 h-full w-4/5 max-sm bg-white z-50 transform translate-x-full transition-transform duration-300 ease-in-out shadow-2xl">
        <div class="p-4 flex flex-col h-full">
            <div class="flex items-center justify-between mb-6">
                <a href="<?php echo clean_url('../'); ?>" class="text-2xl font-bold"><span class="bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 bg-clip-text text-transparent">ColoMap</span></a>
                <button id="close-menu-button" class="p-2 rounded-md text-gray-400 hover:text-gray-600"><svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
            </div>

            <form action="recherche" method="GET" class="mb-6 relative">
                <input type="text" id="mobile-search-input" name="name" placeholder="Rechercher..." autocomplete="off"
                       class="w-full bg-gray-100 border border-gray-200 rounded-lg py-3 pl-4 pr-10 focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all">
                <button type="submit" class="absolute right-0 top-0 mt-3 mr-4 text-gray-400">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>

            <nav class="flex flex-col space-y-2 overflow-y-auto">
                <?php if ($is_logged_in): ?>
                    <a href="profile" class="flex items-center p-3 rounded-lg mb-4 bg-gray-50 border border-gray-100">
                        <img class="h-12 w-12 rounded-full object-cover mr-4" src="<?php echo htmlspecialchars($_SESSION['user']['photo_url'] ?? $placeholder_url_mobile); ?>" alt="Photo de profil">
                        <div>
                            <div class="text-base font-bold text-gray-900"><?php echo htmlspecialchars($_SESSION['user']['prenom']) . ' ' . htmlspecialchars($_SESSION['user']['nom']); ?></div>
                            <div class="text-sm font-medium text-blue-600">Voir mon profil</div>
                        </div>
                    </a>
                <?php endif; ?>
                
                <a href="<?php echo clean_url('../'); ?>" class="text-gray-700 hover:bg-gray-100 block px-3 py-3 rounded-md text-base font-medium">Accueil</a>
                
                <?php if ($is_animateur): ?>
                    <a href="espace-animation" class="text-gray-700 hover:bg-gray-100 block px-3 py-3 rounded-md text-base font-medium">Espace Animation</a>
                <?php endif; ?>

                <?php if ($is_director): ?>
                    <a href="organisateurs" class="text-gray-700 hover:bg-gray-100 block px-3 py-3 rounded-md text-base font-medium">Organisateurs</a>
                <?php endif; ?>
                
                <?php if ($is_logged_in && !$is_animateur): ?>
                    <a href="favorites" class="text-gray-700 hover:bg-gray-100 block px-3 py-3 rounded-md text-base font-medium">Mes Favoris</a>
                    <a href="reservations" class="text-gray-700 hover:bg-gray-100 block px-3 py-3 rounded-md text-base font-medium">Réservations</a>
                <?php endif; ?>

                <?php if ($is_admin): ?>
                    <div class="px-3 pt-4 pb-1 text-xs font-bold text-gray-400 uppercase">Administration</div>
                    <a href="admin" class="text-red-600 hover:bg-gray-100 block px-3 py-3 rounded-md text-base font-medium">Dashboard Admin</a>
                    <a href="admin_messages" class="text-red-600 hover:bg-gray-100 block px-3 py-3 rounded-md text-base font-medium">Réponse Contact</a>
                <?php endif; ?>
                
                <?php if ($is_logged_in): ?>
                    <a href="messagerie" class="relative text-gray-700 hover:bg-gray-100 block px-3 py-3 rounded-md text-base font-medium"><span>Messagerie</span><span id="unread-badge-mobile" class="absolute top-3 left-32 w-5 h-5 flex items-center justify-center bg-red-600 text-white text-xs font-bold rounded-full hidden"></span></a>
                <?php endif; ?>
                
                <a href="aide" class="text-gray-700 hover:bg-gray-100 block px-3 py-3 rounded-md text-base font-medium">Aide</a>
                
                <hr class="my-4 border-gray-200">
                
                <?php if ($is_logged_in): ?>
                    <a href="api/logout" class="bg-gray-100 text-gray-700 hover:bg-gray-200 block px-3 py-3 rounded-md text-base font-medium text-center">Déconnexion</a>
                <?php else: ?>
                    <a href="login" class="bg-blue-600 text-white hover:bg-blue-700 block px-3 py-3 rounded-md text-base font-medium text-center shadow-md shadow-blue-200">Connexion / Inscription</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const desktopSearchInput = document.getElementById('desktop-search-input');
        const navLinks = document.getElementById('nav-links');
        const searchWrapper = document.getElementById('search-wrapper');
        const historyDropdown = document.getElementById('history-dropdown');

        function renderHistory() {
            const history = JSON.parse(localStorage.getItem('colomap_search_history') || '[]');
            if (history.length === 0) {
                historyDropdown.innerHTML = '';
                historyDropdown.classList.add('hidden');
                return;
            }
            let html = '<div class="p-2">';
            html += '<div class="text-[10px] uppercase text-gray-400 font-bold px-3 py-1 mb-1">Récents</div>';
            history.forEach(item => {
                html += `
                <a href="recherche?name=${encodeURIComponent(item.term)}" class="flex items-center justify-between px-3 py-2 hover:bg-gray-50 rounded-lg group transition-colors">
                    <div class="flex items-center overflow-hidden">
                        <i class="fa-solid fa-clock-rotate-left text-gray-400 mr-3 text-sm group-hover:text-blue-500"></i>
                        <span class="text-sm font-bold text-gray-800 truncate">${item.term}</span>
                    </div>
                    <span class="text-xs text-gray-400 ml-2 whitespace-nowrap bg-gray-100 px-2 py-0.5 rounded-full">${item.count} résultats</span>
                </a>`;
            });
            html += '</div>';
            historyDropdown.innerHTML = html;
            historyDropdown.classList.remove('hidden');
        }

        if(desktopSearchInput) {
            desktopSearchInput.addEventListener('focus', () => {
                if(navLinks) { navLinks.style.maxWidth = '0px'; navLinks.style.opacity = '0'; navLinks.style.padding = '0'; navLinks.style.margin = '0'; }
                if(searchWrapper) { searchWrapper.classList.remove('max-w-md'); searchWrapper.classList.add('max-w-full', 'w-full'); }
                renderHistory();
            });
            desktopSearchInput.addEventListener('blur', () => {
                if(navLinks) { navLinks.style.maxWidth = '1000px'; navLinks.style.opacity = '1'; navLinks.style.padding = ''; navLinks.style.margin = ''; }
                if(searchWrapper) { searchWrapper.classList.remove('max-w-full', 'w-full'); searchWrapper.classList.add('max-w-md'); }
                setTimeout(() => { historyDropdown.classList.add('hidden'); }, 200);
            });
            desktopSearchInput.addEventListener('input', () => {
               if(desktopSearchInput.value.length > 0) { historyDropdown.classList.add('hidden'); } else { renderHistory(); }
            });
        }

        const openMenuButton = document.getElementById('open-menu-button');
        const closeMenuButton = document.getElementById('close-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        const overlay = document.getElementById('mobile-menu-overlay');
        const mobileSearchTrigger = document.getElementById('mobile-search-trigger');
        const mobileSearchInput = document.getElementById('mobile-search-input');
        const body = document.body;

        function openMenu(focusSearch = false) {
            mobileMenu.classList.remove('translate-x-full');
            overlay.classList.remove('hidden');
            body.classList.add('body-no-scroll');
            if(focusSearch && mobileSearchInput) { setTimeout(() => mobileSearchInput.focus(), 300); }
        }
        function closeMenu() {
            mobileMenu.classList.add('translate-x-full');
            overlay.classList.add('hidden');
            body.classList.remove('body-no-scroll');
        }
        if(openMenuButton) openMenuButton.addEventListener('click', () => openMenu(false));
        if(closeMenuButton) closeMenuButton.addEventListener('click', closeMenu);
        if(overlay) overlay.addEventListener('click', closeMenu);
        if(mobileSearchTrigger) mobileSearchTrigger.addEventListener('click', () => openMenu(true));

        <?php if ($is_logged_in): ?>
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
            } catch (error) { console.error(error); }
        }
        fetchUnreadCount();
        setInterval(fetchUnreadCount, 60000);
        <?php endif; ?>
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>