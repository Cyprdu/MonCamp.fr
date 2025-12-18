<?php
require_once 'partials/header.php';

// SÉCURITÉ
if (!isset($_SESSION['user']) || !$_SESSION['user']['is_directeur']) {
    header('Location: index.php');
    exit;
}
$initial = strtoupper(substr($_SESSION['user']['prenom'], 0, 1));
$placeholder_avatar_url = "https://placehold.co/360x360/e2e8f0/2563eb?text=" . urlencode($initial);
?>
<title>Espace Organisateur - ColoMap</title>

<main class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <div class="flex items-center gap-4 p-6 bg-white rounded-xl shadow border mb-12">
        <img class="h-16 w-16 rounded-full object-cover" 
             src="<?php echo htmlspecialchars($_SESSION['user']['photo_url'] ?? $placeholder_avatar_url); ?>" 
             alt="Photo de profil de l'organisateur">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Bienvenue dans votre Espace Organisateur</h1>
            <p class="text-gray-600">Gérez vos camps, ajoutez-en de nouveaux et communiquez avec les parents et animateurs.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <a href="mes_camps" class="bg-white p-6 rounded-xl shadow-lg border hover:border-blue-500 hover:ring-2 hover:ring-blue-200 transition-all cursor-pointer">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6"><path d="M4.5 3.75a3 3 0 0 0-3 3v.75h21v-.75a3 3 0 0 0-3-3h-15Z" /><path fill-rule="evenodd" d="M22.5 9.75h-21v7.5a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3v-7.5Zm-18 3.75a.75.75 0 0 1 .75-.75h6a.75.75 0 0 1 0 1.5h-6a.75.75 0 0 1-.75-.75Zm.75 2.25a.75.75 0 0 0 0 1.5h3a.75.75 0 0 0 0-1.5h-3Z" clip-rule="evenodd" /></svg></div>
                <div>
                    <h3 class="font-bold text-lg">Gérer mes camps</h3>
                    <p class="text-sm text-gray-500">Modifier ou supprimer vos camps existants.</p>
                </div>
            </div>
        </a>

        <a href="create_camp" class="bg-white p-6 rounded-xl shadow-lg border hover:border-green-500 hover:ring-2 hover:ring-green-200 transition-all cursor-pointer">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-green-100 text-green-600 flex items-center justify-center"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 9a.75.75 0 0 0-1.5 0v2.25H9a.75.75 0 0 0 0 1.5h2.25V15a.75.75 0 0 0 1.5 0v-2.25H15a.75.75 0 0 0 0-1.5h-2.25V9Z" clip-rule="evenodd" /></svg></div>
                <div>
                    <h3 class="font-bold text-lg">Créer un camp</h3>
                    <p class="text-sm text-gray-500">Ajouter un nouveau séjour.</p>
                </div>
            </div>
        </a>

        <a href="public_infos" class="bg-white p-6 rounded-xl shadow-lg border hover:border-purple-500 hover:ring-2 hover:ring-purple-200 transition-all cursor-pointer">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg></div>
                <div>
                    <h3 class="font-bold text-lg">Mes Organismes</h3>
                    <p class="text-sm text-gray-500">Gérer vos informations publiques.</p>
                </div>
            </div>
        </a>
        
        <a href="demandes-animation" class="relative bg-white p-6 rounded-xl shadow-lg border hover:border-yellow-500 hover:ring-2 hover:ring-yellow-200 transition-all cursor-pointer">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-yellow-100 text-yellow-600 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6"><path d="M10.5 6A2.25 2.25 0 0 0 8.25 8.25V10.5a2.25 2.25 0 0 0 4.5 0v-2.25A2.25 2.25 0 0 0 10.5 6Zm-3.75 3.75a.75.75 0 0 0-1.5 0v4.5a.75.75 0 0 0 1.5 0v-4.5ZM10.5 12a.75.75 0 0 0-1.5 0v6.75a.75.75 0 0 0 1.5 0v-6.75ZM15 9.75a.75.75 0 0 1 1.5 0v4.5a.75.75 0 0 1-1.5 0v-4.5ZM12.75 12a.75.75 0 0 1 1.5 0v6.75a.75.75 0 0 1-1.5 0v-6.75Z" /><path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3A5.25 5.25 0 0 0 12 1.5ZM9.75 6.75v3a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1-.75-.75v-3a3.75 3.75 0 0 1 7.5 0v3a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1-.75-.75v-3a2.25 2.25 0 0 0-4.5 0Z" clip-rule="evenodd" /></svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg">Demandes d'animation</h3>
                    <p class="text-sm text-gray-500">Gérer les candidatures d'animateurs.</p>
                </div>
            </div>
        </a>

        <a href="messagerie" class="relative bg-white p-6 rounded-xl shadow-lg border hover:border-pink-500 hover:ring-2 hover:ring-pink-200 transition-all cursor-pointer">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-pink-100 text-pink-600 flex items-center justify-center"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg></div>
                <div>
                    <h3 class="font-bold text-lg">Messagerie</h3>
                    <p class="text-sm text-gray-500">Échanger avec les parents.</p>
                </div>
                <span id="unread-badge" class="absolute top-3 right-3 w-6 h-6 flex items-center justify-center bg-red-600 text-white text-xs font-bold rounded-full hidden"></span>
            </div>
        </a>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const badge = document.getElementById('unread-badge');
    if (badge) {
        try {
            const response = await fetch('api/get_unread_messages_count.php');
            const data = await response.json();
            if (data.count > 0) {
                badge.textContent = data.count;
                badge.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Erreur de notification:', error);
        }
    }
});
</script>
</body>
</html>