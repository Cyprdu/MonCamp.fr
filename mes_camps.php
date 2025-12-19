<?php
// 1. CONFIGURATION
require_once 'api/config.php';

// 2. SÉCURITÉ
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_directeur'])) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$camps = [];
$error = null;

try {
    $stmtOrga = $pdo->prepare("SELECT id FROM organisateurs WHERE user_id = ?");
    $stmtOrga->execute([$userId]);
    $organisateurIds = $stmtOrga->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($organisateurIds)) {
        $placeholders = implode(',', array_fill(0, count($organisateurIds), '?'));
        $sql = "
            SELECT 
                c.*,
                COUNT(i.id) as nb_inscrits,
                GROUP_CONCAT(CONCAT(e.prenom, ' ', e.nom) SEPARATOR '|||') as liste_inscrits
            FROM camps c
            LEFT JOIN inscriptions i ON c.id = i.camp_id AND i.statut != 'annule'
            LEFT JOIN enfants e ON i.enfant_id = e.id
            WHERE c.organisateur_id IN ($placeholders)
            AND c.supprime = 0  -- <--- AJOUT IMPORTANT ICI
            GROUP BY c.id
            ORDER BY c.date_debut DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($organisateurIds); 
        $camps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $error = "Erreur technique : " . $e->getMessage();
}
require_once 'partials/header.php';
?>
<title>Mes Séjours - Gestion</title>
<style>
.toggle-checkbox:checked {
    right: 0;
    border-color: #68D391;
}
.toggle-checkbox:checked + .toggle-label {
    background-color: #68D391;
}
.tooltip { position: relative; }
</style>
<div class="min-h-screen bg-gray-50 py-10 font-sans">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10">
            <div>
                <h1 class="text-3xl font-extrabold text-[#0A112F]">Gestion de vos séjours</h1>
                <p class="text-gray-500 mt-1">Gérez la visibilité, les inscriptions et modifiez vos séjours.</p>
            </div>
            <a href="create_camp.php" class="inline-flex items-center justify-center gap-2 bg-[#0A112F] hover:bg-blue-900 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Créer un nouveau séjour
            </a>
        </div>
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Erreur</p>
                <p><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>
        <?php if (empty($camps)): ?>
            <div class="bg-white rounded-3xl shadow-sm p-12 text-center border border-gray-100">
                <div class="mx-auto h-24 w-24 bg-blue-50 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-[#0A112F]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Aucun séjour pour le moment</h3>
                <p class="text-gray-500 mb-6">Commencez par publier votre premier séjour.</p>
                <a href="create_camp.php" class="text-[#0A112F] font-bold hover:underline">Créer mon premier séjour &rarr;</a>
            </div>
        <?php else: ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($camps as $camp): 
                    $quota = max(1, intval($camp['quota_global']));
                    $inscrits = intval($camp['nb_inscrits']);
                    $percent = min(100, round(($inscrits / $quota) * 100));
                    $d1 = date('d/m', strtotime($camp['date_debut']));
                    $d2 = date('d/m/Y', strtotime($camp['date_fin']));
                    $img = !empty($camp['image_url']) ? $camp['image_url'] : 'assets/default_camp.jpg';
                    $token = $camp['token'];
                    
                    // Gestion de la liste des inscrits pour JS
                    $listeInscrits = $camp['liste_inscrits'] ? str_replace('|||', ',', $camp['liste_inscrits']) : '';
                ?>
                
                <div class="bg-white rounded-2xl shadow-sm hover:shadow-md transition border border-gray-200 overflow-hidden flex flex-col h-full group relative">
                    
                    <a href="gestion_camp.php?t=<?= $token ?>" class="block relative h-48 w-full overflow-hidden cursor-pointer">
                        <img src="<?= htmlspecialchars($img) ?>" alt="Cover" class="w-full h-full object-cover transition duration-700 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                        
                        <div class="absolute bottom-4 left-4 right-4">
                            <h3 class="text-white font-bold text-xl leading-tight truncate shadow-sm"><?= htmlspecialchars($camp['nom']) ?></h3>
                            <p class="text-gray-200 text-sm flex items-center gap-1 mt-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <?= htmlspecialchars($camp['ville']) ?>
                            </p>
                        </div>
                    </a>

                    <div class="absolute top-4 right-4 z-10">
                        <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                            <input type="checkbox" name="toggle" id="toggle_<?= $camp['id'] ?>" 
                                   class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-300 <?= !$camp['prive'] ? 'right-0 border-green-400' : 'left-0 border-gray-300' ?>" 
                                   <?= !$camp['prive'] ? 'checked' : '' ?>
                                   onchange="toggleCampStatus('<?= $token ?>', this)">
                            <label for="toggle_<?= $camp['id'] ?>" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer <?= !$camp['prive'] ? 'bg-green-400' : '' ?>"></label>
                        </div>
                        <span id="status_text_<?= $camp['id'] ?>" class="text-xs font-bold text-white bg-black/50 px-2 py-1 rounded">
                            <?= !$camp['prive'] ? 'Public' : 'Privé' ?>
                        </span>
                    </div>

                    <div class="p-5 flex-1 flex flex-col">
                        
                        <div class="flex justify-between items-center mb-4 text-sm text-gray-600">
                            <div class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span><?= $d1 ?> - <?= $d2 ?></span>
                            </div>
                            <div class="font-bold text-[#0A112F] text-lg">
                                <?= number_format($camp['prix'], 0, ',', ' ') ?>€
                            </div>
                        </div>

                        <div class="mb-6">
                            <div class="flex justify-between text-xs font-semibold mb-1.5">
                                <span class="text-gray-500">Remplissage</span>
                                <span class="text-[#0A112F]"><?= $inscrits ?> / <?= $quota ?></span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-[#0A112F] h-2 rounded-full" style="width: <?= $percent ?>%"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-6 gap-2 mt-auto border-t border-gray-100 pt-4">
                            
                            <a href="camp_details.php?t=<?= $token ?>" target="_blank" class="col-span-1 flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition tooltip" title="Voir la page">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>

                            <a href="edit_camp.php?t=<?= $token ?>" class="col-span-1 flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-orange-600 hover:bg-orange-50 transition" title="Modifier">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </a>

                            <a href="boost.php?t=<?= $token ?>" class="col-span-1 flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-purple-600 hover:bg-purple-50 transition" title="Booster le camp">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </a>

                            <a href="api/duplicate_camp.php?t=<?= $token ?>" onclick="return confirm('Dupliquer ce séjour ?')" class="col-span-1 flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-green-600 hover:bg-green-50 transition" title="Dupliquer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                            </a>

                            <button type="button" 
                                    onclick="openDeleteModal('<?= $token ?>', '<?= addslashes(htmlspecialchars($camp['nom'])) ?>', '<?= addslashes($listeInscrits) ?>')" 
                                    class="col-span-1 flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50 transition" title="Supprimer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>

                            <button onclick="shareLink('<?= $token ?>')" class="col-span-1 flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 transition" title="Copier le lien">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" /></svg>
                            </button>

                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="deleteModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-80 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative bg-white p-8 rounded-2xl shadow-2xl w-full max-w-lg border border-red-100">
        
        <div class="text-center mb-6">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900">Suppression du Séjour</h3>
            <p class="text-sm text-gray-500 mt-2">Cette action est <span class="font-bold text-red-600 uppercase">irréversible</span>.</p>
        </div>

        <form id="deleteForm" action="api/delete_camp.php" method="POST" class="space-y-6">
            <input type="hidden" name="token" id="modalToken">

            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-sm max-h-40 overflow-y-auto">
                <p class="font-bold text-gray-700 mb-2">Inscrits à ce séjour :</p>
                <div id="modalInscritsList" class="text-gray-600">
                    </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Veuillez écrire <span class="font-bold select-all" id="modalCampNameDisplay"></span> ci-dessous pour confirmer :
                </label>
                <input type="text" id="confirmNameInput" class="w-full border-2 border-gray-300 rounded-lg p-2 focus:border-red-500 focus:outline-none" placeholder="Nom du séjour">
            </div>

            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input id="refundCheck" type="checkbox" required class="w-5 h-5 text-red-600 border-gray-300 rounded focus:ring-red-500">
                </div>
                <div class="ml-3 text-sm">
                    <label for="refundCheck" class="font-medium text-gray-700">Je m'engage sur l'honneur à rembourser intégralement les familles inscrites si le séjour est annulé.</label>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-2">
                <button type="button" onclick="closeDeleteModal()" class="w-full px-4 py-3 bg-gray-100 text-gray-700 rounded-lg font-bold hover:bg-gray-200 transition">
                    Annuler
                </button>
                <button type="submit" id="finalDeleteBtn" class="w-full px-4 py-3 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700 transition opacity-50 cursor-not-allowed" disabled>
                    Supprimer définitivement
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// --- 1. Gestion du Toggle Public/Privé ---
function toggleCampStatus(token, checkbox) {
    const isPublic = checkbox.checked;
    const statusText = document.getElementById('status_text_' + checkbox.id.split('_')[1]);
    const originalState = !isPublic; // Pour rollback si erreur

    // Update UI immédiate
    statusText.textContent = isPublic ? 'Public' : 'Privé';
    checkbox.classList.toggle('right-0', isPublic);
    checkbox.classList.toggle('left-0', !isPublic);
    checkbox.classList.toggle('border-green-400', isPublic);
    checkbox.classList.toggle('border-gray-300', !isPublic);
    checkbox.nextElementSibling.classList.toggle('bg-green-400', isPublic);

    // Appel API
    // Assurez-vous d'avoir créé api/update_camp_visibility.php
    fetch('api/update_camp_visibility.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `token=${token}&prive=${isPublic ? 0 : 1}` 
    })
    .then(res => res.json())
    .then(data => {
        if(!data.success) {
            alert("Erreur lors de la mise à jour.");
            // Rollback UI
            checkbox.checked = originalState;
            statusText.textContent = originalState ? 'Public' : 'Privé';
        }
    })
    .catch(err => {
        console.error(err);
        checkbox.checked = originalState;
    });
}

