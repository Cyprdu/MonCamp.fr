<?php
require_once 'partials/header.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
?>

<title>Mes Réservations - ColoMap</title>

<main class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <div class="text-center mb-12">
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight text-gray-900">
            Mes <span class="bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 bg-clip-text text-transparent">Réservations</span>
        </h1>
        <p class="mt-4 max-w-2xl mx-auto text-lg text-gray-500">
            Retrouvez ici un récapitulatif de toutes les inscriptions de vos enfants.
        </p>
    </div>

    <div id="loader" class="text-center py-10">
        <div class="loader inline-block"></div>
        <p class="mt-4 text-gray-600">Chargement de vos réservations...</p>
    </div>
    
    <div id="reservations-list" class="hidden space-y-6 max-w-4xl mx-auto">
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const listContainer = document.getElementById('reservations-list');
    const loader = document.getElementById('loader');

    try {
        // SOLUTION FINALE : FILTRAGE CÔTÉ CLIENT

        // 1. On récupère la liste complète (et incorrectement filtrée) des réservations depuis l'API.
        // On utilise le fichier API final qui est censé fonctionner.
        const allReservationsResponse = await fetch('api/get_my_reservations.php?t=' + new Date().getTime());
        if (!allReservationsResponse.ok) throw new Error('Erreur API principale.');
        const allReservations = await allReservationsResponse.json();

        // 2. On récupère la liste des enfants qui appartiennent VRAIMENT à l'utilisateur.
        const myChildrenResponse = await fetch('api/get_children.php?t=' + new Date().getTime());
        if (!myChildrenResponse.ok) throw new Error('Erreur API des enfants.');
        const myChildren = await myChildrenResponse.json();
        
        // 3. On crée une liste des IDs de nos enfants.
        const myChildrenIds = new Set(myChildren.map(child => child.id));

        // 4. On filtre la liste complète des réservations en ne gardant que celles qui correspondent à nos enfants.
        const myFilteredReservations = allReservations.filter(reservation => 
            myChildrenIds.has(reservation.enfant_id)
        );

        // 5. On affiche le résultat filtré.
        loader.classList.add('hidden');
        listContainer.classList.remove('hidden');

        if (myFilteredReservations.length === 0) {
            listContainer.innerHTML = `<div class="text-center py-10 bg-white rounded-lg shadow-md border"><p class="text-gray-500">Vous n'avez aucune réservation pour le moment.</p></div>`;
            return;
        }
        
        let htmlContent = '';
        myFilteredReservations.forEach(res => {
            const startDate = res.date_debut ? new Date(res.date_debut).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) : 'Date non définie';
            htmlContent += `
                <div class="bg-white rounded-xl shadow-lg border p-4 flex flex-col sm:flex-row items-center gap-6">
                    <img src="${res.camp_image_url}" alt="Image du camp" class="w-full sm:w-32 h-32 object-cover rounded-lg">
                    <div class="flex-grow text-center sm:text-left">
                        <p class="text-xs text-blue-600 font-semibold">INSCRIPTION CONFIRMÉE</p>
                        <h3 class="text-xl font-bold text-gray-800">${res.camp_nom}</h3>
                        <p class="text-gray-600 mt-1">Pour : <strong class="font-medium text-gray-900">${res.enfant_nom}</strong></p>
                        <p class="text-sm text-gray-500">Date de début : ${startDate}</p>
                    </div>
                    <a href="info_inscrit.php?camp_id=${res.camp_id}&child_id=${res.enfant_id}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg text-sm">Voir les détails</a>
                </div>
            `;
        });
        listContainer.innerHTML = htmlContent;

    } catch (error) {
        loader.innerHTML = `<p class="text-red-500 font-bold col-span-full text-center py-10">Erreur: ${error.message}</p>`;
    }
});
</script>

</body>
</html>