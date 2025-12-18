<?php
require_once 'partials/header.php';

// SÉCURITÉ : On vérifie que l'utilisateur est connecté ET qu'il est admin.
if (!isset($_SESSION['user']) || !($_SESSION['user']['is_admin'] ?? false)) {
    header('Location: index');
    exit;
}
?>

<title>Demandes d'accès - Admin</title>

<!-- Modale pour afficher les détails de l'utilisateur -->
<div id="user-details-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-lg flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-lg w-full relative m-4 transform transition-all" id="user-modal-content">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Détails du demandeur</h2>
        <div id="user-details-content" class="space-y-2 text-sm"></div>
        <button id="close-modal-button" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <div id="copy-feedback" class="absolute bottom-4 right-4 bg-green-500 text-white text-xs font-bold py-1 px-3 rounded-full transition-opacity opacity-0">Copié !</div>
    </div>
</div>

<main class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <a href="admin" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>
            Retour au panneau d'administration
        </a>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-lg border">
        <h1 class="text-2xl font-bold mb-4">Demandes d'accès Directeur en attente</h1>
        <div id="requests-list" class="space-y-4"></div>
    </div>
</main>

<script>
// Le JavaScript de l'ancienne page admin.php est transféré ici
document.addEventListener('DOMContentLoaded', function() {
    const requestsList = document.getElementById('requests-list');
    const modal = document.getElementById('user-details-modal');
    const modalContent = document.getElementById('user-details-content');
    const closeModalButton = document.getElementById('close-modal-button');
    const copyFeedback = document.getElementById('copy-feedback');

    let allRequestsData = [];

    async function fetchRequests() {
        requestsList.innerHTML = '<p class="text-gray-500 text-center py-4">Chargement des demandes...</p>';
        try {
            const response = await fetch('api/get_director_requests.php');
            if (!response.ok) throw new Error('Erreur réseau.');
            allRequestsData = await response.json();
            renderRequests(allRequestsData);
        } catch (error) {
            requestsList.innerHTML = `<p class="text-red-600 font-bold text-center py-4">${error.message}</p>`;
        }
    }
    
    function renderRequests(requests) {
        requestsList.innerHTML = '';
        if (requests.length === 0) {
            requestsList.innerHTML = '<p class="text-gray-500 text-center py-4">Aucune demande en attente.</p>';
            return;
        }
        requests.forEach(user => {
            const requestCard = document.createElement('div');
            requestCard.className = 'request-card bg-gray-50 p-4 rounded-lg border';
            requestCard.id = `user-${user.id}`;
            requestCard.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
                    <div class="md:col-span-2">
                        <p class="font-bold text-lg">${user.prenom} ${user.nom}</p>
                        <p class="text-sm text-gray-600">${user.mail}</p>
                    </div>
                    <div class="flex items-center justify-end gap-3 flex-wrap">
                        <button class="view-details-button bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-3 rounded-lg text-sm" data-userid="${user.id}">Voir infos</button>
                        <button class="process-button bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-3 rounded-lg" data-action="accept" data-userid="${user.id}">Accepter</button>
                        <button class="process-button bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-3 rounded-lg" data-action="refuse" data-userid="${user.id}">Refuser</button>
                    </div>
                </div>`;
            requestsList.appendChild(requestCard);
        });
        addEventListeners();
    }

    function addEventListeners() {
        document.querySelectorAll('.process-button').forEach(btn => btn.addEventListener('click', handleProcessClick));
        document.querySelectorAll('.view-details-button').forEach(btn => btn.addEventListener('click', handleViewDetailsClick));
    }

    function handleViewDetailsClick(event) {
        const userId = event.currentTarget.dataset.userid;
        const user = allRequestsData.find(u => u.id === userId);
        if (!user) return;
        modalContent.innerHTML = `
            ${createDetailRow('ID Utilisateur', user.id)}
            ${createDetailRow('Prénom', user.prenom)}
            ${createDetailRow('Nom', user.nom)}
            ${createDetailRow('Email', user.mail)}
            ${createDetailRow('Téléphone', user.tel || 'Non fourni')}
        `;
        modal.classList.remove('hidden');
        document.querySelectorAll('.copy-button').forEach(btn => btn.addEventListener('click', handleCopyClick));
    }
    
    function createDetailRow(label, value) {
        const id = `copy-${label.replace(/\s+/g, '')}`;
        return `
            <div class="flex justify-between items-center bg-gray-100 p-2 rounded-md">
                <span class="font-semibold text-gray-700">${label} :</span>
                <div class="flex items-center gap-3">
                    <span id="${id}" class="text-gray-900 font-mono">${value}</span>
                    <button class="copy-button text-gray-400 hover:text-blue-600" data-target="${id}" title="Copier">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </button>
                </div>
            </div>`;
    }

    function handleCopyClick(event) {
        const targetId = event.currentTarget.dataset.target;
        const textToCopy = document.getElementById(targetId).textContent;
        const textArea = document.createElement("textarea");
        textArea.value = textToCopy;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            copyFeedback.classList.remove('opacity-0');
            setTimeout(() => copyFeedback.classList.add('opacity-0'), 1500);
        } catch (err) {
            console.error('Erreur de copie:', err);
        }
        document.body.removeChild(textArea);
    }

    async function handleProcessClick(event) {
        const button = event.currentTarget;
        const action = button.dataset.action;
        const userId = button.dataset.userid;
        button.parentElement.querySelectorAll('button').forEach(btn => btn.disabled = true);
        button.textContent = '...';
        try {
            const response = await fetch('api/process_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ userId, action })
            });
            if (!response.ok) throw new Error((await response.json()).error || 'Erreur.');
            const cardToRemove = document.getElementById(`user-${userId}`);
            if (cardToRemove) cardToRemove.remove();
        } catch (error) {
            alert(`Erreur : ${error.message}`);
            button.parentElement.querySelectorAll('button').forEach(btn => btn.disabled = false);
            button.textContent = action === 'accept' ? 'Accepter' : 'Refuser';
        }
    }

    closeModalButton.addEventListener('click', () => modal.classList.add('hidden'));
    fetchRequests();
});
</script>

</body>
</html>
