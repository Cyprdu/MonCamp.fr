<?php
// demande_de_virement_info.php

require_once 'api/config.php';

if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_directeur'])) {
    header('Location: index.php');
    exit;
}

$organisateurId = filter_input(INPUT_GET, 'organisateur_id', FILTER_VALIDATE_INT);
$userId = $_SESSION['user']['id'];
$organisateur = null;
$error = filter_input(INPUT_GET, 'error', FILTER_SANITIZE_STRING);

// Récupération
$stmt = $pdo->prepare("SELECT * FROM organisateurs WHERE id = ? AND user_id = ?");
$stmt->execute([$organisateurId, $userId]);
$organisateur = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$organisateur) {
    die("Organisme introuvable.");
}

// SÉCURITÉ : Si pas de compte Stripe, retour dashboard
if (empty($organisateur['stripe_account_id'])) {
    header("Location: dashboard_organisme.php?organisateur_id=$organisateurId&error=Veuillez d'abord vérifier votre identité.");
    exit;
}

$montantTotal = floatval($organisateur['portefeuille']);
$commission = round($montantTotal * 0.01, 2);
$net = $montantTotal - $commission;

require_once 'partials/header.php';
?>
<title>Demande de Virement</title>
<script src="https://js.stripe.com/v3/"></script>

<div class="min-h-screen bg-gray-50 py-10 font-sans">
    <div class="max-w-2xl mx-auto px-4">
        
        <div class="mb-6">
            <a href="dashboard_organisme.php?organisateur_id=<?= $organisateurId ?>" class="text-gray-500 hover:text-blue-600">&larr; Retour</a>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-2xl font-bold text-[#0A112F] mb-6">Confirmer le virement</h1>
            
            <?php if ($error): ?>
                <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= $error ?></div>
            <?php endif; ?>

            <form action="api/request_virement.php" method="POST" id="virementForm">
                <input type="hidden" name="organisateur_id" value="<?= $organisateurId ?>">
                <input type="hidden" name="stripe_bank_token" id="stripe_bank_token">

                <div class="mb-6 p-4 bg-gray-50 rounded border">
                    <label class="block text-sm font-medium text-gray-700">Montant à virer</label>
                    <input type="number" name="montant_total_demande" value="<?= $montantTotal ?>" class="w-full text-2xl font-bold bg-transparent border-none p-0 focus:ring-0" readonly>
                    <div class="mt-2 text-sm text-gray-500 flex justify-between">
                        <span>Commission (1%): -<?= $commission ?>€</span>
                        <span class="font-bold text-green-600">Net: <?= $net ?>€</span>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">IBAN de réception</label>
                    <input type="text" id="iban" value="<?= htmlspecialchars($organisateur['iban'] ?? '') ?>" class="w-full border rounded p-2 uppercase" placeholder="FR76..." required>
                    <p class="text-xs text-gray-500 mt-1">Sera utilisé pour générer l'ordre de virement sécurisé.</p>
                </div>

                <input type="hidden" id="titulaire" value="<?= htmlspecialchars($organisateur['nom']) ?>">

                <button type="submit" id="submitBtn" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg shadow">
                    Confirmer et transférer les fonds
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    const stripe = Stripe('<?= STRIPE_PUBLISHABLE_KEY ?>');
    const form = document.getElementById('virementForm');
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('submitBtn').textContent = "Traitement en cours...";

        const iban = document.getElementById('iban').value;
        const name = document.getElementById('titulaire').value;

        // On tokenise juste l'IBAN pour ce virement spécifique
        const result = await stripe.createToken('bank_account', {
            country: 'FR',
            currency: 'eur',
            account_number: iban,
            account_holder_name: name,
            account_holder_type: 'company' // Ou individual selon le statut
        });

        if (result.error) {
            alert(result.error.message);
            document.getElementById('submitBtn').disabled = false;
        } else {
            document.getElementById('stripe_bank_token').value = result.token.id;
            form.submit();
        }
    });
</script>
<?php require_once 'partials/footer.php'; ?>