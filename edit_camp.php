<?php
require_once 'api/config.php';

// 1. SÉCURITÉ DE SESSION
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id']) || !$_SESSION['user']['is_directeur']) {
    header('Location: index.php');
    exit;
}

// 2. RÉCUPÉRATION DU TOKEN (et non plus de l'ID)
$token = $_GET['t'] ?? '';
if (empty($token)) {
    header('Location: mes_camps.php');
    exit;
}

// 3. CHARGEMENT DES DONNÉES ET VÉRIFICATION PROPRIÉTAIRE
try {
    // On récupère le camp + vérif que l'user est bien le propriétaire de l'organisateur lié
    $stmt = $pdo->prepare("
        SELECT c.*, o.user_id as owner_id 
        FROM camps c 
        JOIN organisateurs o ON c.organisateur_id = o.id 
        WHERE c.token = ?
    ");
    $stmt->execute([$token]);
    $camp = $stmt->fetch();

    if (!$camp) {
        die("Camp introuvable.");
    }

    // VERROUILLAGE : Si l'utilisateur connecté n'est pas le créateur
    if ($camp['owner_id'] != $_SESSION['user']['id']) {
        die("<div style='color:red;text-align:center;margin-top:50px;'>⛔ ACCÈS REFUSÉ : Ce séjour ne vous appartient pas.</div>");
    }

    // Chargement des listes nécessaires
    // A. Organisateurs du directeur
    $stmtOrgas = $pdo->prepare("SELECT * FROM organisateurs WHERE user_id = ? ORDER BY nom ASC");
    $stmtOrgas->execute([$_SESSION['user']['id']]);
    $organisateurs = $stmtOrgas->fetchAll();

    // B. Tarifs liés à ce camp (pour pré-remplir le JS)
    $stmtTarifs = $pdo->prepare("
        SELECT t.* FROM tarifs t 
        JOIN camps_tarifs ct ON t.id = ct.tarif_id 
        WHERE ct.camp_id = ?
    ");
    $stmtTarifs->execute([$camp['id']]);
    $currentTarifs = $stmtTarifs->fetchAll();

} catch (Exception $e) {
    die("Erreur de chargement : " . $e->getMessage());
}

// Styles
$inputClass = "w-full border-2 border-gray-300 bg-gray-50 rounded p-2 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors duration-200 shadow-sm";
$labelClass = "block text-gray-800 font-bold mb-2 text-sm uppercase tracking-wide";

include 'partials/header.php';
?>

<title>Modifier le séjour - <?= htmlspecialchars($camp['nom']) ?></title>

<div class="max-w-4xl mx-auto mt-10 mb-20 px-4">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-extrabold text-gray-800 border-b-4 border-blue-500 inline-block pb-2">
            Modifier le séjour
        </h1>
        <a href="mes_camps.php" class="text-gray-500 hover:text-blue-600 font-bold">&larr; Annuler</a>
    </div>

    <div class="bg-white p-8 rounded-lg shadow-xl relative border border-gray-200">
        
        <form id="editCampForm" action="api/update_camp.php" method="POST" enctype="multipart/form-data" class="space-y-8">
            
            <input type="hidden" name="camp_id" value="<?= $camp['id'] ?>">
            <input type="hidden" name="token" value="<?= $camp['token'] ?>">

            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-xl font-bold mb-6 text-blue-700 flex items-center"><span class="bg-blue-100 text-blue-700 px-3 py-1 rounded mr-3 text-sm">1</span> Informations générales</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label class="<?= $labelClass ?>">Nom du séjour *</label>
                        <input type="text" name="nom" value="<?= htmlspecialchars($camp['nom']) ?>" required class="<?= $inputClass ?>">
                    </div>
                    
                    <div>
                        <label class="<?= $labelClass ?>">Thème / Activités</label>
                        <input type="text" name="activites" value="<?= htmlspecialchars($camp['description']) ?>" class="<?= $inputClass ?>"> </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="<?= $labelClass ?>">Âge Min</label>
                            <input type="number" name="age_min" value="<?= $camp['age_min'] ?>" required class="<?= $inputClass ?>">
                        </div>
                        <div>
                            <label class="<?= $labelClass ?>">Âge Max</label>
                            <input type="number" name="age_max" value="<?= $camp['age_max'] ?>" required class="<?= $inputClass ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-xl font-bold mb-6 text-blue-700 flex items-center"><span class="bg-blue-100 text-blue-700 px-3 py-1 rounded mr-3 text-sm">2</span> Lieu et Dates</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="<?= $labelClass ?>">Ville *</label>
                        <input type="text" name="ville" value="<?= htmlspecialchars($camp['ville']) ?>" required class="<?= $inputClass ?>">
                    </div>
                    <div>
                        <label class="<?= $labelClass ?>">Code Postal</label>
                        <input type="text" name="cp" value="<?= htmlspecialchars($camp['code_postal']) ?>" class="<?= $inputClass ?>">
                    </div>
                    <div class="col-span-2">
                        <label class="<?= $labelClass ?>">Adresse *</label>
                        <input type="text" name="adresse" value="<?= htmlspecialchars($camp['adresse']) ?>" required class="<?= $inputClass ?>">
                    </div>
                    <div>
                        <label class="<?= $labelClass ?>">Date de début *</label>
                        <input type="date" name="date_debut" value="<?= $camp['date_debut'] ?>" required class="<?= $inputClass ?>">
                    </div>
                    <div>
                        <label class="<?= $labelClass ?>">Date de fin *</label>
                        <input type="date" name="date_fin" value="<?= $camp['date_fin'] ?>" required class="<?= $inputClass ?>">
                    </div>
                </div>
                <div class="mt-6">
                    <label class="<?= $labelClass ?>">Description complète *</label>
                    <textarea name="description" rows="5" required class="<?= $inputClass ?>"><?= htmlspecialchars($camp['description']) ?></textarea>
                </div>
            </div>

            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-xl font-bold mb-6 text-blue-700 flex items-center"><span class="bg-blue-100 text-blue-700 px-3 py-1 rounded mr-3 text-sm">3</span> Options</h2>
                
                <div class="mb-6 bg-gray-50 p-4 rounded border border-gray-300">
                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" name="prive" <?= $camp['prive'] ? 'checked' : '' ?> class="mt-1 w-5 h-5 text-gray-800 rounded focus:ring-gray-500">
                        <div>
                            <span class="text-lg font-bold text-gray-800">Séjour Privé</span>
                            <p class="text-sm text-gray-600">Si coché, le camp est accessible uniquement via le lien sécurisé.</p>
                        </div>
                    </label>
                </div>

                <div class="bg-blue-50 p-6 rounded-lg border-2 border-blue-200">
                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" name="inscription_en_ligne" id="toggleOnline" <?= $camp['inscription_en_ligne'] ? 'checked' : '' ?> class="mt-1 w-6 h-6 text-blue-600 rounded">
                        <div>
                            <span class="text-lg font-bold text-blue-900">Inscriptions en ligne via ColoMap</span>
                        </div>
                    </label>
                </div>
            </div>

            <div id="blockExternal" class="<?= $camp['inscription_en_ligne'] ? 'hidden' : '' ?> border-b border-gray-200 pb-6 bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h2 class="text-xl font-bold mb-4 text-gray-700">Mode "Vitrine" (Inscription externe)</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="<?= $labelClass ?>">Prix affiché (€)</label>
                        <input type="number" name="prix_simple" value="<?= $camp['prix'] ?>" class="<?= $inputClass ?>">
                    </div>
                    <div>
                        <label class="<?= $labelClass ?>">Lien externe</label>
                        <input type="text" name="lien_externe" value="<?= htmlspecialchars($camp['lien_externe']) ?>" class="<?= $inputClass ?>">
                    </div>
                    <div class="col-span-2">
                        <label class="<?= $labelClass ?>">Adresse retour dossier</label>
                        <textarea name="adresse_retour_dossier" rows="2" class="<?= $inputClass ?>"><?= htmlspecialchars($camp['adresse_retour_dossier']) ?></textarea>
                    </div>
                </div>
            </div>

            <div id="blockInternal" class="<?= $camp['inscription_en_ligne'] ? '' : 'hidden' ?> space-y-8">
                
                <div class="border-l-4 border-blue-500 pl-6 py-2">
                    <h3 class="text-lg font-bold mb-4 text-gray-800">Organisation & Tarifs</h3>
                    
                    <div class="mb-6">
                        <label class="<?= $labelClass ?>">Organisateur *</label>
                        <select id="organisateur-select" name="organisateur_id" class="<?= $inputClass ?>">
                            <?php foreach($organisateurs as $orga): ?>
                                <option value="<?= $orga['id'] ?>" <?= $orga['id'] == $camp['organisateur_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($orga['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-6 bg-gray-100 p-4 rounded border border-gray-300">
                        <label class="<?= $labelClass ?>">Tarifs (Sélectionnez pour ajouter)</label>
                        <div class="flex gap-3 mb-3">
                            <select id="tarifSelect" class="<?= $inputClass ?> flex-grow"></select>
                            <button type="button" id="addTarifBtn" class="bg-green-600 text-white px-4 rounded font-bold">Ajouter</button>
                        </div>
                        <div id="tarifsContainer" class="space-y-2"></div>
                        <input type="hidden" name="tarifs" id="hiddenTarifsInput">
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="<?= $labelClass ?>">Date limite</label>
                            <input type="date" name="date_limite_inscription" value="<?= $camp['date_limite_inscription'] ?>" class="<?= $inputClass ?>">
                        </div>
                        <div>
                            <label class="<?= $labelClass ?>">Remise Fratrie (%)</label>
                            <input type="number" name="remise_fratrie" value="<?= $camp['remise_fratrie'] ?>" class="<?= $inputClass ?>">
                        </div>
                    </div>
                </div>

                <div class="border-l-4 border-pink-500 pl-6 py-2">
                    <h3 class="text-lg font-bold mb-4 text-gray-800">Places</h3>
                    <div class="mb-4">
                        <label class="<?= $labelClass ?>">Quota Total *</label>
                        <input type="number" name="quota_global" value="<?= $camp['quota_global'] ?>" class="<?= $inputClass ?>">
                    </div>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="text-sm font-bold text-pink-700">Filles</label>
                            <input type="number" name="quota_fille" value="<?= $camp['quota_fille'] ?>" class="<?= $inputClass ?>">
                        </div>
                        <div>
                            <label class="text-sm font-bold text-blue-700">Garçons</label>
                            <input type="number" name="quota_garcon" value="<?= $camp['quota_garcon'] ?>" class="<?= $inputClass ?>">
                        </div>
                    </div>
                </div>

                <div class="border-l-4 border-purple-500 pl-6 py-2">
                    <label class="flex items-center space-x-3 cursor-pointer mb-4">
                        <input type="checkbox" name="gestion_animateur" id="toggleAnim" <?= $camp['gestion_animateur'] ? 'checked' : '' ?> class="w-5 h-5 text-purple-600 rounded">
                        <span class="text-lg font-bold text-purple-900">Recrutement Animateurs</span>
                    </label>

                    <div id="animBlock" class="<?= $camp['gestion_animateur'] ? '' : 'hidden' ?> space-y-4 bg-purple-50 p-4 rounded">
                        <div class="grid grid-cols-3 gap-4">
                            <div><label class="text-sm font-bold">Total</label><input type="number" name="quota_max_anim" value="<?= $camp['quota_max_anim'] ?>" class="<?= $inputClass ?>"></div>
                            <div><label class="text-sm font-bold text-pink-600">Filles</label><input type="number" name="quota_anim_fille" value="<?= $camp['quota_anim_fille'] ?>" class="<?= $inputClass ?>"></div>
                            <div><label class="text-sm font-bold text-blue-600">Garçons</label><input type="number" name="quota_anim_garcon" value="<?= $camp['quota_anim_garcon'] ?>" class="<?= $inputClass ?>"></div>
                        </div>
                        <div class="flex gap-4 mt-2">
                            <label><input type="checkbox" name="anim_plus_18" <?= $camp['anim_plus_18']?'checked':'' ?>> Majeurs</label>
                            <label><input type="checkbox" name="bafa_obligatoire" <?= $camp['bafa_obligatoire']?'checked':'' ?>> BAFA</label>
                            <label><input type="checkbox" name="remuneration_anim" <?= $camp['remuneration_anim']?'checked':'' ?>> Rémunéré</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-gray-200">
                <h2 class="text-xl font-bold mb-4 text-blue-700">Image</h2>
                <div class="flex items-center gap-4">
                    <img src="<?= $camp['image_url'] ?>" class="w-32 h-20 object-cover rounded border">
                    <div class="flex-grow">
                        <label class="<?= $labelClass ?>">Changer l'image (Optionnel)</label>
                        <input type="file" name="image" accept="image/*" class="w-full bg-white p-2 border rounded">
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-8 gap-4">
                <a href="mes_camps.php" class="px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300">Annuler</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg shadow-lg">Enregistrer les modifications</button>
            </div>

        </form>
    </div>
</div>

<script>
// Données injectées depuis PHP
let selectedTarifs = <?= json_encode($currentTarifs) ?>; // Tarifs actuels du camp

document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. Gestion Affichage (Toggles) ---
    const toggleOnline = document.getElementById('toggleOnline');
    const blockExt = document.getElementById('blockExternal');
    const blockInt = document.getElementById('blockInternal');

    toggleOnline.addEventListener('change', () => {
        if(toggleOnline.checked) {
            blockExt.classList.add('hidden');
            blockInt.classList.remove('hidden');
        } else {
            blockExt.classList.remove('hidden');
            blockInt.classList.add('hidden');
        }
    });

    const toggleAnim = document.getElementById('toggleAnim');
    const animBlock = document.getElementById('animBlock');
    toggleAnim.addEventListener('change', () => {
        animBlock.classList.toggle('hidden', !toggleAnim.checked);
    });

    // --- 2. Gestion Tarifs ---
    const orgaSelect = document.getElementById('organisateur-select');
    
    // Init: Charger les tarifs possibles pour l'orga sélectionné
    loadTarifsOptions(orgaSelect.value);
    // Init: Afficher les tarifs déjà sélectionnés
    renderSelectedTarifs();

    orgaSelect.addEventListener('change', function() {
        loadTarifsOptions(this.value);
        // Attention: Changer d'orga ne supprime pas les tarifs déjà sélectionnés visuellement, mais ça peut créer des incohérences.
        // Pour faire simple ici, on garde la sélection.
    });

    function loadTarifsOptions(orgaId) {
        const select = document.getElementById('tarifSelect');
        select.innerHTML = '<option>Chargement...</option>';
        
        fetch(`api/get_tarifs_by_organisateur.php?organisateur_id=${orgaId}`)
            .then(res => res.json())
            .then(data => {
                select.innerHTML = '<option value="">-- Ajouter un tarif --</option>';
                data.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    const prix = t.montant_libre ? "Libre" : t.prix + "€";
                    opt.textContent = `${t.nom} (${prix})`;
                    // Stockage data
                    opt.dataset.nom = t.nom;
                    opt.dataset.prix = t.prix;
                    opt.dataset.libre = t.montant_libre;
                    select.appendChild(opt);
                });
            });
    }

    document.getElementById('addTarifBtn').addEventListener('click', function() {
        const select = document.getElementById('tarifSelect');
        if(!select.value) return;
        
        const opt = select.options[select.selectedIndex];
        const newId = parseInt(select.value); // Ensure integer comparison

        // Vérif doublon
        if(selectedTarifs.some(t => parseInt(t.id) === newId)) return alert("Déjà ajouté");

        selectedTarifs.push({
            id: newId,
            nom: opt.dataset.nom,
            prix: opt.dataset.prix,
            montant_libre: opt.dataset.libre
        });
        renderSelectedTarifs();
    });

    // Fonction globale pour le onclick
    window.removeTarif = function(id) {
        selectedTarifs = selectedTarifs.filter(t => parseInt(t.id) !== parseInt(id)); // ParseInt pour sécurité
        renderSelectedTarifs();
    }

    function renderSelectedTarifs() {
        const container = document.getElementById('tarifsContainer');
        container.innerHTML = '';
        selectedTarifs.forEach(t => {
            const div = document.createElement('div');
            div.className = "flex justify-between items-center bg-white p-2 border rounded shadow-sm";
            const prix = (t.montant_libre == 1) ? "Libre" : t.prix + " €";
            div.innerHTML = `
                <span class="font-bold">${t.nom} <small class="font-normal text-gray-500">(${prix})</small></span>
                <button type="button" class="text-red-500 font-bold px-2" onclick="removeTarif(${t.id})">&times;</button>
            `;
            container.appendChild(div);
        });
        document.getElementById('hiddenTarifsInput').value = JSON.stringify(selectedTarifs);
    }

});
</script>

<?php 
if (file_exists('partials/footer.php')) include 'partials/footer.php';
else echo "</body></html>";
?>