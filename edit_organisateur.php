<?php
// 1. CONFIG
require_once 'api/config.php';

// 2. SÉCURITÉ
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_directeur'])) {
    header('Location: index.php');
    exit;
}

// 3. LOGIQUE - Récupération des données existantes
$organisateurId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?? filter_input(INPUT_POST, 'organisateur_id', FILTER_VALIDATE_INT);
$userId = $_SESSION['user']['id'];
$organisateur = null;
$message = [];
$error = null;

if (!$organisateurId) {
    $error = "ID d'organisme non spécifié.";
} else {
    try {
        // Fetch Organisateur Data
        $stmt = $pdo->prepare("SELECT * FROM organisateurs WHERE id = ? AND user_id = ?");
        $stmt->execute([$organisateurId, $userId]);
        $organisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$organisateur) {
            $error = "Organisme introuvable ou vous n'êtes pas autorisé à le modifier.";
        }

    } catch (Exception $e) {
        $error = "Erreur SQL : " . $e->getMessage();
    }
}

// 4. LOGIQUE - Traitement du formulaire (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $organisateur && $organisateurId) {
    // Récupérer et valider les données du formulaire
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tel = trim($_POST['tel'] ?? '');
    $web = trim($_POST['web'] ?? '');
    $iban = trim($_POST['iban'] ?? '');
    $bic_swift = trim($_POST['bic_swift'] ?? '');
    $adresse_complete = trim($_POST['adresse_complete'] ?? '');
    $code_postal_orga = trim($_POST['code_postal_orga'] ?? '');
    $ville_orga = trim($_POST['ville_orga'] ?? '');
    $pays_orga = trim($_POST['pays_orga'] ?? '');
    $statut_legal = trim($_POST['statut_legal'] ?? '');
    
    // Garder l'URL du logo existant par défaut
    $logo_url = $organisateur['logo_url']; 

    // --- Gestion de l'upload de Logo (CORRIGÉ) ---
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        
        $upload_dir = 'uploads/logos/'; 
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        $file_name = $_FILES['logo_file']['name'];
        $file_tmp_name = $_FILES['logo_file']['tmp_name'];
        $file_size = $_FILES['logo_file']['size'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validation basique
        if (!in_array($file_extension, $allowed_extensions)) {
            $message['error'] = "Format de fichier non autorisé. Utilisez JPG, PNG ou GIF.";
        } elseif ($file_size > 2000000) { // Max 2MB
            $message['error'] = "Le fichier est trop volumineux (max 2MB).";
        } else {
            // Créer le dossier s'il n'existe pas
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    $message['error'] = "Impossible de créer le répertoire d'upload.";
                }
            }

            if (!isset($message['error'])) {
                // Générer un nom unique
                $new_file_name = uniqid('logo_') . '.' . $file_extension;
                $target_file = $upload_dir . $new_file_name;
                
                // Déplacer le fichier
                if (move_uploaded_file($file_tmp_name, $target_file)) {
                    // Succès : mise à jour de l'URL du logo
                    $logo_url = $target_file;
                    
                    // OPTIONNEL : Supprimer l'ancien logo si un nouveau est uploadé
                    if (!empty($organisateur['logo_url']) && file_exists($organisateur['logo_url'])) {
                        unlink($organisateur['logo_url']);
                    }

                } else {
                    $message['error'] = "Erreur lors du déplacement du fichier sur le serveur.";
                }
            }
        }
    }
    // -----------------------------------------------------------------------------------

    if (empty($message)) {
        try {
            $updateSql = "UPDATE organisateurs SET 
                            nom = ?, email = ?, tel = ?, web = ?, 
                            logo_url = ?, iban = ?, bic_swift = ?, 
                            adresse_complete = ?, code_postal_orga = ?, 
                            ville_orga = ?, pays_orga = ?, statut_legal = ?
                          WHERE id = ? AND user_id = ?";
            
            $stmtUpdate = $pdo->prepare($updateSql);
            $stmtUpdate->execute([
                $nom, $email, $tel, $web, $logo_url, 
                $iban, $bic_swift, $adresse_complete, $code_postal_orga, 
                $ville_orga, $pays_orga, $statut_legal,
                $organisateurId, $userId
            ]);

            $message['success'] = "Les informations de l'organisme ont été mises à jour avec succès.";
            
            // Recharger les données pour afficher les nouvelles valeurs
            $stmt = $pdo->prepare("SELECT * FROM organisateurs WHERE id = ? AND user_id = ?");
            $stmt->execute([$organisateurId, $userId]);
            $organisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $message['error'] = "Erreur SQL lors de la mise à jour : " . $e->getMessage();
        }
    }
}

// 5. AFFICHAGE HTML
require_once 'partials/header.php';
?>

<title>Modifier l'Organisme - <?= htmlspecialchars($organisateur['nom'] ?? 'Chargement...') ?></title>

