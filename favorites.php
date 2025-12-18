<?php
require_once 'partials/header.php';

if (!isset($_SESSION['user'])) {
    header('Location: login');
    exit;
}
$user_favorites = $_SESSION['user']['favorites'] ?? [];
?>

<title>Mes Favoris - ColoMap</title>

<main class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <div class="text-center mb-12">
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight text-gray-900">
            Mes <span class="bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 bg-clip-text text-transparent">Favoris</span>
        </h1>
        <p class="mt-4 max-w-2xl mx-auto text-lg text-gray-500">
            Retrouvez ici tous les camps que vous avez sauvegardés.
        </p>
    </div>
    
    <div id="favorites-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"></div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const listContainer = document.getElementById('favorites-list');
    let userFavorites = <?php echo json_encode($user_favorites); ?>;

    async function fetchFavorites() {
        listContainer.innerHTML = '<p class="text-gray-500 col-span-full text-center py-10">Chargement...</p>';
        try {
            const response = await fetch('api/get_favorites.php');
            if (!response.ok) throw new Error('Erreur réseau.');
            const camps = await response.json();
            
            listContainer.innerHTML = '';
            if (camps.length === 0) {
                listContainer.innerHTML = '<p class="text-gray-500 col-span-full text-center py-10">Aucun favori pour le moment.</p>';
                return;
            }
            renderCamps(camps);
        } catch (error) {
            listContainer.innerHTML = `<p class="text-red-500 font-bold col-span-full text-center py-10">${error.message}</p>`;
        }
    }

    function renderCamps(camps) {
        let newContent = '';
        camps.forEach(camp => {
            const isFavorited = userFavorites.includes(camp.id);
            // SÉCURITÉ : Lien uniquement par Token
            const linkUrl = `camp_details?t=${camp.token}`;

            newContent += `
                <div class="bg-white rounded-lg shadow-md overflow-hidden transform hover:-translate-y-1 transition-transform duration-300 group">
                    <div class="relative">
                        <img src="${camp.image_url}" alt="${camp.nom}" class="w-full h-48 object-cover cursor-pointer" onclick="window.location.href='${linkUrl}'">
                        <button class="favorite-button absolute top-3 right-3 bg-white/70 backdrop-blur-sm p-2 rounded-full transition-all hover:scale-110" data-camp-id="${camp.id}">
                            <svg class="w-5 h-5 ${isFavorited ? 'text-red-500 fill-current' : 'text-gray-600'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.5l1.318-1.182a4.5 4.5 0 116.364 6.364L12 21l-7.682-7.682a4.5 4.5 0 010-6.364z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="p-4 cursor-pointer" onclick="window.location.href='${linkUrl}'">
                         <h3 class="font-bold text-lg mb-2 truncate">${camp.nom}</h3>
                         <p class="text-gray-600 text-sm mb-1">📍 ${camp.ville}</p>
                         <p class="text-blue-600 font-bold text-lg">${camp.prix}€</p>
                    </div>
                </div>`;
        });
        listContainer.innerHTML = newContent;
        
        document.querySelectorAll('.favorite-button').forEach(btn => {
            btn.addEventListener('click', toggleFavorite);
        });
    }

    async function toggleFavorite(event) {
        const button = event.currentTarget;
        const campCard = button.closest('.group');
        const campId = button.dataset.campId;

        try {
            const response = await fetch('api/toggle_favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ campId })
            });
            const result = await response.json();
            if (result.success && !result.isFavorited) {
                campCard.remove();
                if (listContainer.children.length === 0) {
                     listContainer.innerHTML = '<p class="text-gray-500 col-span-full text-center py-10">Aucun favori pour le moment.</p>';
                }
            }
        } catch (error) { console.error(error); }
    }

    fetchFavorites();
});
</script>
</body>
</html>