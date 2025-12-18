<?php
require_once 'partials/header.php';

// Sécurité
if (!isset($_SESSION['user'])) {
    header('Location: login.php?redirect=add_child.php');
    exit;
}
$redirect = $_GET['redirect'] ?? 'children';

// Styles
$inputClass = "w-full bg-gray-50 border border-gray-400 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm";
$labelClass = "block mb-2 text-sm font-bold text-gray-800 uppercase tracking-wide";

// LISTE DES PROFESSIONS (CSP)
$professions = [
    "Agriculteurs",
    "Artisans",
    "Commerçants et assimilés",
    "Chefs d'entreprise de 10 salariés ou plus",
    "Professions libérales",
    "Cadres de la fonction publique",
    "Professeurs, professions scientifiques",
    "Professions de l'information, des arts et des spectacles",
    "Cadres administratifs et commerciaux d'entreprises",
    "Ingénieurs et cadres techniques d'entreprises",
    "Professeurs des écoles, instituteurs et professions assimilées",
    "Professions intermédiaires de la santé et du travail social",
    "Clergé, religieux",
    "Professions intermédiaires administratives de la fonction publique",
    "Professions intermédiaires administratives et commerciales des entreprises",
    "Techniciens (sauf techniciens tertiaires)",
    "Contremaîtres, agents de maîtrise (maîtrise administrative exclue)",
    "Employés civils et agents de service de la fonction publique",
    "Policiers, militaires et agents de surveillance",
    "Employés administratifs d'entreprise",
    "Employés de commerce",
    "Personnels des services directs aux particuliers",
    "Ouvriers qualifiés de type industriel",
    "Ouvriers qualifiés de type artisanal",
    "Chauffeurs",
    "Ouvriers qualifiés de la manutention, du magasinage et du transport",
    "Ouvriers non qualifiés de type industriel",
    "Ouvriers non qualifiés de type artisanal",
    "Ouvriers agricoles et assimilés",
    "Retraité",
    "Sans activité professionnelle",
    "Autre"
];

// Helper pour générer les options
function renderProfessionOptions($proList) {
    $html = '<option value="">-- Sélectionner --</option>';
    foreach ($proList as $p) {
        $html .= '<option value="' . htmlspecialchars($p) . '">' . htmlspecialchars($p) . '</option>';
    }
    return $html;
}
?>

<title>Ajouter un enfant - ColoMap</title>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

