<?php
// Fichier: create_camp.php

// 1. Configuration et Session
require_once 'api/config.php';

// Sécurité Session : On ne démarre que si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Vérification des droits (Directeur uniquement)
if (!isset($_SESSION['user']['id']) || (!isset($_SESSION['user']['is_directeur']) || !$_SESSION['user']['is_directeur'])) {
    header('Location: index.php');
    exit;
}

$pageTitle = "Créer un séjour - ColoMap";

// 3. Chargement des données
try {
    // Organisateurs
    $stmt = $pdo->prepare("SELECT * FROM organisateurs WHERE user_id = ? ORDER BY nom ASC");
    $stmt->execute([$_SESSION['user']['id']]);
    $organisateurs = $stmt->fetchAll();

    // Moyens de paiement
    $stmtPaiement = $pdo->query("SELECT * FROM moyens_paiement WHERE actif = 1 ORDER BY nom ASC");
    $moyensPaiement = $stmtPaiement->fetchAll();

} catch (Exception $e) {
    $error = "Erreur lors du chargement des données.";
}

// 4. Styles CSS réutilisables
$inputClass = "w-full border-2 border-gray-300 bg-gray-50 rounded p-2 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors duration-200 shadow-sm";
$labelClass = "block text-gray-800 font-bold mb-2 text-sm uppercase tracking-wide";

// 5. Inclusion du Header
include 'partials/header.php';
?>

<link rel="icon" type="image/png" href="favico.png">

