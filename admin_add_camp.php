<?php
require_once 'partials/header.php';

// SÉCURITÉ : On vérifie que l'utilisateur est connecté ET qu'il est admin.
if (!isset($_SESSION['user']) || !($_SESSION['user']['is_admin'] ?? false)) {
    header('Location: index.php');
    exit;
}
?>

<title>Ajouter un Camp - Admin</title>

<main class="container mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12">

    <div class="mb-8">
        <a href="admin" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>
            Retour au panneau d'administration
        </a>
    </div>

    <div class="bg-white p-8 rounded-xl shadow-lg border">
        <h1 class="text-2xl font-bold mb-2 text-gray-900">Ajouter un nouveau camp manuellement</h1>
        <p class="text-sm text-gray-500 mb-6">Note : Le camp sera assigné à votre propre compte directeur. Une future version permettra de choisir un autre organisateur.</p>
        
        <form id="add-camp-form" class="space-y-6">
            <div>
                <label for="nom" class="block text-sm font-medium text-gray-700">Nom du camp</label>
                <input type="text" id="nom" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea id="description" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="ville" class="block text-sm font-medium text-gray-700">Ville</label>
                    <input type="text" id="ville" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="code_postal" class="block text-sm font-medium text-gray-700">Code Postal</label>
                    <input type="text" id="code_postal" required pattern="[0-9]{5}" title="Le code postal doit contenir 5 chiffres." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
             <div>
                <label for="adresse" class="block text-sm font-medium text-gray-700">Adresse exacte</label>
                <input type="text" id="adresse" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="prix" class="block text-sm font-medium text-gray-700">Prix (€)</label>
                    <input type="number" id="prix" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="age_min" class="block text-sm font-medium text-gray-700">Âge minimum</label>
                    <input type="number" id="age_min" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="age_max" class="block text-sm font-medium text-gray-700">Âge maximum</label>
                    <input type="number" id="age_max" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="date_debut" class="block text-sm font-medium text-gray-700">Date de début</label>
                    <input type="date" id="date_debut" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="date_fin" class="block text-sm font-medium text-gray-700">Date de fin</label>
                    <input type="date" id="date_fin" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
            <div>
                <label for="image_url" class="block text-sm font-medium text-gray-700">URL de l'image d'illustration</label>
                <input type="url" id="image_url" placeholder="https://..." required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div id="form-message" class="text-center mt-4 text-sm font-medium"></div>
            <div class="pt-4 text-right">
                <button type="submit" class="bg-green-600 text-white font-bold py-3 px-6 rounded-lg transition-all duration-300 hover:bg-green-700">
                    Ajouter le camp
                </button>
            </div>
        </form>
    </div>
</main>

<script>
document.getElementById('add-camp-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formMessage = document.getElementById('form-message');
    const submitButton = this.querySelector('button[type="submit"]');

    const campData = {
        nom: document.getElementById('nom').value,
        description: document.getElementById('description').value,
        ville: document.getElementById('ville').value,
        code_postal: document.getElementById('code_postal').value,
        adresse: document.getElementById('adresse').value,
        prix: document.getElementById('prix').value,
        age_min: document.getElementById('age_min').value,
        age_max: document.getElementById('age_max').value,
        date_debut: document.getElementById('date_debut').value,
        date_fin: document.getElementById('date_fin').value,
        image_url: document.getElementById('image_url').value
    };

    formMessage.innerHTML = '<p class="text-blue-500">Ajout du camp en cours...</p>';
    submitButton.disabled = true;
    submitButton.classList.add('opacity-50', 'cursor-not-allowed');

    try {
        const response = await fetch('api/add_camp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(campData)
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.error || 'Une erreur inconnue est survenue.');
        }

        formMessage.innerHTML = `<p class="text-green-600">${result.success}</p>`;
        this.reset();
        submitButton.disabled = false;
        submitButton.classList.remove('opacity-50', 'cursor-not-allowed');

    } catch (error) {
        formMessage.innerHTML = `<p class="text-red-600">${error.message}</p>`;
        submitButton.disabled = false;
        submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
    }
});
</script>

</body>
</html>
