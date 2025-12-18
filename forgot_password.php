<?php require_once 'partials/header.php'; ?>
<title>Mot de passe oublié - ColoMap</title>

<main class="container mx-auto px-4 py-16 flex justify-center">
    <div class="w-full max-w-md">
        <form id="forgot-form" class="bg-white shadow-lg rounded-xl px-8 pt-6 pb-8 mb-4">
            <h1 class="text-2xl font-bold text-center text-[#0A112F] mb-2">Mot de passe oublié ?</h1>
            <p class="text-gray-500 text-center text-sm mb-6">Entrez votre e-mail pour recevoir un lien de réinitialisation.</p>

            <div id="message-area" class="mb-4 text-center"></div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="mail">Adresse Email</label>
                <input class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" id="mail" type="email" placeholder="exemple@mail.com" required>
            </div>

            <div class="flex items-center justify-between">
                <button class="bg-[#0A112F] hover:bg-blue-900 text-white font-bold py-3 px-6 rounded-lg w-full transition duration-300" type="submit">
                    Envoyer le lien
                </button>
            </div>
            
            <p class="text-center text-gray-500 text-sm mt-6">
                <a class="font-bold text-blue-600 hover:text-blue-800" href="login.php">Retour à la connexion</a>
            </p>
        </form>
    </div>
</main>

<script>
document.getElementById('forgot-form').addEventListener('submit', async function(event) {
    event.preventDefault();
    const mail = document.getElementById('mail').value;
    const messageArea = document.getElementById('message-area');
    messageArea.innerHTML = '<p class="text-blue-500">Envoi en cours...</p>';

    try {
        const response = await fetch('api/request_password_reset.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mail })
        });
        const result = await response.json();

        if (result.success) {
            messageArea.innerHTML = `<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded text-sm">${result.success}</div>`;
            document.getElementById('forgot-form').reset();
        } else {
            messageArea.innerHTML = `<p class="text-red-500 font-bold">${result.error}</p>`;
        }
    } catch (error) {
        messageArea.innerHTML = `<p class="text-red-500 font-bold">Une erreur est survenue.</p>`;
    }
});
</script>
<?php require_once 'partials/footer.php'; ?>