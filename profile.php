<?php
require_once 'partials/header.php';

// Sécurité : si l'utilisateur n'est pas connecté, on le redirige.
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Récupération des statuts depuis la session
$is_admin = $_SESSION['user']['is_admin'] ?? false;
$is_director = $_SESSION['user']['is_directeur'] ?? false;
?>

<title>Mon Espace Personnel - ColoMap</title>

<main class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">

    <div class="mb-8">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Mon Espace Personnel</h1>
        <p class="mt-1 text-lg text-gray-600">Gérez vos informations, vos enfants et vos accès.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <a href="edit_profile.php" class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 hover:border-blue-500 hover:ring-2 hover:ring-blue-200 transition-all cursor-pointer group">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-colors">
                    <i class="fa-solid fa-user-pen text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-gray-900">Mes Informations</h3>
                    <p class="text-sm text-gray-500">Modifier mon profil</p>
                </div>
            </div>
        </a>

        <a href="children.php" class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 hover:border-orange-500 hover:ring-2 hover:ring-orange-200 transition-all cursor-pointer group">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center group-hover:bg-orange-600 group-hover:text-white transition-colors">
                    <i class="fa-solid fa-children text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-gray-900">Mes Enfants</h3>
                    <p class="text-sm text-gray-500">Gérer les fiches enfants</p>
                </div>
            </div>
        </a>

        <?php if ($is_director): ?>
        <a href="organisateurs.php" class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 hover:border-purple-500 hover:ring-2 hover:ring-purple-200 transition-all cursor-pointer group">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition-colors">
                    <i class="fa-solid fa-briefcase text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-gray-900">Espace Organisateur</h3>
                    <p class="text-sm text-gray-500">Gérer mes camps</p>
                </div>
            </div>
        </a>
        <?php endif; ?>

        <?php if (!$is_director && !$is_admin): ?>
        <div onclick="requestDirectorAccess(this)" class="bg-gray-50 p-6 rounded-xl shadow-md border border-gray-200 hover:border-purple-500 hover:bg-white hover:ring-2 hover:ring-purple-200 transition-all cursor-pointer group">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-white border border-gray-200 text-gray-400 flex items-center justify-center group-hover:border-purple-500 group-hover:text-purple-600 transition-colors">
                    <i class="fa-solid fa-building-user text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-gray-700 group-hover:text-purple-700">Devenir Organisateur</h3>
                    <p class="text-sm text-gray-500">Demander un accès directeur</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($is_admin): ?>
        <a href="admin/default.php" class="relative bg-red-50 p-6 rounded-xl shadow-lg border border-red-200 hover:border-red-500 hover:ring-2 hover:ring-red-200 transition-all cursor-pointer md:col-span-2 lg:col-span-1">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-red-100 text-red-600 flex items-center justify-center">
                    <i class="fa-solid fa-gauge-high text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-red-900">Administration</h3>
                    <p class="text-sm text-red-700">Gérer le site</p>
                </div>
                <span id="admin-notif-badge-profile" class="absolute top-3 right-3 w-6 h-6 flex items-center justify-center bg-red-600 text-white text-xs font-bold rounded-full hidden"></span>
            </div>
        </a>
        <?php endif; ?>

        <a href="api/logout.php" class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 hover:border-red-500 hover:bg-red-50 transition-all cursor-pointer group">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center group-hover:bg-red-500 group-hover:text-white transition-colors">
                    <i class="fa-solid fa-right-from-bracket text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-gray-900 group-hover:text-red-700">Déconnexion</h3>
                    <p class="text-sm text-gray-500 group-hover:text-red-600">Fermer ma session</p>
                </div>
            </div>
        </a>

    </div>
</main>

<script>
// Fonction pour demander l'accès directeur
async function requestDirectorAccess(element) {
    if(!confirm("Vous êtes sur le point de demander un accès Organisateur (Directeur). Confirmer ?")) return;

    // Feedback visuel
    const originalContent = element.innerHTML;
    element.innerHTML = '<div class="flex items-center justify-center h-12 text-purple-600"><i class="fa-solid fa-circle-notch fa-spin text-2xl"></i></div>';
    element.style.pointerEvents = 'none';

    try {
        const response = await fetch('api/request_director_access.php', { method: 'POST' });
        const data = await response.json();

        if (data.success) {
            alert("✅ Votre demande a bien été envoyée ! Elle sera examinée par un administrateur.");
            element.remove(); // On cache le bouton
        } else {
            alert("❌ " + (data.message || "Une erreur est survenue."));
            element.innerHTML = originalContent;
            element.style.pointerEvents = 'auto';
        }
    } catch (error) {
        console.error(error);
        alert("❌ Erreur de connexion au serveur.");
        element.innerHTML = originalContent;
        element.style.pointerEvents = 'auto';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    <?php if ($is_admin): ?>
    // Notifs Admin
    async function getAdminNotifCount() {
        try {
            const [requestResponse, campResponse] = await Promise.all([
                fetch('api/get_request_count.php'),
                fetch('api/get_camp_request_count.php')
            ]);

            if (!requestResponse.ok || !campResponse.ok) return;

            const requestData = await requestResponse.json();
            const campData = await campResponse.json();
            const totalCount = (requestData.count || 0) + (campData.count || 0);
            
            const badge = document.getElementById('admin-notif-badge-profile');
            if (badge && totalCount > 0) {
                badge.textContent = totalCount;
                badge.classList.remove('hidden');
            }
        } catch (error) { console.error(error); }
    }
    getAdminNotifCount();
    <?php endif; ?>
});
</script>

