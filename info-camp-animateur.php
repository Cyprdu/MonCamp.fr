<?php
// Fichier: /info-camp-animateur.php
require_once 'partials/header.php';

// Sécurité : l'utilisateur doit être connecté et être un animateur.
if (!isset($_SESSION['user']) || !($_SESSION['user']['is_animateur'] ?? false)) {
    header('Location: login.php');
    exit;
}
$camp_id = $_GET['id'] ?? '';
?>

<title>Détails du Camp - Espace Animateur</title>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-6xl py-8">
    <div id="loader" class="text-center py-20"><div class="loader inline-block"></div><p class="mt-4 text-gray-600">Chargement...</p></div>
    
    <div id="camp-content" class="hidden">
        <div class="mb-6"><a href="animateur.php" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>Retour</a></div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-12">
            <div class="lg:col-span-2">
                <img id="camp-image" src="" alt="Image du camp" class="w-full h-auto object-cover rounded-xl shadow-lg mb-4">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h1 id="camp-name" class="text-4xl font-extrabold text-gray-900 mb-2"></h1>
                        <p id="camp-location" class="text-lg text-gray-500"></p>
                    </div>
                </div>
                <div class="prose max-w-none"><h2 class="text-2xl font-bold mb-4">Description du camp</h2><div id="camp-description" class="text-gray-700"></div></div>
            </div>
            
            <div class="lg:col-span-1">
                <div class="sticky top-24 bg-white p-6 rounded-xl shadow-lg border">
                    <h2 class="text-2xl font-bold mb-4">Informations Clés</h2>
                    <div class="space-y-4 mb-6">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-green-100 text-green-600 flex items-center justify-center"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5a.75.75 0 0 0 0 1.5h10.5a.75.75 0 0 0 0-1.5H4.75Z" clip-rule="evenodd" /></svg></div>
                            <div><p class="font-semibold">Dates</p><p id="camp-dates" class="text-gray-600"></p></div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0 4.438-.784 6.131-2.095a1.23 1.23 0 0 0 .41-1.412A9.99 9.99 0 0 0 10 12.001c-2.31 0-4.438.784-6.131 2.094Z" /></svg></div>
                            <div><p class="font-semibold">Âge des enfants</p><p id="camp-age" class="text-gray-600"></p></div>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4">
                        <h3 class="text-xl font-bold mb-3">Infos Animateurs</h3>
                        <div class="space-y-2 text-sm">
                             <p><strong>Nombre d'animateurs recherchés :</strong> <span id="anim-quota" class="font-bold text-blue-600"></span></p>
                        </div>
                    </div>

                    <div id="action-buttons" class="text-center border-t pt-6 mt-6">
                        <a href="inscription-animateur.php?id=<?php echo htmlspecialchars($camp_id); ?>" class="w-full block text-center bg-green-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-green-700">
                            S'inscrire en tant qu'animateur
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const loader = document.getElementById('loader');
    const campContent = document.getElementById('camp-content');
    const campId = '<?php echo $camp_id; ?>';

    try {
        const response = await fetch(`api/get_camp_details.php?id=${campId}`);
        if (!response.ok) throw new Error('Camp introuvable.');
        const camp = await response.json();
        
        document.title = `${camp.nom} - Infos Animateur`;
        document.getElementById('camp-image').src = camp.image_url;
        document.getElementById('camp-name').textContent = camp.nom;
        document.getElementById('camp-location').textContent = camp.ville;
        document.getElementById('camp-description').innerHTML = camp.description;

        const startDate = new Date(camp.date_debut).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' });
        const endDate = new Date(camp.date_fin).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
        document.getElementById('camp-dates').textContent = `Du ${startDate} au ${endDate}`;
        document.getElementById('camp-age').textContent = `${camp.age_min} - ${camp.age_max} ans`;
        
        // Récupération du quota d'animateurs
        document.getElementById('anim-quota').textContent = camp.quota_max_anim ? `${camp.quota_max_anim} places` : 'Non spécifié';

        loader.classList.add('hidden');
        campContent.classList.remove('hidden');

    } catch (error) {
        loader.innerHTML = `<p class="text-red-500 font-bold">${error.message}</p>`;
    }
});
</script>
</body>
</html>