<?php 
require_once 'api/config.php';
require_once 'partials/header.php'; 

$token = $_GET['t'] ?? '';
$isValid = false;
$errorMsg = "";

// Vérification initiale du token PHP (pour ne pas afficher le form si expiré)
if ($token) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    if ($stmt->fetch()) {
        $isValid = true;
    } else {
        $errorMsg = "Ce lien est invalide ou a expiré.";
    }
} else {
    $errorMsg = "Lien invalide.";
}
?>

<title>Nouveau mot de passe</title>

<main class="container mx-auto px-4 py-16 flex justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white shadow-lg rounded-xl px-8 pt-6 pb-8 mb-4">
            
            <?php if (!$isValid): ?>
                <div class="text-center">
                    <div class="mb-4 text-red-500 font-bold text-xl">⚠️ Oups !</div>
                    <p class="text-gray-600 mb-6"><?= $errorMsg ?></p>
                    <a href="forgot_password.php" class="text-blue-600 hover:underline font-bold">Faire une nouvelle demande</a>
                </div>
            <?php else: ?>

                <form id="reset-form">
                    <h1 class="text-2xl font-bold text-center text-[#0A112F] mb-6">Nouveau mot de passe</h1>
                    <div id="message-area" class="mb-4 text-center"></div>
                    <input type="hidden" id="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nouveau mot de passe</label>
                        <input class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 focus:ring-2 focus:ring-blue-500" id="password" type="password" required>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Confirmer</label>
                        <input class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 focus:ring-2 focus:ring-blue-500" id="confirm" type="password" required>
                    </div>

                    <button class="bg-[#0A112F] hover:bg-blue-900 text-white font-bold py-3 px-6 rounded-lg w-full transition duration-300" type="submit">
                        Modifier le mot de passe
                    </button>
                </form>

                <script>
                document.getElementById('reset-form').addEventListener('submit', async function(event) {
                    event.preventDefault();
                    const p1 = document.getElementById('password').value;
                    const p2 = document.getElementById('confirm').value;
                    const token = document.getElementById('token').value;
                    const messageArea = document.getElementById('message-area');

                    if(p1 !== p2) {
                        messageArea.innerHTML = '<p class="text-red-500 font-bold">Les mots de passe ne correspondent pas.</p>';
                        return;
                    }

                    messageArea.innerHTML = '<p class="text-blue-500">Mise à jour...</p>';

                    try {
                        const response = await fetch('api/process_password_reset.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ token, password: p1 })
                        });
                        const result = await response.json();

                        if (result.success) {
                            messageArea.innerHTML = `<p class="text-green-500 font-bold">${result.success}</p>`;
                            setTimeout(() => { window.location.href = 'login.php'; }, 2000);
                        } else {
                            messageArea.innerHTML = `<p class="text-red-500 font-bold">${result.error}</p>`;
                        }
                    } catch (error) {
                        messageArea.innerHTML = `<p class="text-red-500 font-bold">Erreur technique.</p>`;
                    }
                });
                </script>

            <?php endif; ?>
        </div>
    </div>
</main>
<?php require_once 'partials/footer.php'; ?>