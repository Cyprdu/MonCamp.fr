<?php
require_once 'partials/header.php';
if (!isset($_SESSION['user']) || !$_SESSION['user']['is_directeur']) {
    header('Location: index.php');
    exit;
}
?>
<title>Debug - Création de Tarif</title>

<main class="container mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white p-8 rounded-xl shadow-lg border space-y-6">
        <h1 class="text-2xl font-bold text-center">Page de Débogage : Création de Tarif</h1>
        
        <div class="space-y-4 border p-4 rounded-lg">
            <div>
                <label for="debug-org-select" class="block text-sm font-medium text-gray-700">1. Sélectionnez un Organisme</label>
                <select id="debug-org-select" class="bg-gray-50 mt-1 block w-full rounded-md border-gray-400 p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Chargement...</option>
                </select>
            </div>
            <div>
                <label for="debug-tarif-name" class="block text-sm font-medium text-gray-700">2. Entrez un nom de tarif</label>
                <input type="text" id="debug-tarif-name" value="Tarif Test" class="bg-gray-50 mt-1 block w-full rounded-md border-gray-400 p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="debug-tarif-price" class="block text-sm font-medium text-gray-700">3. Entrez un prix</label>
                <input type="number" id="debug-tarif-price" value="99" class="bg-gray-50 mt-1 block w-full rounded-md border-gray-400 p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="flex items-center cursor-pointer"><input type="checkbox" id="debug-montant-libre" class="h-4 w-4"><span class="ml-2 text-sm">Montant libre</span></label>
            </div>
            <button id="debug-test-btn" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700">Lancer le Test de Création</button>
        </div>

        <div class="space-y-2">
            <h2 class="text-lg font-semibold">Résultat :</h2>
            <pre id="debug-output" class="bg-gray-800 text-white text-sm p-4 rounded-lg overflow-x-auto">En attente du test...</pre>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const orgSelect = document.getElementById('debug-org-select');
    const testBtn = document.getElementById('debug-test-btn');
    const outputEl = document.getElementById('debug-output');

    // 1. Charger les organismes
    (async () => {
        try {
            const response = await fetch('api/get_organisateurs.php');
            const data = await response.json();
            orgSelect.innerHTML = '<option value="">-- Choisissez un organisme --</option>';
            data.forEach(org => {
                orgSelect.innerHTML += `<option value="${org.id}">${org.nom}</option>`;
            });
        } catch (e) {
            orgSelect.innerHTML = '<option value="">Erreur de chargement</option>';
        }
    })();

    // 2. Lancer le test au clic
    testBtn.addEventListener('click', async () => {
        const payload = {
            nom: document.getElementById('debug-tarif-name').value,
            prix: parseFloat(document.getElementById('debug-tarif-price').value),
            organisateur_id: orgSelect.value,
            montant_libre: document.getElementById('debug-montant-libre').checked
        };

        outputEl.textContent = 'Envoi des données suivantes à l\'API...\n\n';
        outputEl.textContent += JSON.stringify(payload, null, 2);

        try {
            const response = await fetch('api/create_tarif.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            outputEl.textContent += '\n\n-----------------------------------\n';
            outputEl.textContent += 'Réponse du serveur reçue.\n\n';
            outputEl.textContent += 'Code Statut HTTP: ' + response.status + '\n';
            
            const responseData = await response.json();
            outputEl.textContent += 'Données de la réponse (JSON) :\n\n';
            outputEl.textContent += JSON.stringify(responseData, null, 2);

        } catch (error) {
            outputEl.textContent += '\n\n-----------------------------------\n';
            outputEl.textContent += 'ERREUR DE COMMUNICATION FATALE :\n\n' + error;
        }
    });
});
</script>
</body>
</html>