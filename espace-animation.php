<?php
// Fichier: /espace-animation.php
require_once 'partials/header.php';

// Sécurité : l'utilisateur doit être connecté et être un animateur.
if (!isset($_SESSION['user']) || !($_SESSION['user']['is_animateur'] ?? false)) {
    header('Location: login.php');
    exit;
}
?>

<title>Espace Animation - ColoMap</title>

<div id="status-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-xl p-8 max-w-sm w-full text-center transform transition-all">
        <div id="modal-icon-container" class="mx-auto flex items-center justify-center h-16 w-16 rounded-full mb-5">
            </div>
        <h2 id="modal-title" class="text-2xl font-bold mb-2 text-gray-900"></h2>
        <p class="text-gray-600 mb-6">Voici l'état actuel de votre candidature pour le camp :</p>
        <p id="modal-camp-name" class="font-bold text-lg text-gray-800 bg-gray-100 p-3 rounded-lg mb-6"></p>
        <button id="close-modal-btn" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700">
            Fermer
        </button>
    </div>
</div>

<main class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <div class="text-center mb-12">
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight text-gray-900">
            Mon <span class="bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 bg-clip-text text-transparent">Espace Animation</span>
        </h1>
        <p class="mt-4 max-w-2xl mx-auto text-lg text-gray-500">
            Suivez l'avancement de vos candidatures et retrouvez les informations de vos prochains camps.
        </p>
    </div>

    <div id="loader" class="text-center py-10">
        <div class="loader inline-block"></div>
        <p class="mt-4 text-gray-600">Chargement de vos candidatures...</p>
    </div>

    <div id="content" class="hidden space-y-12">
        <div>
            <h2 class="text-2xl font-bold mb-6">Mes candidatures en attente</h2>
            <div id="pending-applications-list" class="space-y-6"></div>
        </div>

        <div>
            <h2 class="text-2xl font-bold mb-6">Mes camps acceptés</h2>
            <div id="accepted-applications-list" class="space-y-6"></div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    // Éléments du DOM
    const loader = document.getElementById('loader');
    const content = document.getElementById('content');
    const pendingList = document.getElementById('pending-applications-list');
    const acceptedList = document.getElementById('accepted-applications-list');
    const modal = document.getElementById('status-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');

    try {
        // Appel à la nouvelle API
        const response = await fetch('api/get_my_applications.php');
        if (!response.ok) {
            throw new Error('Erreur lors de la récupération de vos candidatures.');
        }
        const data = await response.json();

        loader.classList.add('hidden');
        content.classList.remove('hidden');

        // Affichage des candidatures en attente
        if (data.pending.length === 0) {
            pendingList.innerHTML = `<div class="text-center py-10 bg-white rounded-lg shadow-md border"><p class="text-gray-500">Vous n'avez aucune candidature en attente.</p></div>`;
        } else {
            data.pending.forEach(app => {
                pendingList.innerHTML += createCampCard(app);
            });
        }

        // Affichage des camps acceptés
        if (data.accepted.length === 0) {
            acceptedList.innerHTML = `<div class="text-center py-10 bg-white rounded-lg shadow-md border"><p class="text-gray-500">Vous n'avez pas encore été accepté à un camp.</p></div>`;
        } else {
            data.accepted.forEach(app => {
                acceptedList.innerHTML += createCampCard(app);
            });
        }
        
        // Ajout des écouteurs pour les boutons "Voir plus"
        document.querySelectorAll('.view-status-btn').forEach(button => {
            button.addEventListener('click', showStatusModal);
        });

    } catch (error) {
        loader.innerHTML = `<p class="text-red-500 font-bold text-center py-10">${error.message}</p>`;
    }

    // Fonction pour créer une carte de candidature
    function createCampCard(app) {
        const isAccepted = app.statut === 'Accepté';
        
        const statusInfo = isAccepted
            ? `<p class="text-sm font-semibold text-green-600">Statut : Accepté</p>`
            : `<p class="text-sm font-semibold text-yellow-600">Statut : En attente</p>`;

        return `
            <div class="bg-white rounded-xl shadow-lg border p-4 sm:p-6 flex flex-col sm:flex-row items-start gap-6">
                <img src="${app.camp_image_url}" alt="Image du camp" class="w-full sm:w-40 h-40 object-cover rounded-lg">
                <div class="flex-grow">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">${app.camp_nom}</h3>
                            <p class="text-sm text-gray-500">📍 ${app.camp_ville}</p>
                        </div>
                        ${statusInfo}
                    </div>
                    <div class="mt-4 border-t pt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="font-semibold text-gray-700">Infos Organisateur</p>
                            <p>${app.organisateur_nom}</p>
                            <p class="text-blue-600 hover:underline"><a href="mailto:${app.organisateur_mail}">${app.organisateur_mail}</a></p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-700">État des inscriptions</p>
                            <p>Jeunes inscrits : ${app.inscrits_enfants}</p>
                            <p>Animateurs inscrits : ${app.inscrits_animateurs}</p>
                        </div>
                    </div>
                    <div class="text-right mt-4">
                        <button class="view-status-btn text-xs bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-3 py-2 rounded-md" data-camp-name="${app.camp_nom}" data-status="${app.statut}">
                            Voir plus
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // Fonctions pour gérer la modale
    function showStatusModal(event) {
        const campName = event.currentTarget.dataset.campName;
        const status = event.currentTarget.dataset.status;
        
        const modalIconContainer = document.getElementById('modal-icon-container');
        const modalTitle = document.getElementById('modal-title');
        const modalCampName = document.getElementById('modal-camp-name');

        modalCampName.textContent = campName;

        if (status === 'Accepté') {
            modalIconContainer.className = 'mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-5';
            modalIconContainer.innerHTML = `<svg class="h-10 w-10 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>`;
            modalTitle.textContent = "Candidature Acceptée !";
        } else {
            modalIconContainer.className = 'mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 mb-5';
            modalIconContainer.innerHTML = `<svg class="h-10 w-10 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>`;
            modalTitle.textContent = "Candidature en Attente";
        }
        
        modal.classList.remove('hidden');
    }

    function hideModal() {
        modal.classList.add('hidden');
    }

    closeModalBtn.addEventListener('click', hideModal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            hideModal();
        }
    });
});
</script>
</body>
</html>