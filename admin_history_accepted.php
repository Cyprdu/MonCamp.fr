<?php
require_once 'partials/header.php';

// SÉCURITÉ : On vérifie que l'utilisateur est connecté ET qu'il est admin.
if (!isset($_SESSION['user']) || !($_SESSION['user']['is_admin'] ?? false)) {
    header('Location: index.php');
    exit;
}
?>

<title>Directeurs Acceptés - Admin</title>

<main class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <a href="admin" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>
            Retour au panneau d'administration
        </a>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-lg border">
        <h1 class="text-2xl font-bold mb-4">Historique des Directeurs Acceptés</h1>
        <div id="accepted-list" class="space-y-3">
            <!-- La liste des directeurs acceptés sera chargée ici par JavaScript -->
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const listContainer = document.getElementById('accepted-list');
    listContainer.innerHTML = '<p class="text-center text-gray-500 py-4">Chargement de l\'historique...</p>';
    try {
        const response = await fetch('api/get_accepted_directors.php');
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Erreur réseau lors de la récupération de l\'historique.');
        }
        const directors = await response.json();
        
        listContainer.innerHTML = '';
        if(directors.length === 0) {
            listContainer.innerHTML = '<p class="text-center text-gray-500 py-4">Aucun directeur n\'a encore été accepté.</p>';
            return;
        }

        directors.forEach(director => {
            const directorCard = `
                <div class="bg-gray-50 p-3 rounded-lg border flex justify-between items-center">
                    <div>
                        <p class="font-semibold">${director.prenom} ${director.nom}</p>
                        <p class="text-sm text-gray-600">${director.mail}</p>
                    </div>
                    <span class="text-xs font-mono text-gray-400" title="ID de l'utilisateur">${director.id}</span>
                </div>
            `;
            listContainer.innerHTML += directorCard;
        });
    } catch (error) {
        listContainer.innerHTML = `<p class="text-red-500 font-bold text-center py-4">${error.message}</p>`;
    }
});
</script>

</body>
</html>