<main class="container mx-auto max-w-4xl px-4 py-12">
    <div class="mb-8">
        <a href="children" class="inline-flex items-center gap-2 text-gray-600 hover:text-blue-600 font-medium">&larr; Retour à la liste</a>
    </div>

    <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-200">
        <h1 class="text-3xl font-extrabold text-gray-900 mb-2 border-b-4 border-blue-500 inline-block pb-1">Fiche Sanitaire & Inscription</h1>
        <p class="text-gray-500 text-sm mb-8">Tous les champs marqués d'un astérisque (*) sont obligatoires.</p>

        <form action="api/add_child.php" method="POST" enctype="multipart/form-data" class="space-y-10" id="childForm">
            <input type="hidden" name="redirect_url" value="<?= htmlspecialchars($redirect) ?>">
            
            <input type="hidden" name="signature_data" id="signature_data">

            <div class="border-b border-gray-200 pb-8">
                <h2 class="text-xl font-bold text-blue-800 mb-6 flex items-center"><span class="bg-blue-600 text-white w-8 h-8 flex items-center justify-center rounded-full mr-3 text-sm shadow">1</span> Identité de l'enfant</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div><label class="<?= $labelClass ?>">Civilité</label><select name="civilite" class="<?= $inputClass ?>"><option>M.</option><option>Mme</option></select></div>
                    <div><label class="<?= $labelClass ?>">Sexe *</label><select name="sexe" required class="<?= $inputClass ?>"><option value="Homme">Garçon</option><option value="Femme">Fille</option></select></div>
                    <div><label class="<?= $labelClass ?>">Nom *</label><input type="text" name="nom" required class="<?= $inputClass ?>"></div>
                    <div><label class="<?= $labelClass ?>">Prénom *</label><input type="text" name="prenom" required class="<?= $inputClass ?>"></div>
                    <div><label class="<?= $labelClass ?>">Date de naissance *</label><input type="date" name="date_naissance" required class="<?= $inputClass ?>"></div>
                    <div><label class="<?= $labelClass ?>">Email (Enfant)</label><input type="email" name="email_enfant" class="<?= $inputClass ?>"></div>
                    <div><label class="<?= $labelClass ?>">Mobile (Enfant)</label><input type="tel" name="tel_mobile_enfant" class="<?= $inputClass ?>"></div>
                    <div><label class="<?= $labelClass ?>">Fixe (Domicile)</label><input type="tel" name="tel_fixe_enfant" class="<?= $inputClass ?>"></div>
                    
                    <div class="md:col-span-2 mt-2"><h3 class="font-bold text-gray-700 border-b">Adresse</h3></div>
                    <div class="md:col-span-2"><label class="<?= $labelClass ?>">Rue / Voie *</label><input type="text" name="adresse" required class="<?= $inputClass ?>"></div>
                    <div><label class="<?= $labelClass ?>">Code Postal *</label><input type="text" name="code_postal" required class="<?= $inputClass ?>"></div>
                    <div><label class="<?= $labelClass ?>">Ville *</label><input type="text" name="ville" required class="<?= $inputClass ?>"></div>
                    <div class="md:col-span-2"><label class="<?= $labelClass ?>">Pays</label><select name="pays" class="<?= $inputClass ?>"><option value="France">France</option><option value="Autre">Autre</option></select></div>
                </div>
            </div>

            <div class="border-b border-gray-200 pb-8">
                <h2 class="text-xl font-bold text-red-700 mb-6 flex items-center">
                    <span class="bg-red-600 text-white w-8 h-8 flex items-center justify-center rounded-full mr-3 text-sm shadow">2</span>
                    Santé & Fiche de Liaison
                </h2>

                <div class="space-y-6">
                    <div class="bg-red-50 p-4 rounded-lg border border-red-100">
                        <label class="flex items-center space-x-3 cursor-pointer mb-2">
                            <input type="checkbox" id="check_allergies" class="w-5 h-5 text-red-600 rounded focus:ring-red-500">
                            <span class="font-bold text-gray-800">L'enfant a-t-il des allergies ou problèmes médicaux ?</span>
                        </label>
                        <div id="block_allergies" class="hidden mt-2 pl-8">
                            <label class="text-xs font-bold text-red-600 uppercase mb-1">Précisez (Médicamenteuses, alimentaires...)</label>
                            <textarea name="infos_sante" id="input_infos_sante" rows="2" class="<?= $inputClass ?> bg-white"></textarea>
                        </div>
                    </div>

                    <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-100">
                        <label class="flex items-center space-x-3 cursor-pointer mb-2">
                            <input type="checkbox" id="check_regime" class="w-5 h-5 text-yellow-600 rounded focus:ring-yellow-500">
                            <span class="font-bold text-gray-800">Suit-il un régime alimentaire spécifique ?</span>
                        </label>
                        <div id="block_regime" class="hidden mt-2 pl-8">
                            <label class="text-xs font-bold text-yellow-600 uppercase mb-1">Précisez (Sans porc, végétarien...)</label>
                            <textarea name="regime_alimentaire" id="input_regime" rows="1" class="<?= $inputClass ?> bg-white"></textarea>
                        </div>
                    </div>

                    <div>
                        <label class="<?= $labelClass ?>">📄 Copie du carnet de santé (Vaccins) *</label>
                        <input type="file" name="carnet_sante" accept=".pdf,.jpg,.jpeg,.png" required class="block w-full text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg cursor-pointer">
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-300">
                        <h3 class="font-bold text-lg text-gray-800 mb-4">Fiche Sanitaire de Liaison</h3>
                        
                        <div class="flex gap-4 mb-6">
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="mode_fiche_sanitaire" value="upload" checked class="peer hidden" onchange="toggleFicheMode()">
                                <div class="peer-checked:bg-blue-600 peer-checked:text-white bg-white border border-gray-300 rounded-lg p-4 text-center transition hover:bg-gray-50">
                                    📤 J'ai déjà la fiche (PDF)
                                </div>
                            </label>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="mode_fiche_sanitaire" value="create" class="peer hidden" onchange="toggleFicheMode()">
                                <div class="peer-checked:bg-blue-600 peer-checked:text-white bg-white border border-gray-300 rounded-lg p-4 text-center transition hover:bg-gray-50">
                                    ✍️ Créer en ligne (Modèle Officiel)
                                </div>
                            </label>
                        </div>

                        <div id="block_fiche_upload">
                            <label class="<?= $labelClass ?>">Importer le PDF signé</label>
                            <input type="file" name="file_fiche_sanitaire" accept=".pdf" class="block w-full text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg">
                        </div>

                        <div id="block_fiche_create" class="hidden bg-gray-100 p-6 rounded-xl border border-gray-300">
                            <p class="text-sm text-blue-800 mb-4 font-bold bg-blue-100 p-2 rounded">Renseignez ces informations pour générer le PDF officiel Cerfa.</p>
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div><label class="text-xs font-bold uppercase">Poids (kg)</label><input type="number" name="poids" step="0.1" class="<?= $inputClass ?> bg-white"></div>
                                <div><label class="text-xs font-bold uppercase">Taille (cm)</label><input type="number" name="taille" class="<?= $inputClass ?> bg-white"></div>
                            </div>

                            <h4 class="font-bold text-gray-700 text-sm uppercase border-b pb-1 mb-3">Vaccinations (Derniers rappels)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div><label class="text-xs font-bold">DTPolio (Date)</label><input type="date" name="vaccin_dtp" class="<?= $inputClass ?> bg-white"></div>
                                <div><label class="text-xs font-bold">BCG (Date ou 'Non')</label><input type="text" name="vaccin_bcg" placeholder="ex: Non" class="<?= $inputClass ?> bg-white"></div>
                                <div><label class="text-xs font-bold">Autre (Nom + Date)</label><input type="text" name="vaccin_autre" class="<?= $inputClass ?> bg-white"></div>
                            </div>

                            <h4 class="font-bold text-gray-700 text-sm uppercase border-b pb-1 mb-3">Médecin Traitant</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div><label class="text-xs font-bold">Nom Médecin</label><input type="text" name="medecin_nom" class="<?= $inputClass ?> bg-white"></div>
                                <div><label class="text-xs font-bold">Tél Cabinet</label><input type="tel" name="medecin_tel" class="<?= $inputClass ?> bg-white"></div>
                            </div>

                            <div class="bg-white p-4 rounded border border-gray-400 mt-6 shadow-sm">
                                <label class="block text-sm font-bold text-gray-800 mb-2 uppercase">Signature du Responsable Légal *</label>
                                <p class="text-xs text-gray-500 mb-2">Signez ci-dessous avec votre souris ou votre doigt.</p>
                                
                                <div class="border-2 border-dashed border-gray-400 rounded bg-gray-50 flex justify-center overflow-hidden">
                                    <canvas id="signature-pad" class="touch-none w-full" style="max-width: 600px; height: 200px;"></canvas>
                                </div>
                                
                                <div class="flex justify-between mt-2">
                                    <button type="button" id="clear-signature" class="text-xs text-red-600 font-bold hover:underline">Effacer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-b border-gray-200 pb-8">
                <h2 class="text-xl font-bold text-green-700 mb-6 flex items-center">
                    <span class="bg-green-600 text-white w-8 h-8 flex items-center justify-center rounded-full mr-3 text-sm shadow">3</span> Responsables Légaux
                </h2>

                <div class="mb-6 p-4 bg-green-50 rounded-lg border border-green-200">
                    <h3 class="font-bold text-gray-800 mb-4">Responsable 1 (Principal)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="<?= $labelClass ?>">Nom *</label><input type="text" name="resp1_nom" required class="<?= $inputClass ?>"></div>
                        <div><label class="<?= $labelClass ?>">Prénom *</label><input type="text" name="resp1_prenom" required class="<?= $inputClass ?>"></div>
                        <div><label class="<?= $labelClass ?>">Email *</label><input type="email" name="resp1_email" required class="<?= $inputClass ?>"></div>
                        <div><label class="<?= $labelClass ?>">Mobile *</label><input type="tel" name="resp1_tel" required class="<?= $inputClass ?>"></div>
                        
                        <div class="md:col-span-2">
                            <label class="<?= $labelClass ?>">Profession (CSP) *</label>
                            <select name="resp1_profession" class="<?= $inputClass ?>">
                                <?= renderProfessionOptions($professions) ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-white rounded-lg border border-gray-200">
                    <div class="flex justify-between items-center cursor-pointer" onclick="document.getElementById('resp2-form').classList.toggle('hidden')">
                        <h3 class="font-bold text-gray-600">Responsable 2 (Optionnel)</h3>
                        <span class="text-blue-600 text-sm hover:underline">Afficher/Masquer</span>
                    </div>
                    <div id="resp2-form" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div><label class="<?= $labelClass ?>">Nom</label><input type="text" name="resp2_nom" class="<?= $inputClass ?>"></div>
                        <div><label class="<?= $labelClass ?>">Prénom</label><input type="text" name="resp2_prenom" class="<?= $inputClass ?>"></div>
                        <div><label class="<?= $labelClass ?>">Email</label><input type="email" name="resp2_email" class="<?= $inputClass ?>"></div>
                        <div><label class="<?= $labelClass ?>">Mobile</label><input type="tel" name="resp2_tel" class="<?= $inputClass ?>"></div>
                        
                        <div class="md:col-span-2">
                            <label class="<?= $labelClass ?>">Profession (CSP)</label>
                            <select name="resp2_profession" class="<?= $inputClass ?>">
                                <?= renderProfessionOptions($professions) ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <h2 class="text-xl font-bold text-gray-700 mb-4">4. Validations</h2>
                <div class="space-y-3">
                    <label class="flex gap-2 cursor-pointer"><input type="checkbox" name="droit_image" class="mt-1 w-5 h-5"> <span>J'autorise la prise de vue (photos/vidéos) dans le cadre des activités.</span></label>
                    <label class="flex gap-2 cursor-pointer"><input type="checkbox" name="autorisation_contact" checked class="mt-1 w-5 h-5"> <span>J'autorise l'enregistrement de mes coordonnées.</span></label>
                    <label class="flex gap-2 cursor-pointer bg-blue-50 p-2 rounded border border-blue-200"><input type="checkbox" name="accord_parental" required class="mt-1 w-5 h-5"> <span class="font-bold">Je certifie avoir l'autorité parentale et l'exactitude des informations. *</span></label>
                    <label class="flex gap-2 cursor-pointer"><input type="checkbox" name="cgv_accepte" required class="mt-1 w-5 h-5"> <span>J'accepte les CGV. *</span></label>
                </div>
                <div class="mt-4 p-3 bg-gray-100 rounded w-fit">
                    <label class="flex items-center space-x-2"><input type="checkbox" required class="w-5 h-5 text-green-600"><span>Je ne suis pas un robot 🤖</span></label>
                </div>
            </div>

            <div class="pt-6 flex justify-end">
                <button type="button" onclick="submitForm()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg transform transition hover:scale-105">
                    Enregistrer l'enfant
                </button>
            </div>
        </form>
    </div>
