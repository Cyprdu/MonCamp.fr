<?php
// 1. D'ABORD LA CONFIG
require_once 'api/config.php';

// 2. SÉCURITÉ
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_directeur'])) {
    header('Location: index.php');
    exit;
}

// 3. LOGIQUE MÉTIER
$userId = $_SESSION['user']['id'];
$organisateurs = [];
$error = null;

// URL de l'icône de paramètre par défaut (si aucun logo n'est trouvé)
$default_logo_url = 'https://media.istockphoto.com/id/1153439787/fr/vectoriel/r%C3%A9glage-engrenage-outil-cog-isol%C3%A9-web-plat-mobile-ic%C3%B4ne-vecteur-signe-symbole-bouton.jpg?s=612x612&w=0&k=20&c=SlqZq-0LEqhx4e9vMIdoXcO_c_R637LajnZDXr6lCno=';

try {
    // Récupérer TOUS les organismes liés à l'utilisateur, y compris le logo_url
    $stmtOrga = $pdo->prepare("SELECT id, nom, email, tel, web, portefeuille, logo_url FROM organisateurs WHERE user_id = ? ORDER BY nom ASC");
    $stmtOrga->execute([$userId]);
    $organisateurs = $stmtOrga->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = "Erreur SQL : " . $e->getMessage();
}

// 4. AFFICHAGE HTML
require_once 'partials/header.php';
?>

<title>Sélection de l'Organisme</title>

<div class="min-h-screen bg-gray-50 py-10 font-sans">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <a href="organisateurs.php" class="text-gray-500 hover:text-[#0A112F] inline-flex items-center mb-6 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour aux outils organisateur
        </a>

        <div class="mb-10 text-center">
            <h1 class="text-3xl font-extrabold text-[#0A112F]">Choisissez l'organisme à gérer</h1>
            <p class="text-gray-500 mt-1">Vous êtes directeur pour plusieurs structures. Sélectionnez celle que vous souhaitez administrer.</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Attention</p>
                <p><?= $error ?></p>
            </div>
        <?php endif; ?>

        <?php if (empty($organisateurs)): ?>
            <div class="bg-white rounded-3xl shadow-sm p-12 text-center border border-gray-100">
                <h3 class="text-xl font-bold text-gray-900 mb-2">Aucun organisme trouvé</h3>
                <p class="text-gray-500 mb-6">Votre profil est marqué comme directeur, mais aucun organisme ne vous est rattaché.</p>
                <a href="create_organisateur.php" class="text-[#0A112F] font-bold hover:underline">Créer un organisme &rarr;</a>
            </div>
        <?php else: ?>
            
            <div class="space-y-4">
                <?php foreach ($organisateurs as $orga): ?>
                    <?php 
                        // Détermine l'URL du logo à afficher
                        $orga_logo = !empty($orga['logo_url']) ? htmlspecialchars($orga['logo_url']) : $default_logo_url;
                        $dashboard_url = "dashboard_organisme.php?organisateur_id=" . $orga['id']; 
                    ?>
                    
                    <a href="<?= $dashboard_url ?>" class="block bg-white rounded-xl shadow-md hover:shadow-lg transition transform hover:-translate-y-0.5 border border-gray-200">
                        <div class="p-5 flex items-center justify-between">
                            
                            <div class="flex items-center gap-4">
                                <img src="<?= $orga_logo ?>" alt="Logo" class="w-10 h-10 object-contain rounded-full border border-gray-200 p-0.5">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($orga['nom']) ?></h3>
                                    <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($orga['email']) ?></p>
                                </div>
                            </div>
                            
                            <div class="text-right">
                                <span class="text-2xl font-extrabold text-[#0A112F]">
                                    <?= number_format($orga['portefeuille'], 2, ',', ' ') ?>€
                                </span>
                                <p class="text-xs text-gray-400">Solde du portefeuille</p>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?>