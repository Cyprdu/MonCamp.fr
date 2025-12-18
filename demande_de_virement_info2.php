<?php
// demande_de_virement_info.php

// 1. CONFIG
require_once 'api/config.php';

// 2. SÉCURITÉ
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_directeur'])) {
    header('Location: index.php');
    exit;
}

// PARAMÈTRES
$COMISSION_RATE = 1.00; // 1.00%
$MIN_AMOUNT = 10.00; 

// 3. LOGIQUE
$organisateurId = filter_input(INPUT_GET, 'organisateur_id', FILTER_VALIDATE_INT);
$userId = $_SESSION['user']['id'];
$organisateur = null;
$error = filter_input(INPUT_GET, 'error', FILTER_SANITIZE_STRING);
$montantTotal = 0.00;
$montantTotalDemandé = 0.00;
$montantApresCommission = 0.00;
$commission = 0.00;

// Date estimée (J+2 ouvrés pour Stripe)
$dateVirementEstime = (new DateTime())->modify('+2 weekdays')->format('Y-m-d H:i:s');

if (!$organisateurId) {
    $error = "ID d'organisme manquant.";
} else {
    try {
        $stmtOrga = $pdo->prepare("SELECT * FROM organisateurs WHERE id = ? AND user_id = ?");
        $stmtOrga->execute([$organisateurId, $userId]);
        $organisateur = $stmtOrga->fetch(PDO::FETCH_ASSOC);

        // Récupérer aussi l'utilisateur pour pré-remplir
        $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $userCurrent = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$organisateur) {
            $error = "Organisme introuvable.";
        } else {
            $montantTotal = floatval($organisateur['portefeuille']);
            
            if ($montantTotal < $MIN_AMOUNT) {
                $error = "Solde insuffisant (min " . number_format($MIN_AMOUNT, 2) . "€).";
            } else {
                $montantTotalDemandé = $montantTotal;
                $commission = round($montantTotalDemandé * ($COMISSION_RATE / 100), 2);
                $montantApresCommission = $montantTotalDemandé - $commission;
            }
        }

    } catch (Exception $e) {
        $error = "Erreur SQL : " . $e->getMessage();
    }
}

require_once 'partials/header.php';
?>

<title>Virement Automatique - <?= htmlspecialchars($organisateur['nom'] ?? '') ?></title>
<script src="https://js.stripe.com/v3/"></script>