<div class="min-h-screen bg-gray-50 py-10 font-sans">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex items-center gap-4 mb-8">
            <a href="dashboard_organisme.php?organisateur_id=<?= $organisateurId ?>" class="text-gray-500 hover:text-[#0A112F]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <h1 class="text-3xl font-extrabold text-[#0A112F]">Modification de l'Organisme</h1>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Erreur</p>
                <p><?= $error ?></p>
            </div>
        <?php elseif ($organisateur): ?>

            <?php if (isset($message['success'])): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
                    <p class="font-bold">Succès</p>
                    <p><?= $message['success'] ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($message['error'])): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                    <p class="font-bold">Erreur</p>
                    <p><?= $message['error'] ?></p>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-lg p-8">
                <form action="edit_organisateur.php?id=<?= $organisateurId ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="organisateur_id" value="<?= $organisateurId ?>">

                    <fieldset class="border-b border-gray-200 pb-8 mb-8">
                        <legend class="text-xl font-bold text-gray-900 mb-4">Informations Générales</legend>
                        
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            
                            <div>
                                <label for="logo_file" class="block text-sm font-medium text-gray-700">Logo de l'organisme</label>
                                <div class="mt-1 flex items-center">
                                    <?php if (!empty($organisateur['logo_url'])): ?>
                                        <img src="<?= htmlspecialchars($organisateur['logo_url']) ?>" alt="Logo actuel" class="w-16 h-16 object-contain mr-4 border rounded-lg">
                                    <?php endif; ?>
                                    <input type="file" name="logo_file" id="logo_file" accept="image/*" class="block w-full text-sm text-gray-500
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-full file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-blue-50 file:text-[#0A112F]
                                        hover:file:bg-blue-100">
                                </div>
                                <p class="mt-2 text-xs text-gray-500">Formats acceptés : PNG, JPG, GIF. Max 2MB.</p>
                            </div>
                            
                            <div>
                                <label for="statut_legal" class="block text-sm font-medium text-gray-700">Statut Légal (Ex: Association Loi 1901)</label>
                                <input type="text" name="statut_legal" id="statut_legal" value="<?= htmlspecialchars($organisateur['statut_legal'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0A112F] focus:ring-[#0A112F] p-2">
                            </div>

                            <div class="sm:col-span-2">
                                <label for="nom" class="block text-sm font-medium text-gray-700">Nom de l'Organisme</label>
                                <input type="text" name="nom" id="nom" value="<?= htmlspecialchars($organisateur['nom'] ?? '') ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0A112F] focus:ring-[#0A112F] p-2">
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email de contact</label>
                                <input type="email" name="email" id="email" value="<?= htmlspecialchars($organisateur['email'] ?? '') ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0A112F] focus:ring-[#0A112F] p-2">
                            </div>

                            <div>
                                <label for="tel" class="block text-sm font-medium text-gray-700">Téléphone</label>
                                <input type="tel" name="tel" id="tel" value="<?= htmlspecialchars($organisateur['tel'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0A112F] focus:ring-[#0A112F] p-2">
                            </div>

                            <div class="sm:col-span-2">
                                <label for="web" class="block text-sm font-medium text-gray-700">Site Web / URL</label>
                                <input type="url" name="web" id="web" value="<?= htmlspecialchars($organisateur['web'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0A112F] focus:ring-[#0A112F] p-2">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="border-b border-gray-200 pb-8 mb-8">
                        <legend class="text-xl font-bold text-gray-900 mb-4">Informations Bancaires (RIB/IBAN)</legend>

                        <p class="text-sm text-gray-500 mb-4">Ces informations sont utilisées pour le versement des fonds collectés pour vos séjours.</p>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="iban" class="block text-sm font-medium text-gray-700">IBAN (Pour les virements)</label>
                                <input type="text" name="iban" id="iban" value="<?= htmlspecialchars($organisateur['iban'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0A112F] focus:ring-[#0A112F] p-2" maxlength="34">
                            </div>

                            <div>
                                <label for="bic_swift" class="block text-sm font-medium text-gray-700">BIC / SWIFT</label>
                                <input type="text" name="bic_swift" id="bic_swift" value="<?= htmlspecialchars($organisateur['bic_swift'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0A112F] focus:ring-[#0A112F] p-2" maxlength="11">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-xl font-bold text-gray-900 mb-4">Adresse Postale Administrative</legend>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="adresse_complete" class="block text-sm font-medium text-gray-700">Adresse (Ligne 1)</label>
                                <input type="text" name="adresse_complete" id="adresse_complete" value="<?= htmlspecialchars($organisateur['adresse_complete'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0A112F] focus:ring-[#0A112F] p-2">
                            </div>

                            <div>
                                <label for="code_postal_orga" class="block text-sm font-medium text-gray-700">Code Postal</label>
                                <input type="text" name="code_postal_orga" id="code_postal_orga" value="<?= htmlspecialchars($organisateur['code_postal_orga'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0A112F] focus:ring-[#0A112F] p-2">
                            </div>

                            <div>
                                <label for="ville_orga" class="block text-sm font-medium text-gray-700">Ville</label>
                                <input type="text" name="ville_orga" id="ville_orga" value="<?= htmlspecialchars($organisateur['ville_orga'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0A112F] focus:ring-[#0A112F] p-2">
                            </div>

                            <div class="sm:col-span-2">
                                <label for="pays_orga" class="block text-sm font-medium text-gray-700">Pays</label>
                                <input type="text" name="pays_orga" id="pays_orga" value="<?= htmlspecialchars($organisateur['pays_orga'] ?? 'France') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0A112F] focus:ring-[#0A112F] p-2">
                            </div>
                        </div>
                    </fieldset>

                    <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end">
                        <button type="submit" class="inline-flex justify-center rounded-xl border border-transparent bg-[#0A112F] py-3 px-6 text-sm font-medium text-white shadow-sm hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-[#0A112F] focus:ring-offset-2 transition">
                            Sauvegarder les modifications
                        </button>
                    </div>
                </form>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?>