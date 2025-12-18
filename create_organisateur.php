<?php
require_once 'partials/header.php';

// Sécurité
if (!isset($_SESSION['user']) || !$_SESSION['user']['is_directeur']) {
    header('Location: index.php');
    exit;
}
?>

<title>Créer un Organisme - ColoMap</title>

<main class="container mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <a href="public_infos.php" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>
            Retour
        </a>
    </div>

    <div class="bg-white p-8 rounded-xl shadow-lg border">
        <h1 class="text-2xl font-bold mb-6 text-gray-900">Créer un nouvel organisme</h1>
        <form id="create-org-form" class="space-y-6">
            <div>
                <label for="nom_organisme" class="block text-sm font-medium text-gray-700">Nom de l'organisme</label>
                <input type="text" id="nom_organisme" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="tel" class="block text-sm font-medium text-gray-700">Téléphone</label>
                    <input type="tel" id="tel" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                </div>
                <div>
                    <label for="mail" class="block text-sm font-medium text-gray-700">Email de contact</label>
                    <input type="email" id="mail" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                </div>
            </div>
            <div>
                <label for="web" class="block text-sm font-medium text-gray-700">Site Web (optionnel)</label>
                <input type="url" id="web" placeholder="https://..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
            </div>
            <div id="form-message" class="text-center mt-4 text-sm font-medium"></div>
            <div class="pt-4 text-right">
                <button type="submit" class="bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700">
                    Enregistrer l'organisme
                </button>
            </div>
        </form>
    </div>
</main>

<script>
document.getElementById('create-org-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formMessage = document.getElementById('form-message');
    formMessage.textContent = 'Enregistrement en cours...';

    const formData = {
        nom: document.getElementById('nom_organisme').value,
        tel: document.getElementById('tel').value,
        mail: document.getElementById('mail').value,
        web: document.getElementById('web').value
    };

    try {
        const response = await fetch('api/create_organisateur.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error);
        
        formMessage.innerHTML = `<p class="text-green-600">${result.success}</p>`;
        setTimeout(() => window.location.href = 'public_infos.php', 1500);

    } catch (error) {
        formMessage.innerHTML = `<p class="text-red-600">${error.message}</p>`;
    }
});
</script>
</body>
</html>
