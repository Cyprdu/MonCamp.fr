<?php
// Fichier: /animateur.php (mis à jour)
require_once 'partials/header.php';

if (!isset($_SESSION['user']) || !($_SESSION['user']['is_animateur'] ?? false)) {
    header('Location: index');
    exit;
}
?>
<title>Espace Animateur - ColoMap</title>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl py-6">
    <section class="text-center mt-4 mb-8">
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight text-gray-900">
            Espace <span class="bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 bg-clip-text text-transparent">Animateur</span>
        </h1>
        <p class="mt-4 max-w-2xl mx-auto text-lg text-gray-500">
            Bienvenue. Retrouvez ici la liste de tous les camps disponibles sur la plateforme.
        </p>
    </section>

    <section class="mt-8">
        <h2 class="text-2xl font-bold mb-6">Tous les camps disponibles</h2>
        <div id="loader" class="flex justify-center items-center h-64"><div class="loader"></div></div>
        <div id="camps-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"></div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campsListContainer = document.getElementById('camps-list');
    const loader = document.getElementById('loader');

    async function fetchAndDisplayCamps() {
        loader.style.display = 'flex';
        campsListContainer.innerHTML = '';
        const apiUrl = `api/get_camps.php`;
        
        try {
            const response = await fetch(apiUrl);
            if (!response.ok) {
                throw new Error('Erreur réseau lors de la récupération des camps.');
            }
            const camps = await response.json();
            loader.style.display = 'none';

            if (camps.error || camps.length === 0) {
                campsListContainer.innerHTML = `<p class="text-gray-500 col-span-full text-center">Aucun camp disponible pour le moment.</p>`;
                return;
            }
            renderCamps(camps);
        } catch (error) {
            loader.style.display = 'none';
            campsListContainer.innerHTML = `<p class="text-red-500 col-span-full text-center">${error.message}</p>`;
        }
    }
    
    function renderCamps(camps) {
        let newContent = '';
        camps.forEach(camp => {
            newContent += `
                <div class="bg-white rounded-lg shadow-md overflow-hidden transform hover:-translate-y-1 transition-transform duration-300 group" onclick="window.location.href='info-camp-animateur?id=${camp.id}'" style="cursor: pointer;">
                    <div class="relative">
                         <img src="${camp.image_url}" alt="Image pour ${camp.nom}" class="w-full h-48 object-cover" onerror="this.onerror=null;this.src='https://placehold.co/600x400/e2e8f0/cbd5e0?text=Image+invalide';">
                    </div>
                    <div class="p-4">
                         <h3 class="font-bold text-lg mb-2 truncate">${camp.nom}</h3>
                         <p class="text-gray-600 text-sm mb-1">📍 ${camp.ville}</p>
                         <p class="text-gray-600 text-sm mb-3">🎂 ${camp.age_min} - ${camp.age_max} ans</p>
                         <div class="flex justify-between items-center">
                             <p class="text-blue-600 font-bold text-lg">${camp.prix}€</p>
                              <span class="text-xs font-semibold text-gray-500">${new Date(camp.date_debut).toLocaleDateString('fr-FR')}</span>
                         </div>
                    </div>
                </div>`;
        });
        campsListContainer.innerHTML = newContent;
    }

    fetchAndDisplayCamps();
});
</script>
</body>
</html>