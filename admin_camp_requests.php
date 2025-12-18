<?php
require_once 'partials/header.php';

if (!isset($_SESSION['user']) || !($_SESSION['user']['is_admin'] ?? false)) {
    header('Location: index');
    exit;
}
?>

<title>Admin - Demandes</title>

<main class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8"><a href="admin" class="text-gray-600 hover:text-gray-900">&larr; Retour Admin</a></div>
    <div class="bg-white p-6 rounded-xl shadow-lg border">
        <h1 class="text-2xl font-bold mb-4">Camps en attente</h1>
        <div id="camp-requests-list" class="space-y-4"></div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const listContainer = document.getElementById('camp-requests-list');

    async function fetchRequests() {
        try {
            const res = await fetch('api/get_camp_requests.php');
            const requests = await res.json();
            listContainer.innerHTML = '';

            if (requests.length === 0) {
                listContainer.innerHTML = '<p class="text-gray-500 text-center py-4">Aucune demande.</p>';
                return;
            }

            requests.forEach(camp => {
                // SÉCURITÉ : Lien uniquement par Token
                const previewLink = `camp_details?t=${camp.token}`;

                listContainer.innerHTML += `
                    <div class="bg-white p-5 rounded-lg border shadow-sm mb-4" id="camp-card-${camp.id}">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-bold text-lg">${camp.nom}</p>
                                <p class="text-sm text-gray-600">${camp.ville} (${camp.code_postal})</p>
                                <p class="text-xs text-gray-400">Orga: ${camp.organisateur_nom}</p>
                            </div>
                            <div class="flex gap-2">
                                <a href="${previewLink}" target="_blank" class="bg-gray-100 text-gray-700 px-3 py-2 rounded text-sm font-bold">Prévisualiser</a>
                                <button class="action-btn bg-green-600 text-white px-3 py-2 rounded text-sm font-bold" data-action="approve" data-id="${camp.id}">Approuver</button>
                                <button class="action-btn bg-red-600 text-white px-3 py-2 rounded text-sm font-bold" data-action="deny" data-id="${camp.id}">Refuser</button>
                            </div>
                        </div>
                    </div>`;
            });

            document.querySelectorAll('.action-btn').forEach(btn => btn.addEventListener('click', handleAction));
        } catch (e) { listContainer.innerHTML = `<p class="text-red-500 text-center">${e.message}</p>`; }
    }

    async function handleAction(e) {
        const btn = e.currentTarget;
        const action = btn.dataset.action;
        const campId = btn.dataset.id;
        
        if (action === 'deny' && !confirm('Supprimer ce camp ?')) return;
        
        try {
            await fetch('api/process_camp_request.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ campId, action })
            });
            document.getElementById(`camp-card-${campId}`).remove();
        } catch(e) { alert(e.message); }
    }

    fetchRequests();
});
</script>
</body>
</html>