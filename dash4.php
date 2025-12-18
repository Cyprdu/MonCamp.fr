<?php
// dashboard_organisme.php

// 1. CONFIG
require_once 'api/config.php';

// 2. SÉCURITÉ
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_directeur'])) {
    header('Location: index.php');
    exit;
}

// 3. LOGIQUE
$organisateurId = filter_input(INPUT_GET, 'organisateur_id', FILTER_VALIDATE_INT);
$userId = $_SESSION['user']['id'];
$organisateur = null;
$camps = [];
$virements = []; 
$error = null;
$logo_url = 'assets/default_logo.png';

if (!$organisateurId) {
    $error = "ID d'organisme non spécifié.";
} else {
    try {
        // A. Récupérer l'organisme
        $stmtOrga = $pdo->prepare("SELECT * FROM organisateurs WHERE id = ? AND user_id = ?");
        $stmtOrga->execute([$organisateurId, $userId]);
        $organisateur = $stmtOrga->fetch(PDO::FETCH_ASSOC);

        if ($organisateur) {
            // B. Camps
            $sqlCamps = "SELECT c.*, (SELECT COUNT(*) FROM inscriptions WHERE camp_id = c.id) as nb_inscrits 
                         FROM camps c WHERE c.organisateur_id = ? ORDER BY c.date_debut DESC";
            $stmtCamps = $pdo->prepare($sqlCamps);
            $stmtCamps->execute([$organisateurId]);
            $camps = $stmtCamps->fetchAll(PDO::FETCH_ASSOC);

            // C. Stats
            $stats = [
                'total_camps' => count($camps),
                'total_inscrits' => array_sum(array_column($camps, 'nb_inscrits')), 
                'solde_disponible' => floatval($organisateur['portefeuille'])
            ];
            
            // D. Historique Virements
            $stmtVirements = $pdo->prepare("SELECT * FROM virements WHERE organisateur_id = ? ORDER BY date_demande DESC");
            $stmtVirements->execute([$organisateurId]);
            $virements = $stmtVirements->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($organisateur['logo_url'])) {
                $logo_url = htmlspecialchars($organisateur['logo_url']);
            }
            
            // Gestion erreur URL
            if (isset($_GET['error'])) $error = htmlspecialchars($_GET['error']);
            if (isset($_GET['success'])) $success = htmlspecialchars($_GET['success']);

        } else {
            $error = "Organisme introuvable.";
        }

    } catch (Exception $e) {
        $error = "Erreur SQL : " . $e->getMessage();
    }
}

// Vérifier si le compte Stripe est actif (Identité vérifiée)
$isStripeReady = !empty($organisateur['stripe_account_id']);

require_once 'partials/header.php';
?>

<title>Dashboard - <?= htmlspecialchars($organisateur['nom'] ?? '') ?></title>

<div class="min-h-screen bg-gray-50 py-10 font-sans">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Erreur</p>
                <p><?= $error ?></p>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Succès</p>
                <p><?= $success ?></p>
            </div>
        <?php endif; ?>

        <?php if ($organisateur): ?>
            <div class="mb-8">
                <a href="public_infos.php" class="text-gray-500 hover:text-[#0A112F] inline-flex items-center mb-6 transition">
                    &larr; Retour à la sélection
                </a>
                
                <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <img src="<?= $logo_url ?>" class="w-16 h-16 object-contain rounded-full border p-1 bg-white">
                        <div>
                            <h1 class="text-3xl font-extrabold text-[#0A112F]">Dashboard : <?= htmlspecialchars($organisateur['nom']) ?></h1>
                            
                            <?php if ($isStripeReady): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mt-1">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    Identité vérifiée (Virements actifs)
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 mt-1">
                                    Identité non vérifiée
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-3 mt-4 md:mt-0">
                        <a href="edit_organisateur.php?id=<?= $organisateurId ?>" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Modifier infos
                        </a>
                        
                        <?php if (!$isStripeReady): ?>
                            <a href="verify_identity.php?organisateur_id=<?= $organisateurId ?>" 
                               class="inline-flex items-center justify-center gap-2 bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition animate-pulse">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Ajouter documents d'identité
                            </a>
                        <?php else: ?>
                            <?php if ($stats['solde_disponible'] > 0): ?>
                                <a href="demande_de_virement_info.php?organisateur_id=<?= $organisateurId ?>" 
                                   class="inline-flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition hover:-translate-y-0.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    Virer l'argent (<?= number_format($stats['solde_disponible'], 2, ',', ' ') ?>€)
                                </a>
                            <?php else: ?>
                                <button disabled class="bg-gray-400 text-white font-bold py-3 px-6 rounded-xl cursor-not-allowed">
                                    Solde : 0.00€
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-green-500">
                    <p class="text-sm text-gray-500">Solde Disponible</p>
                    <p class="text-3xl font-bold text-green-700"><?= number_format($stats['solde_disponible'], 2) ?>€</p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-[#0A112F]">
                    <p class="text-sm text-gray-500">Séjours</p>
                    <p class="text-3xl font-bold text-[#0A112F]"><?= $stats['total_camps'] ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-blue-500">
                    <p class="text-sm text-gray-500">Inscrits</p>
                    <p class="text-3xl font-bold text-blue-700"><?= $stats['total_inscrits'] ?></p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 mb-10">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Historique des Virements</h2>
                <?php if (empty($virements)): ?>
                    <p class="text-gray-500 italic">Aucun virement effectué.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-500 uppercase font-medium">
                                <tr>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Montant Net</th>
                                    <th class="px-4 py-3">Statut</th>
                                    <th class="px-4 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($virements as $v): ?>
                                <tr>
                                    <td class="px-4 py-3"><?= date('d/m/Y', strtotime($v['date_demande'])) ?></td>
                                    <td class="px-4 py-3 font-bold text-green-700"><?= number_format($v['montant_apres_commission'], 2) ?>€</td>
                                    <td class="px-4 py-3">
                                        <?php if($v['effectue']): ?>
                                            <span class="px-2 py-1 rounded-full bg-green-100 text-green-800 text-xs">Effectué</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 rounded-full bg-yellow-100 text-yellow-800 text-xs">En cours</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="virement.php?t=<?= $v['token'] ?>" class="text-blue-600 hover:underline">Reçu</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <h2 class="text-xl font-bold text-gray-800 mb-4">Séjours</h2>
            <?php if (empty($camps)): ?>
                <div class="bg-white p-8 rounded-xl text-center border">
                    <p class="text-gray-500">Aucun séjour.</p>
                    <a href="create_camp.php" class="text-blue-600 font-bold hover:underline">Créer un séjour</a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($camps as $camp): 
                        $img = !empty($camp['image_url']) ? htmlspecialchars($camp['image_url']) : 'assets/default_camp.jpg'; 
                    ?>
                    <div class="bg-white rounded-lg shadow border overflow-hidden">
                        <img src="<?= $img ?>" class="w-full h-32 object-cover">
                        <div class="p-4">
                            <h3 class="font-bold text-gray-900 truncate"><?= htmlspecialchars($camp['nom']) ?></h3>
                            <p class="text-gray-500 text-sm"><?= htmlspecialchars($camp['ville']) ?></p>
                            <a href="gestion_camp.php?t=<?= $camp['token'] ?>" class="block mt-3 text-center bg-gray-100 text-gray-700 py-2 rounded hover:bg-gray-200">Gérer</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?>