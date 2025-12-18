<?php
// Fichier: /demandes-animation.php
require_once 'partials/header.php';

if (!isset($_SESSION['user']) || !$_SESSION['user']['is_directeur']) {
    header('Location: index.php');
    exit;
}
?>

<title>Demandes d'Animation - Espace Organisateur</title>

<main class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <a href="organisateurs.php" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>
            Retour
        </a>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-lg border">
        <h1 class="text-2xl font-bold mb-4">Candidatures d'animateurs</h1>
        <div id="applications-list" class="space-y-4"></div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const listContainer = document.getElementById('applications-list');
    listContainer.innerHTML = '<p class="text-gray-500 text-center py-4">Chargement...</p>';

    try {
        const response = await fetch('api/get_animator_applications.php');
        if (!response.ok) throw new Error('Erreur réseau.');
        const applications = await response.json();

        if (applications.length === 0) {
            listContainer.innerHTML = '<p class="text-gray-500 text-center py-4">Aucune candidature pour le moment.</p>';
            return;
        }

        listContainer.innerHTML = '';
        applications.forEach(app => {
            const card = `
                <div class="bg-gray-50 p-4 rounded-lg border">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-bold text-lg">${app.candidat_nom}</p>
                            <p class="text-sm text-blue-600">Pour le camp : ${app.camp_nom}</p>
                            <p class="text-xs text-gray-500 mt-1">Contact: ${app.candidat_mail} / ${app.candidat_tel}</p>
                        </div>
                        <div class="text-right">
                             <p class="text-sm font-medium">Candidature ${app.statut}</p>
                             <p class="text-xs text-gray-400">${new Date(app.date).toLocaleDateString('fr-FR')}</p>
                        </div>
                    </div>
                    <div class="mt-4 border-t pt-3">
                        <p class="text-sm text-gray-700">${app.motivation}</p>
                    </div>
                </div>`;
            listContainer.innerHTML += card;
        });
    } catch (error) {
        listContainer.innerHTML = `<p class="text-red-500 font-bold text-center py-4">${error.message}</p>`;
    }
});
</script>
</body>
</html>