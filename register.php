<?php require_once 'partials/header.php'; ?>
<title>Inscription - ColoMap</title>

<main class="container mx-auto px-4 py-16 flex justify-center">
    <div class="w-full max-w-lg">
        <form id="register-form" class="bg-white shadow-lg rounded-xl px-8 pt-6 pb-8 mb-4">
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Créer un compte</h1>
            
            <div id="message-area" class="mb-4 text-center"></div>

            <div class="flex flex-wrap -mx-3 mb-4">
                <div class="w-full md:w-1/2 px-3 mb-4 md:mb-0">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="prenom">Prénom</label>
                    <input class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700" id="prenom" type="text" required>
                </div>
                <div class="w-full md:w-1/2 px-3">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="nom">Nom</label>
                    <input class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700" id="nom" type="text" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="mail">Email</label>
                <input class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700" id="mail" type="email" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="naissance">Date de naissance</label>
                <input class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700" id="naissance" type="date" required>
            </div>
             <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="sexe">Sexe</label>
                <select id="sexe" class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700">
                    <option>Homme</option>
                    <option>Femme</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Mot de passe</label>
                <input class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700" id="password" type="password" required>
            </div>
            
            <div class="mb-6 border-t pt-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Je m'inscris en tant que :</label>
                <div class="mt-2 flex justify-around">
                    <label class="flex items-center">
                        <input type="radio" name="role" value="parent" class="h-4 w-4 text-blue-600" checked>
                        <span class="ml-2 text-gray-800">Parent</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="role" value="animateur" class="h-4 w-4 text-blue-600">
                        <span class="ml-2 text-gray-800">Animateur</span>
                    </label>
                </div>
                 <div class="mt-4">
                     <label class="flex items-center">
                        <input type="checkbox" id="bafa" class="h-4 w-4 text-blue-600">
                        <span class="ml-2 text-sm text-gray-800">Je possède le BAFA</span>
                    </label>
                </div>
            </div>
            
            <div class="flex items-center justify-between">
                <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg w-full" type="submit">
                    S'inscrire
                </button>
            </div>
        </form>
    </div>
</main>

<script>
document.getElementById('register-form').addEventListener('submit', async function(event) {
    event.preventDefault();
    const messageArea = document.getElementById('message-area');
    messageArea.innerHTML = '<p class="text-blue-500">Création du compte...</p>';

    const formData = {
        nom: document.getElementById('nom').value,
        prenom: document.getElementById('prenom').value,
        mail: document.getElementById('mail').value,
        naissance: document.getElementById('naissance').value,
        sexe: document.getElementById('sexe').value,
        password: document.getElementById('password').value,
        role: document.querySelector('input[name="role"]:checked').value,
        bafa: document.getElementById('bafa').checked
    };

    try {
        const response = await fetch('api/user_register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Affichage du succès
            messageArea.innerHTML = `<p class="text-green-500 font-bold">${result.success}</p>`;
            
            // Redirection vers validation.php avec le token URL reçu
            setTimeout(() => { 
                if(result.redirect_token) {
                    window.location.href = 'validation.php?t=' + result.redirect_token; 
                } else {
                    // Fallback au cas où (ne devrait pas arriver si l'API est à jour)
                    window.location.href = 'login.php';
                }
            }, 1500);
            
        } else {
            // Affichage de l'erreur
            messageArea.innerHTML = `<p class="text-red-500 font-bold">${result.error}</p>`;
        }
    } catch (error) {
        console.error(error);
        messageArea.innerHTML = `<p class="text-red-500 font-bold">Une erreur est survenue.</p>`;
    }
});
</script>
</body>
</html>