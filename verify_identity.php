<?php
// verify_identity.php
// PAGE DÉDIÉE À LA CRÉATION DU COMPTE STRIPE CONNECT + UPLOAD KYC

require_once 'api/config.php';

if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_directeur'])) {
    header('Location: index.php');
    exit;
}

$organisateurId = filter_input(INPUT_GET, 'organisateur_id', FILTER_VALIDATE_INT);
$userId = $_SESSION['user']['id'];
$error = null;

// Vérifs
try {
    $stmt = $pdo->prepare("SELECT * FROM organisateurs WHERE id = ? AND user_id = ?");
    $stmt->execute([$organisateurId, $userId]);
    $organisateur = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$organisateur) die("Organisme introuvable.");
    
    // Si déjà vérifié, pas besoin d'être là
    if (!empty($organisateur['stripe_account_id'])) {
        header("Location: dashboard_organisme.php?organisateur_id=$organisateurId&success=Identité déjà vérifiée.");
        exit;
    }
    
    // Pré-remplissage
    $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $userCurrent = $stmtUser->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Erreur: " . $e->getMessage());
}

require_once 'partials/header.php';
?>
<title>Vérification d'Identité - <?= htmlspecialchars($organisateur['nom']) ?></title>
<script src="https://js.stripe.com/v3/"></script>

<div class="min-h-screen bg-gray-50 py-10 font-sans">
    <div class="max-w-2xl mx-auto px-4">
        
        <div class="mb-6">
            <a href="dashboard_organisme.php?organisateur_id=<?= $organisateurId ?>" class="text-gray-500 hover:text-blue-600 flex items-center">
                &larr; Retour au tableau de bord
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-8 border border-gray-100">
            <div class="border-b pb-4 mb-6">
                <h1 class="text-2xl font-bold text-[#0A112F]">Activation des Virements</h1>
                <p class="text-gray-600 text-sm mt-1">Pour recevoir vos fonds, la réglementation bancaire impose de vérifier l'identité du responsable légal de l'organisme.</p>
            </div>

            <form action="api/save_identity.php" method="POST" id="identityForm" enctype="multipart/form-data">
                <input type="hidden" name="organisateur_id" value="<?= $organisateurId ?>">
                <input type="hidden" name="stripe_account_token" id="stripe_account_token">

                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-3">1. Informations du responsable</h2>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm text-gray-700">Prénom</label>
                            <input type="text" id="user_prenom" value="<?= htmlspecialchars($userCurrent['prenom']) ?>" class="w-full border rounded p-2" required>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700">Nom</label>
                            <input type="text" id="user_nom" value="<?= htmlspecialchars($userCurrent['nom']) ?>" class="w-full border rounded p-2" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm text-gray-700">Date de naissance</label>
                        <input type="date" id="user_dob" class="w-full border rounded p-2" required>
                    </div>
                    
                    <label class="block text-sm text-gray-700 mt-2">Adresse personnelle complète</label>
                    <input type="text" id="user_line1" placeholder="Adresse" class="w-full border rounded p-2 mb-2" required>
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" id="user_postal_code" placeholder="Code Postal" class="w-full border rounded p-2" required>
                        <input type="text" id="user_city" placeholder="Ville" class="w-full border rounded p-2" required>
                    </div>
                </div>

                <div class="mb-8 p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <h2 class="text-lg font-semibold text-blue-900 mb-2">2. Pièce d'identité</h2>
                    <p class="text-xs text-blue-700 mb-3">Carte d'identité (Recto) ou Passeport en cours de validité. Format JPG, PNG ou PDF.</p>
                    <input type="file" name="identity_document" id="identity_document" accept=".jpg,.jpeg,.png,.pdf" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200">
                </div>

                <div id="error-message" class="hidden text-red-600 text-sm font-bold mb-4 text-center"></div>

                <button type="submit" id="submitBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg shadow transition">
                    Valider mon identité et activer les virements
                </button>
            </form>
        </div>
    </div>
</div>

<div id="loader" class="fixed inset-0 bg-gray-900 bg-opacity-75 hidden flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl flex flex-col items-center">
        <svg class="animate-spin h-10 w-10 text-blue-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="font-bold text-gray-800">Traitement sécurisé en cours...</p>
    </div>
</div>

<script>
    const stripe = Stripe('<?= STRIPE_PUBLISHABLE_KEY ?>');
    const form = document.getElementById('identityForm');
    const btn = document.getElementById('submitBtn');
    const loader = document.getElementById('loader');
    const errDiv = document.getElementById('error-message');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        btn.disabled = true;
        loader.classList.remove('hidden');
        errDiv.classList.add('hidden');

        const nom = document.getElementById('user_nom').value;
        const prenom = document.getElementById('user_prenom').value;
        const dob = document.getElementById('user_dob').value.split('-');
        
        try {
            // Création du Token Identité via Stripe.js
            const result = await stripe.createToken('account', {
                business_type: 'individual',
                individual: {
                    first_name: prenom,
                    last_name: nom,
                    dob: { day: dob[2], month: dob[1], year: dob[0] },
                    address: {
                        line1: document.getElementById('user_line1').value,
                        city: document.getElementById('user_city').value,
                        postal_code: document.getElementById('user_postal_code').value,
                        country: 'FR'
                    }
                },
                tos_shown_and_accepted: true
            });

            if (result.error) throw new Error(result.error.message);

            // Injection et envoi PHP
            document.getElementById('stripe_account_token').value = result.token.id;
            form.submit();

        } catch (err) {
            loader.classList.add('hidden');
            btn.disabled = false;
            errDiv.textContent = err.message;
            errDiv.classList.remove('hidden');
        }
    });
</script>
<?php require_once 'partials/footer.php'; ?>