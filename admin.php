<?php
// 1. D'ABORD LA CONFIG (Session start est dedans)
require_once 'api/config.php';

// 2. SÉCURITÉ
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_admin'])) {
    header('Location: index');
    exit;
}

// 3. LOGIQUE
$error = null;

try {
    // Récupérer le nombre de demandes d'accès directeur en attente
    $stmtDirectors = $pdo->query("SELECT COUNT(*) FROM users WHERE is_directeur = 0 AND demande_en_cours = 1");
    $countDirectors = $stmtDirectors->fetchColumn();

    // Récupérer le nombre de demandes de camps en attente
    $stmtCamps = $pdo->query("SELECT COUNT(*) FROM camps WHERE en_attente = 1 AND valide = 0 AND refuse = 0");
    $countCamps = $stmtCamps->fetchColumn();
    
    // Récupérer le nombre de demandes de virement en attente (NOUVEAU)
    $stmtVirements = $pdo->query("SELECT COUNT(*) FROM virements WHERE effectue = 0");
    $countVirements = $stmtVirements->fetchColumn();

} catch (Exception $e) {
    $error = "Erreur SQL : " . $e->getMessage();
}

// 4. AFFICHAGE HTML
require_once 'partials/header.php';
?>

<title>Tableau de bord Administrateur</title>

<div class="min-h-screen bg-gray-50 py-10 font-sans">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-extrabold text-[#0A112F] mb-8">Tableau de bord Administrateur</h1>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Attention</p>
                <p><?= $error ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            <a href="admin_requests" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-300 border border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900">Demandes Directeurs</h2>
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <p class="text-4xl font-extrabold text-[#0A112F] mt-4"><?= $countDirectors ?></p>
                <p class="text-gray-500 mt-1">Demandes d'accès à valider</p>
            </a>

            <a href="admin_camp_requests" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-300 border border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900">Validation des Camps</h2>
                    <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <p class="text-4xl font-extrabold text-[#0A112F] mt-4"><?= $countCamps ?></p>
                <p class="text-gray-500 mt-1">Camps en attente de modération</p>
            </a>
            
            <a href="admin_demande_virement" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-300 border border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900">Demandes de Virement</h2>
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <p class="text-4xl font-extrabold text-[#0A112F] mt-4"><?= $countVirements ?></p>
                <p class="text-gray-500 mt-1">Demandes de virement à traiter</p>
            </a>
            
            <div class="lg:col-span-3">
                <h2 class="text-2xl font-bold text-[#0A112F] mt-10 mb-4 border-b pb-2">Historique et Rapports</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <a href="admin_history_accepted" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-300 border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-700">Camps Publiés (Historique)</h3>
                        <p class="text-sm text-gray-500 mt-1">Voir tous les camps qui ont été acceptés.</p>
                    </a>
                    <a href="admin_history_refused" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-300 border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-700">Camps Refusés (Historique)</h3>
                        <p class="text-sm text-gray-500 mt-1">Voir tous les camps qui ont été refusés.</p>
                    </a>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?>