// --- 2. Gestion de la Modale de Suppression ---
let targetCampName = "";

function openDeleteModal(token, campName, inscritsStr) {
    // Reset
    document.getElementById('deleteForm').reset();
    document.getElementById('finalDeleteBtn').disabled = true;
    document.getElementById('finalDeleteBtn').classList.add('opacity-50', 'cursor-not-allowed');
    
    // Set Data
    document.getElementById('modalToken').value = token;
    document.getElementById('modalCampNameDisplay').textContent = campName;
    targetCampName = campName; 

    // Gestion liste inscrits
    const listDiv = document.getElementById('modalInscritsList');
    if (inscritsStr && inscritsStr.trim() !== "") {
        const names = inscritsStr.split('|||');
        let html = '<ul class="list-disc pl-5 mt-1 space-y-1">';
        names.forEach(name => {
            html += `<li class="text-red-600 font-medium">${name}</li>`;
        });
        html += '</ul>';
        listDiv.innerHTML = html;
    } else {
        listDiv.innerHTML = '<span class="text-green-600 italic">Aucun inscrit pour le moment.</span>';
    }

    // Show Modal
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Vérification de la saisie pour activer le bouton
const nameInput = document.getElementById('confirmNameInput');
const checkInput = document.getElementById('refundCheck');
const btn = document.getElementById('finalDeleteBtn');

function checkDeleteForm() {
    const isNameOk = nameInput.value.trim() === targetCampName;
    const isChecked = checkInput.checked;

    if (isNameOk && isChecked) {
        btn.disabled = false;
        btn.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
    }
}

nameInput.addEventListener('input', checkDeleteForm);
checkInput.addEventListener('change', checkDeleteForm);

// --- 3. Partage de lien ---
function shareLink(token) {
    if (!token) return alert("Pas de lien.");
    const fullUrl = "https://moncamp.fr/camp_details.php?t=" + token;
    
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(fullUrl).then(() => {
            alert("Lien copié !");
        });
    } else {
        prompt("Copiez le lien :", fullUrl);
    }
}
</script>

<?php require_once 'partials/footer.php'; ?>