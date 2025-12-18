<?php
/**
 * Page : modif_child.php
 * Objectif : Permettre au parent de modifier les informations de son enfant
 */

require_once 'api/config.php';
require_once 'partials/header.php';

// 1. Sécurité : Vérification de connexion
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$child_id = $_GET['id'] ?? null;
$parent_id = $_SESSION['user']['id'];
$message = '';
$message_type = ''; // 'success' ou 'error'

if (!$child_id) {
    header('Location: reservations.php');
    exit;
}

// 2. Traitement du formulaire (Mise à jour)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $date_naissance = $_POST['date_naissance'] ?? '';
    $sexe = $_POST['sexe'] ?? '';
    
    $taille = trim($_POST['taille'] ?? '');
    $poids = trim($_POST['poids'] ?? '');
    $infos_sante = trim($_POST['infos_sante'] ?? '');
    $allergies = trim($_POST['allergies'] ?? '');
    $regime = trim($_POST['regime_alimentaire'] ?? '');
    
    $medecin_nom = trim($_POST['medecin_nom'] ?? '');
    $medecin_tel = trim($_POST['medecin_tel'] ?? '');

    // Validation basique
    if (empty($prenom) || empty($nom) || empty($date_naissance)) {
        $message = "Les champs Prénom, Nom et Date de naissance sont obligatoires.";
        $message_type = 'error';
    } else {
        try {
            // Requête de mise à jour sécurisée
            $sql = "UPDATE enfants SET 
                    prenom = ?, nom = ?, date_naissance = ?, sexe = ?,
                    taille = ?, poids = ?, infos_sante = ?, allergies = ?, regime_alimentaire = ?,
                    medecin_nom = ?, medecin_tel = ?
                    WHERE id = ? AND parent_id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $prenom, $nom, $date_naissance, $sexe,
                $taille, $poids, $infos_sante, $allergies, $regime,
                $medecin_nom, $medecin_tel,
                $child_id, $parent_id
            ]);

            $message = "Les informations ont été mises à jour avec succès.";
            $message_type = 'success';
            
            // On peut rediriger vers la page précédente si on veut, ou rester ici
            // header("Location: info_inscrit.php?child_id=$child_id..."); 

        } catch (Exception $e) {
            $message = "Erreur lors de la mise à jour : " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// 3. Récupération des données actuelles de l'enfant
try {
    $stmt = $pdo->prepare("SELECT * FROM enfants WHERE id = ? AND parent_id = ?");
    $stmt->execute([$child_id, $parent_id]);
    $enfant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$enfant) {
        die('<div class="container mx-auto py-12 text-center text-red-600 font-bold">Enfant introuvable ou accès refusé.</div>');
    }
} catch (Exception $e) {
    die("Erreur de chargement.");
}
?>

<main class="bg-gray-50 min-h-screen py-10">
    <div class="container mx-auto max-w-4xl px-4">
        
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Modifier le profil</h1>
                <p class="text-gray-500 text-sm">Mettez à jour les informations de <?= htmlspecialchars($enfant['prenom']) ?></p>
            </div>
            <a href="javascript:history.back()" class="text-gray-600 hover:text-blue-600 font-medium flex items-center transition">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour
            </a>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $message_type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Informations Générales
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prénom</label>
                        <input type="text" name="prenom" value="<?= htmlspecialchars($enfant['prenom']) ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                        <input type="text" name="nom" value="<?= htmlspecialchars($enfant['nom']) ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date de naissance</label>
                        <input type="date" name="date_naissance" value="<?= htmlspecialchars($enfant['date_naissance']) ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sexe</label>
                        <select name="sexe" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="Homme" <?= $enfant['sexe'] === 'Homme' ? 'selected' : '' ?>>Garçon</option>
                            <option value="Femme" <?= $enfant['sexe'] === 'Femme' ? 'selected' : '' ?>>Fille</option>
                            <option value="Autre" <?= $enfant['sexe'] === 'Autre' ? 'selected' : '' ?>>Autre</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    Santé & Informations Physiques
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Taille (cm)</label>
                        <input type="number" name="taille" value="<?= htmlspecialchars($enfant['taille'] ?? '') ?>" placeholder="ex: 145" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Poids (kg)</label>
                        <input type="text" name="poids" value="<?= htmlspecialchars($enfant['poids'] ?? '') ?>" placeholder="ex: 35" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Problèmes de santé / Traitements</label>
                        <textarea name="infos_sante" rows="3" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Rien à signaler..."><?= htmlspecialchars($enfant['infos_sante'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Allergies</label>
                        <textarea name="allergies" rows="2" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Aucune..."><?= htmlspecialchars($enfant['allergies'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Régime Alimentaire</label>
                        <input type="text" name="regime_alimentaire" value="<?= htmlspecialchars($enfant['regime_alimentaire'] ?? '') ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="ex: Sans porc, Végétarien...">
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Médecin Traitant
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom du médecin</label>
                        <input type="text" name="medecin_nom" value="<?= htmlspecialchars($enfant['medecin_nom'] ?? '') ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Dr. Dupont">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone médecin</label>
                        <input type="tel" name="medecin_tel" value="<?= htmlspecialchars($enfant['medecin_tel'] ?? '') ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-4 pt-4">
                <a href="javascript:history.back()" class="px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition">Annuler</a>
                <button type="submit" class="px-6 py-2.5 rounded-lg bg-blue-600 text-white font-bold shadow hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition">
                    Enregistrer les modifications
                </button>
            </div>

        </form>
    </div>
</main>

<?php require_once 'partials/footer.php'; ?>