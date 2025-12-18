<?php
require_once 'partials/header.php';

if (!isset($_SESSION['user'])) {
    header('Location: login');
    exit;
}
?>

<title>Mes Enfants - ColoMap</title>

<main class="container mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <a href="profile" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>
            Retour à l'espace personnel
        </a>
    </div>

    <div class="bg-white p-8 rounded-xl shadow-lg border">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Gérer mes enfants</h1>
            <a href="add_child" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700">
                Ajouter un enfant
            </a>
        </div>
        
        <div id="children-list" class="space-y-4">
            <!-- La liste des enfants sera chargée ici -->
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const listContainer = document.getElementById('children-list');

    async function fetchChildren() {
        listContainer.innerHTML = '<p class="text-center text-gray-500 py-4">Chargement...</p>';
        try {
            const response = await fetch('api/get_children.php');
            if (!response.ok) throw new Error('Erreur réseau');
            const children = await response.json();
            
            listContainer.innerHTML = '';
            if(children.length === 0) {
                listContainer.innerHTML = '<p class="text-center text-gray-500 py-4">Vous n\'avez pas encore enregistré d\'enfant.</p>';
                return;
            }

            children.forEach(child => {
                const card = `
                    <div class="bg-gray-50 p-4 rounded-lg border flex justify-between items-center">
                        <div>
                            <p class="font-semibold text-gray-800">${child.prenom}</p>
                            <p class="text-sm text-gray-600">${child.age !== null ? child.age + ' ans' : 'Âge non calculable'}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <a href="#" class="text-yellow-600 hover:underline text-sm font-medium">Modifier</a>
                            <button class="text-red-600 hover:underline text-sm font-medium">Supprimer</button>
                        </div>
                    </div>
                `;
                listContainer.innerHTML += card;
            });
        } catch (error) {
            listContainer.innerHTML = `<p class="text-red-500 font-bold text-center py-4">${error.message}</p>`;
        }
    }

    fetchChildren();
});
</script>

</body>
</html>