</main>

<script>
// --- LOGIQUE TOGGLES ---
document.getElementById('check_allergies').addEventListener('change', function() {
    const el = document.getElementById('block_allergies');
    const inp = document.getElementById('input_infos_sante');
    if(this.checked) { el.classList.remove('hidden'); inp.required = true; } 
    else { el.classList.add('hidden'); inp.value = ''; inp.required = false; }
});
document.getElementById('check_regime').addEventListener('change', function() {
    const el = document.getElementById('block_regime');
    const inp = document.getElementById('input_regime');
    if(this.checked) { el.classList.remove('hidden'); inp.required = true; } 
    else { el.classList.add('hidden'); inp.value = ''; inp.required = false; }
});

// --- TOGGLE FICHE MODE ---
function toggleFicheMode() {
    const mode = document.querySelector('input[name="mode_fiche_sanitaire"]:checked').value;
    const up = document.getElementById('block_fiche_upload');
    const cr = document.getElementById('block_fiche_create');
    const fileIn = document.querySelector('input[name="file_fiche_sanitaire"]');
    
    if(mode === 'upload') {
        up.classList.remove('hidden');
        cr.classList.add('hidden');
        fileIn.required = true;
    } else {
        up.classList.add('hidden');
        cr.classList.remove('hidden');
        fileIn.required = false;
        resizeCanvas();
    }
}

// --- SIGNATURE PAD ---
const canvas = document.getElementById('signature-pad');
const signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgb(255, 255, 255)' });

document.getElementById('clear-signature').addEventListener('click', () => signaturePad.clear());

function resizeCanvas() {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    canvas.width = canvas.offsetWidth * ratio;
    canvas.height = canvas.offsetHeight * ratio;
    canvas.getContext("2d").scale(ratio, ratio);
    signaturePad.clear(); 
}
window.addEventListener("resize", resizeCanvas);

// --- SUBMIT ---
function submitForm() {
    const mode = document.querySelector('input[name="mode_fiche_sanitaire"]:checked').value;
    
    if (mode === 'create') {
        if (signaturePad.isEmpty()) {
            alert("Merci de signer la fiche sanitaire dans le cadre prévu.");
            return;
        }
        document.getElementById('signature_data').value = signaturePad.toDataURL('image/png');
    }
    
    // Vérification HTML5 standard
    if(document.getElementById('childForm').reportValidity()) {
        document.getElementById('childForm').submit();
    }
}

// Init
toggleFicheMode();
</script>

<?php if (file_exists('partials/footer.php')) include 'partials/footer.php'; ?>