<?php
require_once 'partials/header.php';

// SÉCURITÉ : On vérifie que l'utilisateur est connecté ET qu'il est admin.
if (!isset($_SESSION['user']) || !($_SESSION['user']['is_admin'] ?? false)) {
    header('Location: index.php');
    exit;
}
?>

<title>Historique des Camps Refusés - Admin</title>

<main class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <a href="admin" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>
            Retour au panneau d'administration
        </a>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-lg border">
        <h1 class="text-2xl font-bold mb-4">Historique des Camps Refusés</h1>
        <div id="refused-camps-list" class="space-y-3">
            <!-- La liste sera chargée ici par JavaScript -->
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const listContainer = document.getElementById('refused-camps-list');

    async function fetchRefusedCamps() {
        listContainer.innerHTML = '<p class="text-center text-gray-500 py-4">Chargement de l\'historique...</p>';
        try {
            const response = await fetch('api/get_refused_camps.php');
            if (!response.ok) throw new Error('Erreur réseau.');
            const camps = await response.json();
            
            listContainer.innerHTML = '';
            if(camps.length === 0) {
                listContainer.innerHTML = '<p class="text-center text-gray-500 py-4">Aucun camp n\'a encore été refusé.</p>';
                return;
            }

            camps.forEach(camp => {
                const campCard = document.createElement('div');
                campCard.className = "camp-card bg-red-50 p-3 rounded-lg border border-red-200 flex justify-between items-center";
                campCard.id = `camp-${camp.id}`;
                campCard.innerHTML = `
                    <div>
                        <p class="font-semibold text-gray-800">${camp.nom}</p>
                        <p class="text-sm text-red-800">${camp.ville}</p>
                    </div>
                    <button class="requeue-button text-xs bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-2 py-1 rounded-md" data-campid="${camp.id}">
                        Remettre en attente
                    </button>
                `;
                listContainer.appendChild(campCard);
            });

            document.querySelectorAll('.requeue-button').forEach(button => {
                button.addEventListener('click', handleRequeueClick);
            });

        } catch (error) {
            listContainer.innerHTML = `<p class="text-red-500 font-bold text-center py-4">${error.message}</p>`;
        }
    }

    async function handleRequeueClick(event) {
        const button = event.currentTarget;
        const campId = button.dataset.campid;

        if (!confirm('Voulez-vous vraiment remettre ce camp dans la liste des demandes en attente ?')) {
            return;
        }

        button.disabled = true;
        button.textContent = '...';

        try {
            const response = await fetch('api/requeue_camp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ campId })
            });

            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Une erreur est survenue.');

            // Supprimer la carte de l'interface si l'opération a réussi
            const cardToRemove = document.getElementById(`camp-${campId}`);
            if (cardToRemove) {
                cardToRemove.remove();
            }
            if (listContainer.children.length === 0) {
                listContainer.innerHTML = '<p class="text-center text-gray-500 py-4">Aucun camp n\'a encore été refusé.</p>';
            }

        } catch (error) {
            alert('Erreur: ' + error.message);
            button.disabled = false;
            button.textContent = 'Remettre en attente';
        }
    }

    fetchRefusedCamps();
});
</script>

</body>
</html>
