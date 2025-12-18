<?php
// Fichier: /inscription-animateur.php (Corrigé)
require_once 'partials/header.php';

if (!isset($_SESSION['user']) || !($_SESSION['user']['is_animateur'] ?? false)) {
    header('Location: login.php');
    exit;
}
$camp_id = $_GET['id'] ?? '';
if (empty($camp_id)) {
    header('Location: animateur.php');
    exit;
}

$user_prenom = $_SESSION['user']['prenom'] ?? 'N/A';
$user_nom = $_SESSION['user']['nom'] ?? 'N/A';
$user_mail = $_SESSION['user']['mail'] ?? 'N/A';
$user_tel = $_SESSION['user']['tel'] ?? 'Non renseigné'; // CORRECTION : Utilise la variable de session mise à jour
?>

<title>Inscription Animateur - ColoMap</title>

<main class="container mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 py-12">
    <div id="loader" class="text-center py-20"><div class="loader inline-block"></div></div>

    <div id="form-container" class="hidden">
        <div class="mb-8">
            <a href="info-camp-animateur.php?id=<?php echo htmlspecialchars($camp_id); ?>" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>
                Retour
            </a>
        </div>
        
        <div class="bg-white p-8 rounded-xl shadow-lg border">
            <div class="text-center mb-8">
                <h1 id="camp-title" class="text-3xl font-bold text-gray-900"></h1>
                <p class="text-gray-600">Postuler en tant qu'animateur</p>
            </div>

            <form id="inscription-anim-form" class="space-y-8">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4">Vos Informations</h2>
                    <div class="bg-gray-50 p-4 rounded-lg space-y-2 text-sm">
                        <p><strong>Nom :</strong> <?php echo htmlspecialchars($user_prenom . ' ' . $user_nom); ?></p>
                        <p><strong>Email :</strong> <?php echo htmlspecialchars($user_mail); ?></p>
                        <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($user_tel); ?></p>
                    </div>
                </div>
                <div>
                    <label for="motivation" class="block text-lg font-semibold text-gray-700">Vos motivations</label>
                    <textarea id="motivation" rows="6" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-3" placeholder="Parlez de votre expérience..."></textarea>
                </div>
                
                <div class="pt-4 text-right">
                    <button type="submit" class="bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700">
                        Envoyer ma candidature
                    </button>
                </div>
                <div id="form-message" class="text-center mt-4 text-sm font-medium"></div>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const loader = document.getElementById('loader');
    const formContainer = document.getElementById('form-container');
    const campTitle = document.getElementById('camp-title');
    const form = document.getElementById('inscription-anim-form');
    const formMessage = document.getElementById('form-message');
    const campId = '<?php echo $camp_id; ?>';

    try {
        const response = await fetch(`api/get_camp_details.php?id=${campId}`);
        if (!response.ok) throw new Error('Camp non trouvé.');
        const camp = await response.json();
        campTitle.textContent = camp.nom;
        loader.classList.add('hidden');
        formContainer.classList.remove('hidden');
    } catch (error) {
        loader.innerHTML = `<p class="text-red-500 font-bold">${error.message}</p>`;
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        formMessage.innerHTML = '<p class="text-blue-500">Envoi en cours...</p>';
        
        const formData = {
            campId: campId,
            motivation: document.getElementById('motivation').value
        };

        try {
            // CORRECTION : Appel au bon script API
            const response = await fetch('api/process_animator_application.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Une erreur est survenue.');
            
            formMessage.innerHTML = `<p class="text-green-600 font-bold">Candidature envoyée !</p>`;
            form.reset();
            
        } catch (error) {
            formMessage.innerHTML = `<p class="text-red-500 font-bold">${error.message}</p>`;
            submitButton.disabled = false;
        }
    });
});
</script>
</body>
</html>