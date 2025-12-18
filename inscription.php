<?php
// Fichier : inscription.php
// Objectif : Inscription complète avec Vérification exhaustive (Étape 2).

require_once 'api/config.php';
require_once 'partials/header.php';

// Sécurité
if (!isset($_SESSION['user'])) {
    echo "<script>window.location.href = 'login.php';</script>";
    exit;
}

$camp_token = $_GET['camp_t'] ?? '';
if (empty($camp_token)) {
    echo "<script>window.location.href = 'reservations.php';</script>";
    exit;
}

try {
    // 1. Infos Camp
    $stmtCamp = $pdo->prepare("SELECT * FROM camps WHERE token = ?");
    $stmtCamp->execute([$camp_token]);
    $camp = $stmtCamp->fetch(PDO::FETCH_ASSOC);

    if (!$camp) die('<div class="py-20 text-center text-red-600 font-bold">Séjour introuvable.</div>');

    // 2. Infos Enfants (Tous les champs nécessaires)
    $stmtEnfants = $pdo->prepare("SELECT * FROM enfants WHERE parent_id = ? ORDER BY prenom ASC");
    $stmtEnfants->execute([$_SESSION['user']['id']]);
    $enfants = $stmtEnfants->fetchAll(PDO::FETCH_ASSOC);

    // 3. Enfants déjà inscrits
    $stmtDeja = $pdo->prepare("SELECT enfant_id FROM inscriptions WHERE camp_id = ? AND statut != 'Annulé'");
    $stmtDeja->execute([$camp['id']]);
    $dejaInscrits = $stmtDeja->fetchAll(PDO::FETCH_COLUMN);

    // 4. Tarifs
    $stmtTarifs = $pdo->prepare("
        SELECT t.* FROM tarifs t
        JOIN camps_tarifs ct ON t.id = ct.tarif_id
        WHERE ct.camp_id = ?
        ORDER BY t.prix ASC
    ");
    $stmtTarifs->execute([$camp['id']]);
    $tarifs = $stmtTarifs->fetchAll(PDO::FETCH_ASSOC);

    if (empty($tarifs)) {
        $tarifs[] = ['id' => 'default', 'nom' => 'Tarif Standard', 'prix' => $camp['prix'], 'montant_libre' => 0];
    }

    $enfantsJson = json_encode($enfants);
    $tarifsJson = json_encode($tarifs);

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

$remise_pourcent = floatval($camp['remise_fratrie'] ?? 0);
?>

<main class="bg-gray-50 min-h-screen py-10 font-sans">
    <div class="container mx-auto max-w-5xl px-4">

        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-1">Inscription</h1>
            <p class="text-gray-600">Séjour : <span class="font-bold text-blue-600"><?= htmlspecialchars($camp['nom']) ?></span></p>
        </div>

        <div class="mb-8 mx-auto max-w-2xl">
            <div class="flex justify-between items-center relative z-0">
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-gray-200 -z-10"></div>
                <?php for($i=1; $i<=4; $i++): ?>
                    <div id="step-icon-<?= $i ?>" class="w-10 h-10 rounded-full flex items-center justify-center font-bold border-4 transition-colors duration-300 <?= $i===1 ? 'bg-blue-600 text-white border-blue-100' : 'bg-gray-200 text-gray-400 border-white' ?>">
                        <?= $i ?>
                    </div>
                <?php endfor; ?>
            </div>
            <div class="flex justify-between text-xs text-gray-500 mt-2 font-medium px-1">
                <span>Enfants</span>
                <span class="pl-2">Vérification</span>
                <span class="pl-2">Tarifs</span>
                <span>Paiement</span>
            </div>
        </div>

        <form id="mainForm" action="api/create_stripe_session_multi.php" method="POST" class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-h-[400px]">
            <input type="hidden" name="camp_t" value="<?= htmlspecialchars($camp_token) ?>">

            <div id="step-1" class="p-6 md:p-8 fade-in">
                <h2 class="text-xl font-bold text-gray-800 mb-6">1. Qui participe au séjour ?</h2>

                <?php if (empty($enfants)): ?>
                    <div class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                        <p class="text-gray-500 mb-4">Aucun enfant enregistré.</p>
                        <a href="add_child.php" class="px-5 py-2 bg-blue-600 text-white rounded-lg">Ajouter un enfant</a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($enfants as $enfant): 
                            $isInscrit = in_array($enfant['id'], $dejaInscrits);
                        ?>
                            <label class="relative flex items-start p-4 rounded-xl border-2 transition cursor-pointer group <?= $isInscrit ? 'border-gray-100 bg-gray-50 opacity-60 cursor-not-allowed' : 'border-gray-100 hover:border-blue-200 hover:bg-blue-50' ?>">
                                <div class="flex items-center h-5 mt-1">
                                    <input type="checkbox" name="child_ids[]" value="<?= $enfant['id'] ?>" 
                                           class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 child-check"
                                           <?= $isInscrit ? 'disabled' : '' ?>>
                                </div>
                                <div class="ml-3">
                                    <span class="block font-bold text-gray-800"><?= htmlspecialchars($enfant['prenom'] . ' ' . $enfant['nom']) ?></span>
                                    <span class="block text-sm text-gray-500">Né(e) le <?= date('d/m/Y', strtotime($enfant['date_naissance'])) ?></span>
                                    <?php if($isInscrit): ?>
                                        <span class="inline-block mt-1 text-xs font-bold text-green-600 bg-green-100 px-2 py-0.5 rounded">Déjà inscrit</span>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="mt-8 flex justify-end">
                    <button type="button" onclick="goToStep(2)" class="px-6 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition">
                        Suivant &rarr;
                    </button>
                </div>
            </div>

            <div id="step-2" class="hidden p-6 md:p-8 fade-in">
                <h2 class="text-xl font-bold text-gray-800 mb-4">2. Vérification des informations</h2>
                
                <div class="mb-6 bg-orange-50 border-l-4 border-orange-500 p-4 rounded-r-lg text-sm text-orange-700">
                    Merci de vérifier l'exactitude de toutes les données ci-dessous.
                    <span class="font-bold block mt-1">Validation possible dans <span id="timer-display">5</span>s.</span>
                </div>

                <div id="children-details-container" class="space-y-8">
                    </div>

                <div class="mt-8 pt-6 border-t flex justify-between items-center">
                    <button type="button" onclick="goToStep(1)" class="text-gray-500 hover:text-gray-800">← Retour</button>
                    <button type="button" id="btn-validate-step2" onclick="goToStep(3)" disabled class="px-6 py-3 bg-gray-300 text-gray-500 font-bold rounded-lg cursor-not-allowed transition-colors">
                        Valider et Continuer
                    </button>
                </div>
            </div>

            <div id="step-3" class="hidden p-6 md:p-8 fade-in">
                <h2 class="text-xl font-bold text-gray-800 mb-2">3. Choix des Tarifs</h2>
                <p class="text-gray-500 text-sm mb-6">Sélectionnez un tarif pour chaque participant.</p>

                <div id="tariffs-container" class="space-y-8"></div>

                <div class="mt-8 pt-6 border-t flex justify-between items-center">
                    <button type="button" onclick="goToStep(2)" class="text-gray-500 hover:text-gray-800">← Retour</button>
                    <button type="button" onclick="goToStep(4)" class="px-6 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition">
                        Voir le récapitulatif &rarr;
                    </button>
                </div>
            </div>

            <div id="step-4" class="hidden p-6 md:p-8 fade-in">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    4. Récapitulatif Financier
                </h2>

                <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                    <ul id="billing-lines" class="space-y-3 text-sm text-gray-700"></ul>
                    <hr class="my-4 border-gray-300">
                    <div class="flex justify-between items-end">
                        <span class="text-gray-600 font-medium">Total à régler</span>
                        <span class="text-3xl font-extrabold text-blue-600" id="final-total">0,00 €</span>
                    </div>
                </div>

                <div class="mt-8">
                    <button type="submit" class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold text-lg rounded-xl shadow-md transition transform hover:-translate-y-0.5 flex justify-center items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Payer par Carte Bancaire
                    </button>
                    
                    <div class="mt-6 border-t border-gray-100 pt-4 text-center">
                        <p class="text-xs text-gray-500 mb-3 flex items-center justify-center">
                            <svg class="w-3 h-3 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                            Paiement 100% sécurisé via Stripe
                        </p>
                        <div class="flex justify-center items-center space-x-3 opacity-60 grayscale hover:grayscale-0 transition duration-300">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" alt="Visa" class="h-5">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="Mastercard" class="h-5">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" alt="PayPal" class="h-5">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/f/fa/American_Express_logo_%282018%29.svg" alt="Amex" class="h-5">
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 text-center">
                    <button type="button" onclick="goToStep(3)" class="text-sm text-gray-500 hover:underline">Modifier les tarifs</button>
                </div>
            </div>

        </form>
    </div>
</main>

<script>
    // Données PHP
    const allChildren = <?= $enfantsJson ?>;
    const tariffs = <?= $tarifsJson ?>;
    const remisePercent = <?= $remise_pourcent ?>;

    let selectedChildIds = [];
    let selections = {}; 
    let timerInterval = null;

    // --- NAVIGATION ---
    function goToStep(step) {
        if (step === 2) {
            const checkboxes = document.querySelectorAll('.child-check:checked');
            if (checkboxes.length === 0) { alert("Sélectionnez au moins un enfant."); return; }
            selectedChildIds = Array.from(checkboxes).map(cb => cb.value);
            initStep2();
        }
        if (step === 3) initStep3();
        if (step === 4) updateSummary();

        // UI
        [1, 2, 3, 4].forEach(i => {
            document.getElementById(`step-${i}`).classList.add('hidden');
            const icon = document.getElementById(`step-icon-${i}`);
            icon.className = `w-10 h-10 rounded-full flex items-center justify-center font-bold border-4 transition-colors duration-300 ${i === step ? 'bg-blue-600 text-white border-blue-100' : (i < step ? 'bg-green-500 text-white border-white' : 'bg-gray-200 text-gray-400 border-white')}`;
            if(i < step) icon.innerHTML = "✓"; else icon.innerHTML = i;
        });
        document.getElementById(`step-${step}`).classList.remove('hidden');
    }

    // --- ÉTAPE 2 : VÉRIFICATION COMPLÈTE & TIMER ---
    function initStep2() {
        const container = document.getElementById('children-details-container');
        container.innerHTML = '';
        selectedChildIds.forEach(id => {
            const child = allChildren.find(c => c.id == id);
            if(child) container.innerHTML += renderChildCard(child);
        });

        // Gestion du Timer (Reset complet)
        const btn = document.getElementById('btn-validate-step2');
        const timerDisplay = document.getElementById('timer-display');
        let timeLeft = 5;
        
        btn.disabled = true;
        btn.classList.add('bg-gray-300', 'cursor-not-allowed', 'text-gray-500');
        btn.classList.remove('bg-green-600', 'hover:bg-green-700', 'text-white');
        
        if (timerInterval) clearInterval(timerInterval);
        
        timerInterval = setInterval(() => {
            timeLeft--;
            if(timerDisplay) timerDisplay.textContent = timeLeft;
            btn.textContent = `Valider (${timeLeft}s)`;
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                btn.disabled = false;
                btn.textContent = "Valider et Continuer";
                btn.classList.remove('bg-gray-300', 'cursor-not-allowed', 'text-gray-500');
                btn.classList.add('bg-green-600', 'hover:bg-green-700', 'text-white');
            }
        }, 1000);
    }

    // --- ÉTAPE 3 : TARIFS PAR ENFANT ---
    function initStep3() {
        const container = document.getElementById('tariffs-container');
        container.innerHTML = '';

        selectedChildIds.forEach((childId) => {
            const child = allChildren.find(c => c.id == childId);
            if (!child) return;

            if (!selections[childId]) selections[childId] = { tariffId: tariffs[0].id, customPrice: 0 };

            let options = '';
            tariffs.forEach(t => {
                const isSelected = selections[childId].tariffId == t.id;
                const borderClass = isSelected ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-blue-300';
                
                let inputHtml = '';
                if(t.montant_libre == 1) {
                    const val = selections[childId].customPrice > 0 ? selections[childId].customPrice : parseFloat(t.prix);
                    inputHtml = `<div class="mt-2 pl-6 flex items-center text-sm"><span class="mr-2 text-gray-600">Montant libre :</span><input type="number" step="0.01" value="${val}" class="w-24 border rounded p-1 text-right" oninput="updateSelection('${childId}', '${t.id}', this.value)" onclick="event.stopPropagation()"> €</div>`;
                }

                options += `<div class="border rounded-lg p-3 mb-2 cursor-pointer ${borderClass}" onclick="updateSelection('${childId}', '${t.id}', null)">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center"><input type="radio" ${isSelected ? 'checked' : ''} class="mr-2"> <span class="font-medium">${t.nom}</span></div>
                        <span class="font-bold">${t.prix} €</span>
                    </div>
                    ${inputHtml}
                </div>`;
            });

            container.innerHTML += `<div class="bg-white border rounded-xl p-5 shadow-sm"><h3 class="font-bold text-lg mb-3 flex items-center"><div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-2">${child.prenom.charAt(0)}</div> Tarif pour ${child.prenom}</h3>${options}</div>`;
        });
    }

    function updateSelection(childId, tariffId, customPrice) {
        if (!selections[childId]) selections[childId] = {};
        selections[childId].tariffId = tariffId;
        if (customPrice !== null) selections[childId].customPrice = parseFloat(customPrice);
        else if (tariffs.find(t => t.id == tariffId).montant_libre != 1) selections[childId].customPrice = 0;
        initStep3();
    }

    // --- ÉTAPE 4 : RÉSUMÉ ---
    function updateSummary() {
        const container = document.getElementById('billing-lines');
        container.innerHTML = '';
        let total = 0;

        selectedChildIds.forEach((childId, index) => {
            const child = allChildren.find(c => c.id == childId);
            const sel = selections[childId];
            const t = tariffs.find(x => x.id == sel.tariffId);
            
            let price = (t.montant_libre == 1 && sel.customPrice > 0) ? sel.customPrice : parseFloat(t.prix);
            let detail = `(${t.nom})`;

            if (index > 0 && remisePercent > 0) {
                const discount = price * (remisePercent / 100);
                price -= discount;
                detail += ` <span class="text-green-600 text-xs bg-green-100 px-1 rounded">Fratrie -${remisePercent}%</span>`;
            }

            total += price;
            container.innerHTML += `<li class="flex justify-between py-2 border-b border-gray-100"><span>${child.prenom} ${child.nom} <small class="text-gray-500">${detail}</small></span><span class="font-bold">${price.toFixed(2)} €</span></li>`;
        });

        document.getElementById('final-total').textContent = total.toFixed(2) + ' €';
    }

    // --- GÉNÉRATEUR FICHE COMPLÈTE (Étape 2) ---
    function renderChildCard(c) {
        const v = (val) => val && val !== 'null' ? val : '<i class="text-gray-400">Non renseigné</i>';
        const b = (val) => val == 1 ? '<span class="text-green-600 font-bold">OUI</span>' : '<span class="text-red-500">NON</span>';
        
        // Liens documents
        const docLink = (token, label) => token ? `<a href="uploads/${label == 'Carnet' ? 'sante' : 'sanitaire'}/${token}" target="_blank" class="text-blue-600 underline">Voir ${label}</a>` : '<span class="text-red-500">Manquant</span>';

        return `
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden text-sm">
            <div class="bg-gray-50 px-5 py-3 border-b flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-lg">${c.prenom.charAt(0)}</div>
                    <div><h3 class="font-bold text-gray-800 text-lg">${c.prenom} ${c.nom}</h3><p class="text-xs text-gray-500">Né(e) le ${new Date(c.date_naissance).toLocaleDateString()}</p></div>
                </div>
                <a href="modif_child.php?id=${c.id}" target="_blank" class="text-blue-600 border border-blue-200 bg-white px-3 py-1 rounded hover:bg-blue-50 transition">Modifier</a>
            </div>
            
            <div class="p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-y-4 gap-x-8">
                <div class="col-span-full font-bold text-xs uppercase text-gray-400 border-b pb-1 mt-2">Identité & Contact</div>
                <div><span class="block text-gray-500">Sexe</span> ${v(c.sexe)}</div>
                <div><span class="block text-gray-500">Civilité</span> ${v(c.civilite)}</div>
                <div><span class="block text-gray-500">Email</span> ${v(c.email_enfant)}</div>
                <div><span class="block text-gray-500">Mobile</span> ${v(c.tel_mobile_enfant)}</div>
                <div><span class="block text-gray-500">Fixe</span> ${v(c.tel_fixe_enfant)}</div>
                <div class="lg:col-span-3"><span class="block text-gray-500">Adresse</span> ${v(c.adresse)} ${v(c.code_postal)} ${v(c.ville)} ${v(c.pays)}</div>

                <div class="col-span-full font-bold text-xs uppercase text-gray-400 border-b pb-1 mt-4">Santé</div>
                <div><span class="block text-gray-500">Taille/Poids</span> ${v(c.taille)}cm / ${v(c.poids)}kg</div>
                <div><span class="block text-gray-500">Médecin</span> ${v(c.medecin_nom)} (${v(c.medecin_tel)})</div>
                <div><span class="block text-gray-500">Vaccins</span> ${v(c.vaccins_data)}</div>
                <div class="lg:col-span-3"><span class="block text-gray-500">Allergies</span> <span class="text-red-600 font-medium">${v(c.allergies)}</span></div>
                <div class="lg:col-span-3"><span class="block text-gray-500">Régime</span> ${v(c.regime_alimentaire)}</div>

                <div class="col-span-full font-bold text-xs uppercase text-gray-400 border-b pb-1 mt-4">Responsables</div>
                <div><span class="block text-gray-500">Resp. 1 (${v(c.resp1_statut)})</span> ${v(c.resp1_nom)} ${v(c.resp1_prenom)}</div>
                <div><span class="block text-gray-500">Contact 1</span> ${v(c.resp1_email)} / ${v(c.resp1_tel)}</div>
                <div><span class="block text-gray-500">Profession 1</span> ${v(c.resp1_profession)}</div>
                
                <div><span class="block text-gray-500">Resp. 2 (${v(c.resp2_statut)})</span> ${v(c.resp2_nom)} ${v(c.resp2_prenom)}</div>
                <div><span class="block text-gray-500">Contact 2</span> ${v(c.resp2_email)} / ${v(c.resp2_tel)}</div>
                <div><span class="block text-gray-500">Profession 2</span> ${v(c.resp2_profession)}</div>

                <div class="col-span-full font-bold text-xs uppercase text-gray-400 border-b pb-1 mt-4">Documents & Autorisations</div>
                <div><span class="block text-gray-500">Carnet Santé</span> ${docLink(c.carnet_sante_token, 'Carnet')}</div>
                <div><span class="block text-gray-500">Fiche Liaison</span> ${docLink(c.fiche_sanitaire_token, 'Fiche')}</div>
                <div><span class="block text-gray-500">Date Création</span> ${v(c.date_creation)}</div>

                <div><span class="block text-gray-500">Droit Image</span> ${b(c.droit_image)}</div>
                <div><span class="block text-gray-500">Contact</span> ${b(c.autorisation_contact)}</div>
                <div><span class="block text-gray-500">Accord Parent.</span> ${b(c.accord_parental)}</div>
                <div><span class="block text-gray-500">CGV</span> ${b(c.cgv_accepte)}</div>
                <div><span class="block text-gray-500">Newsletter</span> ${b(c.newsletter_accepte)}</div>
                
                <div class="col-span-full mt-2"><span class="block text-gray-500">Commentaires</span> <div class="bg-gray-50 p-2 rounded text-gray-700 italic border">${v(c.commentaires)}</div></div>
            </div>
        </div>`;
    }
</script>

<style>
    .fade-in { animation: fadeIn 0.3s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>

<?php require_once 'partials/footer.php'; ?>