<div class="min-h-screen bg-gray-50 py-10 font-sans">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex items-center gap-4 mb-8">
            <a href="dashboard_organisme.php?organisateur_id=<?= $organisateurId ?>" class="text-gray-500 hover:text-[#0A112F]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <h1 class="text-3xl font-extrabold text-[#0A112F]">Demande de Virement</h1>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Attention</p>
                <p><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($organisateur): ?>
            <div class="bg-white rounded-xl shadow-lg p-8 border border-gray-100">
                
                <div class="flex items-center space-x-3 text-gray-700 mb-6 border-b pb-4">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <div>
                        <p class="text-lg font-medium">Virement sécurisé Stripe Connect</p>
                        <p class="text-xs text-gray-500">Vos fonds seront transférés vers le compte bancaire ci-dessous.</p>
                    </div>
                </div>

                <form action="api/request_virement2.php" method="POST" id="virementForm">
                    <input type="hidden" name="organisateur_id" value="<?= $organisateurId ?>">
                    
                    <input type="hidden" name="stripe_account_token" id="stripe_account_token">
                    <input type="hidden" name="stripe_bank_token" id="stripe_bank_token">

                    <div class="mb-8 p-6 bg-gray-50 rounded-lg border border-gray-200">
                        <h2 class="text-xl font-bold text-[#0A112F] mb-4">1. Montant du virement</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Montant à retirer</label>
                                <div class="relative mt-1">
                                    <input type="number" step="0.01" min="0.01" max="<?= $montantTotal ?>" 
                                        name="montant_total_demande" id="montant_total_demande" 
                                        value="<?= number_format($montantTotalDemandé, 2, '.', '') ?>" 
                                        class="block w-full rounded-md border-gray-300 border-2 p-2 text-lg font-bold pr-12 focus:border-purple-500 focus:ring-purple-500" required
                                        oninput="updateCalcul()">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-500">€</div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Disponible : <?= number_format($montantTotal, 2, ',', ' ') ?>€</p>
                            </div>
                            <div class="flex flex-col justify-center">
                                <div class="flex justify-between text-sm text-gray-600">
                                    <span>Commission (<?= $COMISSION_RATE ?>%) :</span>
                                    <span id="display_com" class="font-medium text-red-600">- <?= number_format($commission, 2, ',', ' ') ?>€</span>
                                </div>
                                <div class="flex justify-between text-lg font-bold text-green-700 mt-2 pt-2 border-t border-gray-200">
                                    <span>Net versé :</span>
                                    <span id="display_net"><?= number_format($montantApresCommission, 2, ',', ' ') ?>€</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-8 p-6 bg-white rounded-xl border border-gray-200 shadow-sm">
                        <h2 class="text-xl font-bold text-[#0A112F] mb-4">2. Identité du bénéficiaire</h2>
                        <p class="text-sm text-gray-500 mb-4 bg-yellow-50 p-2 rounded border border-yellow-200">
                            Stripe requiert ces informations pour valider l'ouverture du compte technique et autoriser le virement.
                        </p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Prénom</label>
                                <input type="text" id="user_prenom" value="<?= htmlspecialchars($userCurrent['prenom']) ?>" class="mt-1 block w-full rounded-md border-gray-300 border shadow-sm p-2" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nom</label>
                                <input type="text" id="user_nom" value="<?= htmlspecialchars($userCurrent['nom']) ?>" class="mt-1 block w-full rounded-md border-gray-300 border shadow-sm p-2" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Date de naissance</label>
                                <input type="date" id="user_dob" class="mt-1 block w-full rounded-md border-gray-300 border shadow-sm p-2" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" id="user_email" value="<?= htmlspecialchars($userCurrent['email']) ?>" class="mt-1 block w-full rounded-md border-gray-300 border shadow-sm p-2" required>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Adresse postale complète</label>
                            <input type="text" id="user_line1" placeholder="10 rue de la Paix" class="mt-1 block w-full rounded-md border-gray-300 border shadow-sm p-2 mb-2" required>
                            <div class="grid grid-cols-2 gap-4">
                                <input type="text" id="user_postal_code" placeholder="Code Postal" class="block w-full rounded-md border-gray-300 border shadow-sm p-2" required>
                                <input type="text" id="user_city" placeholder="Ville" class="block w-full rounded-md border-gray-300 border shadow-sm p-2" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-8 p-6 bg-white rounded-xl border border-gray-200 shadow-sm">
                        <h2 class="text-xl font-bold text-[#0A112F] mb-4">3. Coordonnées Bancaires (RIB)</h2>
                        <div>
                            <label for="iban" class="block text-sm font-medium text-gray-700">IBAN <span class="text-red-500">*</span></label>
                            <input type="text" id="iban" value="<?= htmlspecialchars($organisateur['iban'] ?? '') ?>" 
                                class="mt-1 block w-full rounded-md border-gray-400 border-2 shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2 uppercase tracking-wide" 
                                placeholder="FR76 ...." required>
                            <p class="text-xs text-gray-500 mt-1">Stripe vérifiera automatiquement la validité de cet IBAN.</p>
                        </div>
                    </div>

                    <div id="error-message" class="hidden text-red-600 text-sm font-bold mb-4 text-center"></div>

                    <div class="pt-6 border-t border-gray-200 flex justify-end">
                        <button type="submit" id="submitBtn" class="inline-flex justify-center items-center gap-2 rounded-xl border border-transparent bg-green-600 py-3 px-6 text-sm font-medium text-white shadow-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition transform hover:scale-105">
                            Valider le virement
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="loader" class="fixed inset-0 bg-gray-900 bg-opacity-70 hidden flex items-center justify-center z-50">
    <div class="bg-white p-8 rounded-xl shadow-2xl flex flex-col items-center max-w-sm text-center">
        <svg class="animate-spin h-12 w-12 text-purple-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <h3 class="text-xl font-bold text-gray-800">Sécurisation en cours</h3>
        <p class="text-sm text-gray-500 mt-2">Nous chiffrons vos données et contactons Stripe pour validation...</p>
    </div>
</div>

<script>
    // Configuration Stripe avec la clé publique PHP
    const stripe = Stripe('<?= STRIPE_PUBLISHABLE_KEY ?>');
    const form = document.getElementById('virementForm');
    const submitBtn = document.getElementById('submitBtn');
    const loader = document.getElementById('loader');
    const errorDiv = document.getElementById('error-message');

    // Mise à jour calculs (inchangé)
    const COMMISSION = <?= $COMISSION_RATE / 100 ?>;
    function updateCalcul() {
        let brut = parseFloat(document.getElementById('montant_total_demande').value) || 0;
        let com = brut * COMMISSION;
        let net = brut - com;
        document.getElementById('display_com').innerText = '- ' + com.toFixed(2) + '€';
        document.getElementById('display_net').innerText = net.toFixed(2) + '€';
    }

    // Gestion de la soumission du formulaire
    form.addEventListener('submit', async function(event) {
        event.preventDefault(); // On bloque l'envoi classique
        
        // UI
        errorDiv.classList.add('hidden');
        submitBtn.disabled = true;
        loader.classList.remove('hidden');

        // Récupération des données Identité
        const nom = document.getElementById('user_nom').value;
        const prenom = document.getElementById('user_prenom').value;
        const dobVal = document.getElementById('user_dob').value; // YYYY-MM-DD
        const email = document.getElementById('user_email').value;
        
        const line1 = document.getElementById('user_line1').value;
        const city = document.getElementById('user_city').value;
        const postal_code = document.getElementById('user_postal_code').value;
        
        const iban = document.getElementById('iban').value;

        // Parsing date
        let dobParts = dobVal.split('-'); // [YYYY, MM, DD]
        if(dobParts.length !== 3) {
            showError("Date de naissance invalide");
            return;
        }

        try {
            // 1. Créer le Token de COMPTE (Identité)
            const accountResult = await stripe.createToken('account', {
                business_type: 'individual',
                individual: {
                    first_name: prenom,
                    last_name: nom,
                    email: email,
                    dob: {
                        day: dobParts[2],
                        month: dobParts[1],
                        year: dobParts[0]
                    },
                    address: {
                        line1: line1,
                        city: city,
                        postal_code: postal_code,
                        country: 'FR' // Assumé France
                    }
                },
                tos_shown_and_accepted: true // Obligatoire pour Custom
            });

            if (accountResult.error) {
                throw new Error("Erreur Identité: " + accountResult.error.message);
            }

            // 2. Créer le Token BANCAIRE (IBAN)
            // CORRECTION ICI : Pas de routing_number pour la France
            const bankResult = await stripe.createToken('bank_account', {
                country: 'FR',
                currency: 'eur',
                account_number: iban,
                account_holder_name: prenom + ' ' + nom,
                account_holder_type: 'individual'
            });

            if (bankResult.error) {
                throw new Error("Erreur RIB: " + bankResult.error.message);
            }

            // 3. Injecter les tokens dans le formulaire
            document.getElementById('stripe_account_token').value = accountResult.token.id;
            document.getElementById('stripe_bank_token').value = bankResult.token.id;

            // 4. Soumettre le formulaire au PHP
            form.submit();

        } catch (err) {
            showError(err.message);
        }
    });

    function showError(msg) {
        loader.classList.add('hidden');
        submitBtn.disabled = false;
        errorDiv.textContent = msg;
        errorDiv.classList.remove('hidden');
    }
</script>

<?php require_once 'partials/footer.php'; ?>