<div class="max-w-4xl mx-auto mt-10 mb-20 px-4">
    <h1 class="text-3xl font-extrabold text-gray-800 mb-8 border-b-4 border-blue-500 inline-block pb-2">
        Créer un nouveau séjour
    </h1>

    <div class="bg-white p-8 rounded-lg shadow-xl relative border border-gray-200">
        
        <form id="createCampForm" action="api/add_camp.php" method="POST" enctype="multipart/form-data" class="space-y-8">
            
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-xl font-bold mb-6 text-blue-700 flex items-center">
                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded mr-3 text-sm">1</span> 
                    Organisation & Responsabilité
                </h2>
                
                <div class="mb-6">
                    <label class="<?= $labelClass ?>">Organisme Responsable *</label>
                    <p class="text-xs text-gray-500 mb-2">Sélectionnez l'organisme qui porte juridiquement et financièrement ce séjour.</p>
                    <div class="flex gap-2">
                        <select id="organisateur-select" name="organisateur_id" required class="<?= $inputClass ?>">
                            <option value="">-- Choisir un organisme --</option>
                            <?php foreach($organisateurs as $orga): ?>
                                <option value="<?= htmlspecialchars($orga['id']) ?>"><?= htmlspecialchars($orga['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" onclick="openModal('newOrganismeModal')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 rounded shadow transition">
                            +
                        </button>
                    </div>
                </div>
            </div>

            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-xl font-bold mb-6 text-blue-700 flex items-center">
                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded mr-3 text-sm">2</span> 
                    Informations du séjour
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label class="<?= $labelClass ?>">Nom du séjour *</label>
                        <input type="text" name="nom" required class="<?= $inputClass ?>" placeholder="Ex: Aventure dans les Alpes">
                    </div>
                    
                    <div>
                        <label class="<?= $labelClass ?>">Thème / Activités</label>
                        <input type="text" name="activites" placeholder="Ex: Equitation, Informatique..." class="<?= $inputClass ?>">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="<?= $labelClass ?>">Âge Min *</label>
                            <input type="number" name="age_min" min="0" required class="<?= $inputClass ?>">
                        </div>
                        <div>
                            <label class="<?= $labelClass ?>">Âge Max *</label>
                            <input type="number" name="age_max" min="0" required class="<?= $inputClass ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-xl font-bold mb-6 text-blue-700 flex items-center">
                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded mr-3 text-sm">3</span> 
                    Lieu et Dates
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="<?= $labelClass ?>">Ville du séjour *</label>
                        <input type="text" name="ville" required class="<?= $inputClass ?>">
                    </div>
                    <div>
                        <label class="<?= $labelClass ?>">Code Postal</label>
                        <input type="text" name="cp" class="<?= $inputClass ?>">
                    </div>
                    <div class="col-span-2">
                        <label class="<?= $labelClass ?>">Adresse (Lieu précis ou RDV) *</label>
                        <input type="text" name="adresse" placeholder="Ex: Gare de Lyon, ou 12 rue des Pins..." required class="<?= $inputClass ?>">
                        <p class="text-xs text-gray-500 mt-1">Sera affiché aux inscrits.</p>
                    </div>
                    <div>
                        <label class="<?= $labelClass ?>">Date de début *</label>
                        <input type="date" name="date_debut" required class="<?= $inputClass ?>">
                    </div>
                    <div>
                        <label class="<?= $labelClass ?>">Date de fin *</label>
                        <input type="date" name="date_fin" required class="<?= $inputClass ?>">
                    </div>
                </div>
                <div class="mt-6">
                    <label class="<?= $labelClass ?>">Description complète *</label>
                    <textarea name="description" rows="5" required class="<?= $inputClass ?>" placeholder="Décrivez le séjour, l'hébergement, les activités..."></textarea>
                </div>
            </div>

            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-xl font-bold mb-6 text-blue-700 flex items-center">
                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded mr-3 text-sm">4</span> 
                    Moyens de paiement acceptés
                </h2>

                <div class="bg-orange-50 border-l-4 border-orange-500 text-orange-800 p-4 mb-6 rounded text-sm">
                    <p class="font-bold">⚠️ Information importante</p>
                    <p>Cochez les moyens de paiement que vous acceptez pour ce séjour. </p>
                    <p class="mt-1">Notez que <strong>seuls les paiements par Carte Bancaire</strong> (via Stripe) sont gérés automatiquement par ColoMap (si l'inscription en ligne est activée).</p>
                    <p>Les autres moyens de paiement (Chèques, Espèces, ANCV...) devront être gérés manuellement par l'organisme.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach($moyensPaiement as $mp): ?>
                        <label class="flex items-center space-x-3 bg-gray-50 p-3 rounded border border-gray-200 cursor-pointer hover:bg-gray-100">
                            <input type="checkbox" name="paiements[]" value="<?= $mp['id'] ?>" class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                            <span class="text-gray-800 font-medium"><?= htmlspecialchars($mp['nom']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-xl font-bold mb-6 text-blue-700 flex items-center">
                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded mr-3 text-sm">5</span> 
                    Visibilité et Inscription
                </h2>
                
                <div class="mb-6 bg-gray-50 p-4 rounded border border-gray-300">
                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" name="prive" class="mt-1 w-5 h-5 text-gray-800 rounded focus:ring-gray-500">
                        <div>
                            <span class="text-lg font-bold text-gray-800">Séjour Privé (Lien caché)</span>
                            <p class="text-sm text-gray-600 mt-1">
                                Si coché, ce séjour n'apparaîtra <strong>pas</strong> dans les recherches publiques ni sur l'accueil.<br>
                                Seules les personnes disposant du lien sécurisé pourront y accéder.
                            </p>
                        </div>
                    </label>
                </div>

                <div class="bg-blue-50 p-6 rounded-lg border-2 border-blue-200 shadow-inner">
                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" name="inscription_en_ligne" id="toggleOnline" class="mt-1 w-6 h-6 text-blue-600 rounded focus:ring-blue-500">
                        <div>
                            <span class="text-lg font-bold text-blue-900">Activer les inscriptions et la gestion via ColoMap ?</span>
                            <p class="text-sm text-blue-700 mt-1">
                                Gestion automatique des dossiers, paiements par carte, quotas et recrutement des animateurs.
                            </p>
                            
                            <div id="commissionWarning" class="hidden mt-4 p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-900 rounded">
                                <p class="font-bold text-sm uppercase mb-1">⚠️ Conditions tarifaires</p>
                                <p class="text-sm mb-3">
                                    En activant la gestion en ligne, ColoMap prélève une commission de <strong>1%</strong> sur chaque transaction effectuée pour couvrir les frais de fonctionnement et bancaires.
                                </p>
                                <label class="flex items-center font-bold cursor-pointer text-sm">
                                    <input type="checkbox" id="acceptToS" disabled class="mr-2 w-4 h-4 text-blue-600 focus:ring-blue-500 rounded border-gray-300">
                                    Je valide les conditions et accepte la commission de 1%.
                                </label>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <div id="blockExternal" class="border-b border-gray-200 pb-6 bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h2 class="text-xl font-bold mb-4 text-gray-700">Configuration Inscription Externe</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="<?= $labelClass ?>">Prix du séjour (€) *</label>
                        <input type="number" name="prix_simple" class="<?= $inputClass ?>" placeholder="Ex: 500">
                    </div>
                    <div>
                        <label class="<?= $labelClass ?>">Lien d'inscription (URL)</label>
                        <input type="text" name="lien_externe" class="<?= $inputClass ?>" placeholder="https://mon-site.com/inscription">
                    </div>
                    <div class="col-span-2">
                        <label class="<?= $labelClass ?>">Adresse/Email retour dossier</label>
                        <textarea name="adresse_retour_dossier" rows="2" class="<?= $inputClass ?>" placeholder="Ex: contact@asso.com ou adresse postale"></textarea>
                    </div>
                </div>
            </div>

            <div id="blockInternal" class="hidden space-y-8">
                
                <div class="border-l-4 border-blue-500 pl-6 py-2">
                    <h3 class="text-lg font-bold mb-4 text-gray-800">Tarification & Options</h3>
                    
                    <div class="mb-6 bg-gray-100 p-4 rounded border border-gray-300">
                        <label class="<?= $labelClass ?>">Tarifs applicables</label>
                        <div class="flex flex-col md:flex-row gap-3 mb-3">
                            <select id="tarifSelect" class="<?= $inputClass ?> flex-grow">
                                <option value="">Choisir organisme d'abord</option>
                            </select>
                            <button type="button" id="addTarifBtn" class="bg-green-600 hover:bg-green-700 text-white font-bold px-4 py-2 rounded shadow transition">
                                Ajouter
                            </button>
                            <button type="button" onclick="openModal('modalNewTarif')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded shadow whitespace-nowrap transition">
                                Créer Tarif
                            </button>
                        </div>
                        <div id="tarifsContainer" class="space-y-2"></div>
                        <input type="hidden" name="tarifs" id="hiddenTarifsInput">
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="<?= $labelClass ?>">Date limite inscription</label>
                            <input type="date" name="date_limite_inscription" class="<?= $inputClass ?>">
                        </div>
                        <div>
                            <label class="<?= $labelClass ?>">Remise Fratrie (%)</label>
                            <input type="number" name="remise_fratrie" min="0" max="100" value="0" class="<?= $inputClass ?>">
                        </div>
                    </div>
                </div>

                <div class="border-l-4 border-pink-500 pl-6 py-2">
                    <h3 class="text-lg font-bold mb-4 text-gray-800">Places & Quotas Enfants</h3>
                    <div class="mb-4">
                        <label class="<?= $labelClass ?>">Nombre total de places *</label>
                        <input type="number" name="quota_global" class="<?= $inputClass ?>">
                    </div>
                    
                    <label class="flex items-center space-x-2 mb-4 cursor-pointer">
                        <input type="checkbox" id="toggleGenderQuota" class="w-5 h-5 text-pink-600 rounded">
                        <span class="font-bold text-gray-700">Activer des quotas Filles / Garçons distincts</span>
                    </label>

                    <div id="genderQuotaBlock" class="hidden grid grid-cols-2 gap-6 bg-pink-50 p-4 rounded border border-pink-100">
                        <div>
                            <label class="block text-sm font-bold text-pink-700 mb-2">Quota Filles</label>
                            <input type="number" name="quota_fille" class="<?= $inputClass ?> border-pink-300">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-blue-700 mb-2">Quota Garçons</label>
                            <input type="number" name="quota_garcon" class="<?= $inputClass ?> border-blue-300">
                        </div>
                    </div>
                </div>

                <div class="border-l-4 border-purple-500 pl-6 py-2">
                    <label class="flex items-center space-x-3 cursor-pointer mb-4">
                        <input type="checkbox" name="gestion_animateur" id="toggleAnim" class="w-6 h-6 text-purple-600 rounded focus:ring-purple-500">
                        <span class="text-lg font-bold text-purple-900">Recruter les animateurs via ColoMap ?</span>
                    </label>

                    <div id="animBlock" class="hidden space-y-6 bg-purple-50 p-5 rounded border border-purple-100">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block font-bold mb-1 text-sm text-gray-700">Nb Animateurs Total</label>
                                <input type="number" name="quota_max_anim" class="<?= $inputClass ?>">
                            </div>
                            <div>
                                <label class="block font-bold mb-1 text-sm text-pink-600">Dont Filles</label>
                                <input type="number" name="quota_anim_fille" class="<?= $inputClass ?>">
                            </div>
                            <div>
                                <label class="block font-bold mb-1 text-sm text-blue-600">Dont Garçons</label>
                                <input type="number" name="quota_anim_garcon" class="<?= $inputClass ?>">
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-6 border-t border-purple-200 pt-4">
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="anim_plus_18" class="form-checkbox text-purple-600 h-5 w-5 rounded">
                                <span class="font-medium text-gray-800">Majeurs uniquement</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="bafa_obligatoire" class="form-checkbox text-purple-600 h-5 w-5 rounded">
                                <span class="font-medium text-gray-800">BAFA Obligatoire</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="remuneration_anim" class="form-checkbox text-purple-600 h-5 w-5 rounded">
                                <span class="font-medium text-gray-800">Rémunération prévue</span>
                            </label>
                        </div>

                        <div class="bg-white p-4 rounded border border-purple-200">
                            <label class="flex items-center space-x-2 mb-2">
                                <input type="checkbox" name="anim_doit_payer" id="toggleAnimPrice" class="form-checkbox text-purple-600 h-5 w-5 rounded">
                                <span class="font-bold text-gray-700">L'animateur doit payer une participation aux frais</span>
                            </label>
                            <div id="animPriceBlock" class="hidden mt-3 pl-7">
                                <label class="block text-sm font-bold text-gray-600 mb-1">Montant à payer (€)</label>
                                <input type="number" name="prix_anim" class="<?= $inputClass ?> w-40">
                            </div>
                        </div>
                    </div>
                </div>

            </div> 

            <div class="pt-6 border-t border-gray-200">
                <h2 class="text-xl font-bold mb-4 text-blue-700 flex items-center">
                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded mr-3 text-sm">6</span> 
                    Image de couverture
                </h2>
                <div class="bg-yellow-50 border border-yellow-200 p-4 rounded text-sm text-yellow-800 mb-3 flex items-start">
                    <span class="text-xl mr-2">📷</span>
                    <p><strong>Attention :</strong> L'image ne doit pas dépasser <strong>5 Mo</strong>. Formats acceptés : JPG, PNG, WEBP.</p>
                </div>
                <input type="file" name="image" accept="image/*" required class="w-full bg-white p-4 border-2 border-dashed border-gray-400 rounded-lg hover:bg-gray-50 cursor-pointer transition">
            </div>

            <div class="flex justify-end pt-8">
                <button type="submit" class="bg-gradient-to-r from-blue-600 to-blue-800 hover:from-blue-700 hover:to-blue-900 text-white font-bold py-4 px-10 rounded-lg shadow-lg transform transition hover:scale-105">
                    Valider et Créer le séjour
                </button>
            </div>

        </form>
    </div>
</div>

<div id="newOrganismeModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-70 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative bg-white p-8 rounded-lg shadow-2xl w-full max-w-md">
        <h3 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2">Nouvel Organisme</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nom *</label>
                <input type="text" id="newOrgaNom" class="<?= $inputClass ?>">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Email contact *</label>
                <input type="email" id="newOrgaMail" class="<?= $inputClass ?>">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Téléphone</label>
                <input type="text" id="newOrgaTel" class="<?= $inputClass ?>">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Site Web</label>
                <input type="text" id="newOrgaWeb" class="<?= $inputClass ?>">
            </div>
        </div>
        <div class="flex justify-end gap-3 mt-8">
            <button type="button" onclick="closeModal('newOrganismeModal')" class="px-5 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg font-bold transition">Annuler</button>
            <button type="button" id="saveNewOrganisme" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold transition">Enregistrer</button>
        </div>
    </div>
</div>

<div id="modalNewTarif" class="hidden fixed inset-0 bg-gray-900 bg-opacity-70 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative bg-white p-8 rounded-lg shadow-2xl w-full max-w-md">
        <h3 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2">Nouveau Tarif</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nom (ex: Tarif QF1) *</label>
                <input type="text" id="newTarifNom" class="<?= $inputClass ?>">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Prix (€)</label>
                <input type="number" id="newTarifPrix" class="<?= $inputClass ?>">
            </div>
            <div class="flex items-center bg-gray-100 p-3 rounded border">
                <input type="checkbox" id="newTarifMontantLibre" class="h-5 w-5 text-blue-600 rounded border-gray-300">
                <label for="newTarifMontantLibre" class="ml-3 font-bold text-gray-700">Tarif à montant libre (Don, etc)</label>
            </div>
        </div>
        <div class="flex justify-end gap-3 mt-8">
            <button type="button" onclick="closeModal('modalNewTarif')" class="px-5 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg font-bold transition">Annuler</button>
            <button type="button" id="saveNewTarif" class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-bold transition">Créer</button>
        </div>
    </div>
</div>

<script>
// --- FONCTIONS UTILITAIRES GLOBALES ---
window.openModal = function(modalID) {
    document.getElementById(modalID).classList.remove('hidden');
}

window.closeModal = function(modalID) {
    document.getElementById(modalID).classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. GESTION DES TOGGLES (Affichage Dynamique)
    
    // Toggle: Inscription en ligne vs Externe + Commission
    const toggleOnline = document.getElementById('toggleOnline');
    const blockExt = document.getElementById('blockExternal');
    const blockInt = document.getElementById('blockInternal');
    const commWarning = document.getElementById('commissionWarning');
    const acceptTosCheckbox = document.getElementById('acceptToS');

    function updateOnlineMode() {
        if(toggleOnline.checked) {
            // Mode EN LIGNE
            blockExt.classList.add('hidden');
            blockInt.classList.remove('hidden');
            
            // Afficher l'avertissement commission
            commWarning.classList.remove('hidden');
            // Rendre la case obligatoire
            acceptTosCheckbox.disabled = false;
            acceptTosCheckbox.required = true;
        } else {
            // Mode EXTERNE
            blockExt.classList.remove('hidden');
            blockInt.classList.add('hidden');
            
            // Cacher l'avertissement
            commWarning.classList.add('hidden');
            acceptTosCheckbox.disabled = true;
            acceptTosCheckbox.required = false;
        }
    }
    toggleOnline.addEventListener('change', updateOnlineMode);
    updateOnlineMode(); // Initialisation au chargement

    // Toggle: Quotas Genre
    const toggleGender = document.getElementById('toggleGenderQuota');
    const genderBlock = document.getElementById('genderQuotaBlock');
    toggleGender.addEventListener('change', function() {
        if (this.checked) genderBlock.classList.remove('hidden');
        else genderBlock.classList.add('hidden');
    });

    // Toggle: Animateurs
    const toggleAnim = document.getElementById('toggleAnim');
    const animBlock = document.getElementById('animBlock');
    toggleAnim.addEventListener('change', function() {
        if (this.checked) animBlock.classList.remove('hidden');
        else animBlock.classList.add('hidden');
    });

    // Toggle: Prix Animateur
    const toggleAnimPrice = document.getElementById('toggleAnimPrice');
    const animPriceBlock = document.getElementById('animPriceBlock');
    toggleAnimPrice.addEventListener('change', function() {
        if (this.checked) animPriceBlock.classList.remove('hidden');
        else animPriceBlock.classList.add('hidden');
    });


    // 2. GESTION ORGANISATEUR & TARIFS (API)
    
    let selectedTarifs = []; // Stockage local des tarifs sélectionnés
    const orgaSelect = document.getElementById('organisateur-select');
    
    // Si la page est rechargée avec une valeur déjà sélectionnée
    if(orgaSelect.value) loadTarifs(orgaSelect.value);

    orgaSelect.addEventListener('change', function() {
        const id = this.value;
        if(id) {
            loadTarifs(id);
        } else {
            resetTarifsUI();
        }
    });

    function resetTarifsUI() {
        document.getElementById('tarifSelect').innerHTML = '<option value="">-- Sélectionnez d\'abord un organisme --</option>';
        document.getElementById('tarifsContainer').innerHTML = '';
        selectedTarifs = [];
        updateHiddenTarifsInput();
    }

    function loadTarifs(organisateurId) {
        const tarifSelect = document.getElementById('tarifSelect');
        tarifSelect.innerHTML = '<option>Chargement...</option>';

        // Reset visuel
        document.getElementById('tarifsContainer').innerHTML = '';
        selectedTarifs = [];
        updateHiddenTarifsInput();

        fetch(`api/get_tarifs_by_organisateur.php?organisateur_id=${organisateurId}`)
            .then(res => res.json())
            .then(tarifs => {
                tarifSelect.innerHTML = '<option value="">-- Sélectionner un tarif --</option>';
                
                if (tarifs.length === 0) {
                    const opt = document.createElement('option');
                    opt.text = "Aucun tarif trouvé pour cet organisme";
                    opt.disabled = true;
                    tarifSelect.appendChild(opt);
                    return;
                }
                
                tarifs.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    const prixTxt = (t.montant_libre == 1) ? "Libre" : `${t.prix} €`;
                    opt.textContent = `${t.nom} - ${prixTxt}`;
                    // Stockage des data
                    opt.dataset.nom = t.nom;
                    opt.dataset.prix = t.prix;
                    opt.dataset.libre = (t.montant_libre == 1) ? "1" : "0";
                    tarifSelect.appendChild(opt);
                });
            })
            .catch(err => {
                console.error(err);
                tarifSelect.innerHTML = '<option>Erreur chargement</option>';
            });
    }

    // BOUTON AJOUTER TARIF (UI)
    document.getElementById('addTarifBtn').addEventListener('click', function() {
        const select = document.getElementById('tarifSelect');
        if(!select.value) return alert("Veuillez sélectionner un tarif dans la liste.");
        
        const opt = select.options[select.selectedIndex];
        const tarifId = select.value;

        // Vérif doublon
        if (selectedTarifs.some(t => t.id == tarifId)) return alert("Ce tarif est déjà ajouté.");

        addTarifToDOM({
            id: tarifId,
            nom: opt.dataset.nom,
            prix: parseFloat(opt.dataset.prix),
            montant_libre: opt.dataset.libre === "1"
        });
    });

    // Fonction d'affichage du tarif dans la liste
    function addTarifToDOM(tarif) {
        selectedTarifs.push(tarif);
        updateHiddenTarifsInput();

        const div = document.createElement('div');
        div.className = "flex justify-between items-center bg-white p-3 border rounded shadow-sm hover:bg-gray-50 transition";
        div.id = `row-tarif-${tarif.id}`;
        
        const prixDisplay = tarif.montant_libre ? "Montant libre" : `${tarif.prix} €`;
        
        div.innerHTML = `
            <div>
                <span class="font-bold text-gray-800">${tarif.nom}</span>
                <span class="text-sm text-gray-500 ml-2">(${prixDisplay})</span>
            </div>
            <button type="button" class="text-red-500 hover:text-red-700 font-bold px-2 py-1 rounded hover:bg-red-50" onclick="removeTarif('${tarif.id}')">
                &times; Supprimer
            </button>
        `;
        document.getElementById('tarifsContainer').appendChild(div);
    }

    // Fonction globale pour supprimer un tarif (accessible via onclick)
    window.removeTarif = function(id) {
        selectedTarifs = selectedTarifs.filter(t => t.id != id);
        updateHiddenTarifsInput();
        const el = document.getElementById(`row-tarif-${id}`);
        if(el) el.remove();
    }

    function updateHiddenTarifsInput() {
        document.getElementById('hiddenTarifsInput').value = JSON.stringify(selectedTarifs);
    }

    // 3. API : CREATION ORGANISME
    document.getElementById('saveNewOrganisme').addEventListener('click', function() {
        const nom = document.getElementById('newOrgaNom').value;
        const mail = document.getElementById('newOrgaMail').value;
        
        if(!nom || !mail) return alert("Le Nom et l'Email sont obligatoires.");

        const btn = this;
        btn.textContent = "Sauvegarde...";
        btn.disabled = true;

        fetch('api/create_organisateur.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                nom: nom,
                email: mail,
                tel: document.getElementById('newOrgaTel').value,
                web: document.getElementById('newOrgaWeb').value
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.textContent = "Enregistrer";
            btn.disabled = false;
            
            if(data.error) throw new Error(data.error);
            
            // Ajouter à la liste déroulante
            const select = document.getElementById('organisateur-select');
            const opt = document.createElement('option');
            opt.value = data.id;
            opt.textContent = data.nom;
            opt.selected = true;
            select.appendChild(opt);
            
            closeModal('newOrganismeModal');
            // Reset form
            document.getElementById('newOrgaNom').value = "";
            document.getElementById('newOrgaMail').value = "";
            
            // Recharger les tarifs (qui seront vides)
            select.dispatchEvent(new Event('change'));
        })
        .catch(err => {
            btn.textContent = "Enregistrer";
            btn.disabled = false;
            alert("Erreur: " + err.message);
        });
    });

    // 4. API : CREATION TARIF
    document.getElementById('saveNewTarif').addEventListener('click', function() {
        const nom = document.getElementById('newTarifNom').value;
        const orgaId = document.getElementById('organisateur-select').value;
        
        if(!nom) return alert("Le nom du tarif est obligatoire.");
        if(!orgaId) return alert("Veuillez sélectionner un organisme avant de créer un tarif.");

        const payload = {
            nom: nom,
            prix: document.getElementById('newTarifPrix').value,
            montant_libre: document.getElementById('newTarifMontantLibre').checked,
            organisateur_id: orgaId
        };

        const btn = this;
        btn.textContent = "Création...";
        btn.disabled = true;

        fetch('api/create_tarif.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            btn.textContent = "Créer";
            btn.disabled = false;

            if(data.error) throw new Error(data.error);

            // Ajouter directement à la liste visuelle
            addTarifToDOM({
                id: data.id,
                nom: data.nom,
                prix: data.prix,
                montant_libre: (data.montant_libre == 1)
            });

            // Ajouter aussi au select pour la cohérence
            const select = document.getElementById('tarifSelect');
            const opt = document.createElement('option');
            opt.value = data.id;
            const prixTxt = data.montant_libre ? "Libre" : `${data.prix} €`;
            opt.textContent = `${data.nom} - ${prixTxt}`;
            opt.dataset.nom = data.nom;
            opt.dataset.prix = data.prix;
            opt.dataset.libre = data.montant_libre ? "1" : "0";
            select.appendChild(opt);

            closeModal('modalNewTarif');
            // Reset form
            document.getElementById('newTarifNom').value = "";
            document.getElementById('newTarifPrix').value = "";
        })
        .catch(err => {
            btn.textContent = "Créer";
            btn.disabled = false;
            alert("Erreur: " + err.message);
        });
    });

});
</script>

<?php 
// Inclusion du footer avec vérification
if (file_exists('partials/footer.php')) {
    include 'partials/footer.php'; 
} else {
    // Si pas de footer, on ferme proprement les balises ouvertes par le header
    echo "</main></body></html>";
}
?>