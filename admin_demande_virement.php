<?php
// admin_demande_virement.php

// 1. CONFIG
require_once 'api/config.php';

// 2. SÉCURITÉ - STRICT ADMIN CHECK
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_admin'])) {
    header('Location: alerte.html'); // Redirection sécurisée
    exit;
}

// 3. LOGIQUE
$error = null;
$message = null;
$virements_en_attente = [];
$virements_passes = [];

try {
    // --- Logique de validation manuelle ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'valider_virement') {
        $virementToken = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_STRING);

        if ($virementToken) {
            $updateSql = "UPDATE virements SET effectue = 1, date_virement_effectue = NOW() WHERE token = ? AND effectue = 0";
            $stmtUpdate = $pdo->prepare($updateSql);
            $stmtUpdate->execute([$virementToken]);

            if ($stmtUpdate->rowCount()) {
                $message = "Le virement (Token: " . substr($virementToken, 0, 10) . "...) a été marqué comme effectué.";
            } else {
                $error = "Erreur: Le virement n'a pas pu être mis à jour (déjà effectué ou token invalide).";
            }
        }
    }

    // A. Récupérer les virements EN ATTENTE (Priorité : les plus anciens d'abord)
    $stmtAttente = $pdo->query("
        SELECT v.*, o.nom as organisateur_nom 
        FROM virements v
        JOIN organisateurs o ON v.organisateur_id = o.id
        WHERE v.effectue = 0
        ORDER BY v.date_demande ASC
    ");
    $virements_en_attente = $stmtAttente->fetchAll(PDO::FETCH_ASSOC);

    // B. Récupérer les virements PASSÉS (historique)
    $stmtPasses = $pdo->query("
        SELECT v.*, o.nom as organisateur_nom 
        FROM virements v
        JOIN organisateurs o ON v.organisateur_id = o.id
        WHERE v.effectue = 1
        ORDER BY v.date_virement_effectue DESC
        LIMIT 50
    ");
    $virements_passes = $stmtPasses->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = "Erreur SQL : " . $e->getMessage();
}

// 4. AFFICHAGE HTML
require_once 'partials/header.php';
?>

<title>Admin - Gestion des Virements</title>

<div class="min-h-screen bg-gray-50 py-10 font-sans">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <h1 class="text-3xl font-extrabold text-[#0A112F]">Gestion des Demandes de Virement</h1>
            
            <a href="admin_commission_stats.php" class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0h2a2 2 0 002-2v-6a2 2 0 00-2-2h-2a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                Statistiques des Commissions
            </a>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Erreur</p>
                <p><?= $error ?></p>
            </div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Succès</p>
                <p><?= $message ?></p>
            </div>
        <?php endif; ?>

        <h2 class="text-2xl font-bold text-[#0A112F] mt-10 mb-4 border-b pb-2">À Traiter (<?= count($virements_en_attente) ?> en attente)</h2>

        <?php if (empty($virements_en_attente)): ?>
            <div class="bg-white rounded-xl shadow-sm p-8 text-center border border-gray-100 mb-10">
                <p class="text-green-600 font-semibold">✅ Aucune demande de virement en attente de traitement. Le travail est fait !</p>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-md overflow-x-auto mb-10">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-yellow-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-yellow-800 uppercase tracking-wider">Demande du</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-yellow-800 uppercase tracking-wider">Organisme / Demandeur</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-yellow-800 uppercase tracking-wider">Montant Net / Brut</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-yellow-800 uppercase tracking-wider">IBAN / BIC</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Action</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($virements_en_attente as $virement): ?>
                        <tr class="<?= (strtotime($virement['date_demande']) < strtotime('-7 days')) ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-gray-50' ?>">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm font-medium text-gray-900"><?= date('d/m/Y', strtotime($virement['date_demande'])) ?></p>
                                <p class="text-xs text-gray-500">Estimé : <?= date('d/m/Y', strtotime($virement['date_virement_estime'])) ?></p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <p class="font-bold"><?= htmlspecialchars($virement['organisateur_nom']) ?></p>
                                <p class="text-xs text-gray-600"><?= htmlspecialchars($virement['prenom_user'] . ' ' . $virement['nom_user']) ?></p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <p class="font-bold text-green-700"><?= number_format($virement['montant_apres_commission'], 2, ',', ' ') ?>€ (Net)</p>
                                <p class="text-xs text-gray-500"><?= number_format($virement['montant_total'], 2, ',', ' ') ?>€ (Brut)</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono">
                                <p class="text-gray-900 break-all"><?= htmlspecialchars($virement['iban']) ?></p>
                                <p class="text-xs text-gray-600"><?= htmlspecialchars($virement['bic_swift']) ?></p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="virement.php?t=<?= $virement['token'] ?>" target="_blank" class="text-blue-600 hover:text-blue-900 mr-3">Voir Détails</a>
                                
                                <form method="POST" class="inline-block" onsubmit="return confirm('Confirmez-vous que vous avez effectué le virement (<?= number_format($virement['montant_apres_commission'], 2, ',', ' ') ?>€) ?')">
                                    <input type="hidden" name="action" value="valider_virement">
                                    <input type="hidden" name="token" value="<?= $virement['token'] ?>">
                                    <button type="submit" class="bg-green-600 text-white py-1 px-3 rounded text-xs font-bold hover:bg-green-700 transition">
                                        Valider Manuellement
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h2 class="text-2xl font-bold text-[#0A112F] mt-10 mb-4 border-b pb-2">Historique des 50 derniers Virements Effectués</h2>
        
        <?php if (empty($virements_passes)): ?>
            <div class="bg-white rounded-xl shadow-sm p-8 text-center border border-gray-100">
                <p class="text-gray-500">Aucun historique de virement effectué.</p>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-md overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Effectué le</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Demande du</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Organisme</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant Net</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Détails</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($virements_passes as $virement): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-700">
                                <?= date('d/m/Y', strtotime($virement['date_virement_effectue'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('d/m/Y', strtotime($virement['date_demande'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($virement['organisateur_nom']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold">
                                <?= number_format($virement['montant_apres_commission'], 2, ',', ' ') ?>€
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="virement.php?t=<?= $virement['token'] ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900">Détails</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?>