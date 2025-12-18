<?php
// 1. D'ABORD LA CONFIG
require_once 'api/config.php';

// 2. SÉCURITÉ
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_directeur'])) {
    header('Location: index');
    exit;
}

// 3. LOGIQUE MÉTIER
$organisateurId = filter_input(INPUT_GET, 'organisateur_id', FILTER_VALIDATE_INT);
$userId = $_SESSION['user']['id'];
$organisateur = null;
$camps = [];
$virements = []; 
$error = null;
$logo_url = 'assets/default_logo.png'; // Logo par défaut

if (!$organisateurId) {
    $error = "ID d'organisme non spécifié.";
} else {
    try {
        // A. Vérifier que l'organisme appartient bien à l'utilisateur et récupérer TOUTES ses données
        $stmtOrga = $pdo->prepare("SELECT * FROM organisateurs WHERE id = ? AND user_id = ?");
        $stmtOrga->execute([$organisateurId, $userId]);
        $organisateur = $stmtOrga->fetch(PDO::FETCH_ASSOC);

        if ($organisateur) {
            // B. Récupérer les camps liés à cet organisme
            $sqlCamps = "
                SELECT 
                    c.*,
                    (SELECT COUNT(*) FROM inscriptions WHERE camp_id = c.id) as nb_inscrits
                FROM camps c
                WHERE c.organisateur_id = ?
                ORDER BY c.date_debut DESC
            ";
            
            $stmtCamps = $pdo->prepare($sqlCamps);
            $stmtCamps->execute([$organisateurId]);
            $camps = $stmtCamps->fetchAll(PDO::FETCH_ASSOC);

            // C. Calculer des statistiques
            $stats = [
                'total_camps' => count($camps),
                'total_inscrits' => array_sum(array_column($camps, 'nb_inscrits')), 
                'solde_disponible' => floatval($organisateur['portefeuille'])
            ];
            
            // D. Récupérer l'historique des virements
            $stmtVirements = $pdo->prepare("
                SELECT token, montant_total, montant_apres_commission, date_demande, effectue, date_virement_effectue, date_virement_estime
                FROM virements
                WHERE organisateur_id = ?
                ORDER BY date_demande DESC
            ");
            $stmtVirements->execute([$organisateurId]);
            $virements = $stmtVirements->fetchAll(PDO::FETCH_ASSOC);

            // Mise à jour du logo
            if (!empty($organisateur['logo_url'])) {
                $logo_url = htmlspecialchars($organisateur['logo_url']);
            }

            // Vérifier les messages d'erreur de redirection
            $externalError = filter_input(INPUT_GET, 'error', FILTER_SANITIZE_STRING);
            if ($externalError) {
                $error = $externalError;
            }
            
            // Message de succès (ex: après validation identité)
            $successMsg = filter_input(INPUT_GET, 'success', FILTER_SANITIZE_STRING);

        } else {
            $error = "Organisme introuvable ou vous n'êtes pas autorisé à y accéder.";
        }

    } catch (Exception $e) {
        $error = "Erreur SQL : " . $e->getMessage();
    }
}

// NOUVEAU : Vérification du statut Stripe (Identité vérifiée ou non)
$isStripeReady = ($organisateur && !empty($organisateur['stripe_account_id']));

// 4. AFFICHAGE HTML
require_once 'partials/header.php';
?>

<title>Dashboard - <?= htmlspecialchars($organisateur['nom'] ?? 'Organisme') ?></title>

<div class="min-h-screen bg-gray-50 py-10 font-sans">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Erreur de gestion</p>
                <p><?= $error ?></p>
            </div>
            <a href="public_infos" class="text-[#0A112F] font-bold hover:underline block mt-4">&larr; Retour à la sélection</a>
        <?php else: ?>

            <?php if (isset($successMsg)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
                    <p class="font-bold">Succès</p>
                    <p><?= $successMsg ?></p>
                </div>
            <?php endif; ?>

            <div class="mb-8">
                <a href="public_infos" class="text-gray-500 hover:text-[#0A112F] inline-flex items-center mb-6 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Retour à la sélection des organismes
                </a>
                
                <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <?php if (isset($logo_url)): ?>
                            <img src="<?= $logo_url ?>" alt="Logo de l'organisme" class="w-16 h-16 object-contain rounded-full border border-gray-200 p-1">
                        <?php endif; ?>
                        <div>
                            <h1 class="text-3xl font-extrabold text-[#0A112F]">Tableau de bord : <?= htmlspecialchars($organisateur['nom']) ?></h1>
                            <div class="flex items-center gap-2 mt-1">
                                <p class="text-gray-500">Vue d'ensemble et gestion.</p>
                                <?php if ($isStripeReady): ?>
                                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-700 border border-green-200">Virements Actifs</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-orange-100 text-orange-700 border border-orange-200">Identité à vérifier</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-4 mt-4 md:mt-0">
                        <a href="edit_organisateur?id=<?= $organisateur['id'] ?>" class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Modifier les informations
                        </a>
                        
                        <?php if (!$isStripeReady): ?>
                            
                            <a href="verify_identity?organisateur_id=<?= $organisateur['id'] ?>" 
                               class="inline-flex items-center justify-center gap-2 bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition transform hover:-translate-y-0.5 animate-pulse">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Ajouter les documents d'identité
                            </a>

                        <?php else: ?>
                            
                            <?php 
                            $solde = $stats['solde_disponible'];
                            if ($solde > 0): 
                            ?>
                                <a href="demande_de_virement_info?organisateur_id=<?= $organisateur['id'] ?>" 
                                   class="inline-flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition transform hover:-translate-y-0.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    Virer l'argent (<?= number_format($solde, 2, ',', ' ') ?>€)
                                </a>
                            <?php else: ?>
                                <button disabled class="inline-flex items-center justify-center gap-2 bg-gray-400 text-white font-bold py-3 px-6 rounded-xl shadow-lg opacity-75 cursor-not-allowed">
                                    Solde insuffisant (0.00€)
                                </button>
                            <?php endif; ?>

                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                    <p class="text-sm font-medium text-gray-500">Solde Disponible</p>
                    <p class="text-3xl font-extrabold text-green-700 mt-1">
                        <?= number_format($stats['solde_disponible'], 2, ',', ' ') ?>€
                    </p>
                    <p class="text-xs text-gray-400 mt-2">Portefeuille actuel</p>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-[#0A112F]">
                    <p class="text-sm font-medium text-gray-500">Nombre de Séjours</p>
                    <p class="text-3xl font-extrabold text-[#0A112F] mt-1">
                        <?= $stats['total_camps'] ?>
                    </p>
                    <p class="text-xs text-gray-400 mt-2">Camps actifs pour cet organisme</p>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                    <p class="text-sm font-medium text-gray-500">Inscriptions Totales</p>
                    <p class="text-3xl font-extrabold text-blue-700 mt-1">
                        <?= $stats['total_inscrits'] ?>
                    </p>
                    <p class="text-xs text-gray-400 mt-2">Toutes inscriptions confondues</p>
                </div>
            </div>
            
            <?php 
                $iban_ok = !empty($organisateur['iban']);
                $bic_ok = !empty($organisateur['bic_swift']);
            ?>
            <div class="bg-white rounded-xl shadow-md p-8 mb-10">
                <h2 class="text-xl font-bold text-gray-900 mb-6 border-b pb-3">Informations Administratives et Bancaires</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-y-4 gap-x-8 text-sm">
                    
                    <div class="p-2 border rounded-lg bg-gray-50">
                        <p class="font-semibold text-gray-600">Statut Légal</p>
                        <p class="text-gray-900 mt-0.5"><?= htmlspecialchars($organisateur['statut_legal'] ?? 'Non renseigné') ?></p>
                    </div>

                    <div class="p-2 border rounded-lg bg-gray-50">
                        <p class="font-semibold text-gray-600">Email de Contact</p>
                        <p class="text-gray-900 mt-0.5"><?= htmlspecialchars($organisateur['email'] ?? 'Non renseigné') ?></p>
                    </div>

                    <div class="p-2 border rounded-lg bg-gray-50">
                        <p class="font-semibold text-gray-600">Téléphone</p>
                        <p class="text-gray-900 mt-0.5"><?= htmlspecialchars($organisateur['tel'] ?? 'Non renseigné') ?></p>
                    </div>

                    <div class="md:col-span-3 p-2 border rounded-lg bg-gray-50">
                        <p class="font-semibold text-gray-600">Adresse Administrative</p>
                        <p class="text-gray-900 mt-0.5">
                            <?= htmlspecialchars($organisateur['adresse_complete'] ?? 'Non renseigné') ?>, 
                            <?= htmlspecialchars($organisateur['code_postal_orga'] ?? '') ?> 
                            <?= htmlspecialchars($organisateur['ville_orga'] ?? '') ?>, 
                            <?= htmlspecialchars($organisateur['pays_orga'] ?? 'France') ?>
                        </p>
                    </div>
                    
                    <div class="p-2 border rounded-lg <?= $iban_ok ? 'bg-green-50' : 'bg-red-50' ?> md:col-span-2">
                        <p class="font-semibold text-gray-600">IBAN</p>
                        <p class="text-gray-900 mt-0.5 font-mono break-all"><?= htmlspecialchars($organisateur['iban'] ?? 'Non renseigné') ?></p>
                    </div>
                    
                    <div class="p-2 border rounded-lg <?= $bic_ok ? 'bg-green-50' : 'bg-red-50' ?>">
                        <p class="font-semibold text-gray-600">BIC / SWIFT</p>
                        <p class="text-gray-900 mt-0.5"><?= htmlspecialchars($organisateur['bic_swift'] ?? 'Non renseigné') ?></p>
                    </div>
                    
                    <?php if (!$iban_ok || !$bic_ok): ?>
                        <div class="md:col-span-3 text-red-700 text-sm mt-2 p-3 bg-red-100 rounded-lg">
                            ⚠️ Attention : IBAN ou BIC manquant. Veuillez <a href="edit_organisateur?id=<?= $organisateur['id'] ?>" class="font-bold underline">modifier les informations</a> pour permettre les virements.
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>

            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-[#0A112F]">Historique des Demandes de Virement</h2>
                <?php if (!empty($virements)): ?>
                    <a href="pdf_export_virements_history?organisateur_id=<?= $organisateurId ?>" target="_blank" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2v5h-2m0 0h-2m2 0v-5m-9-1h-2v5h2m0 0h-2m2 0v-5m-2-12h10v10h-10v-10zm-2 10h14v10h-14v-10z"/></svg>
                        Exporter en PDF
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($virements)): ?>
                <div class="bg-white rounded-xl shadow-sm p-8 text-center border border-gray-100 mb-10">
                    <p class="text-gray-500">Aucune demande de virement n'a été enregistrée pour cet organisme.</p>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden mb-10">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Demande du</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant Net</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant Brut</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut & Dates</th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Détails</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($virements as $virement): 
                                $status_text = $virement['effectue'] == 1 ? 'Effectué' : 'En attente';
                                $status_color = $virement['effectue'] == 1 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= date('d/m/Y', strtotime($virement['date_demande'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-700">
                                    <?= number_format($virement['montant_apres_commission'], 2, ',', ' ') ?>€
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= number_format($virement['montant_total'], 2, ',', ' ') ?>€
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_color ?>">
                                        <?= $status_text ?>
                                    </span>
                                    <?php if ($virement['effectue'] == 1): ?>
                                        <p class="text-xs text-gray-500 mt-1">Viré le <?= date('d/m/Y', strtotime($virement['date_virement_effectue'])) ?></p>
                                    <?php elseif ($virement['date_virement_estime']): ?>
                                        <p class="text-xs text-blue-500 mt-1">Estimé le <?= date('d/m/Y', strtotime($virement['date_virement_estime'])) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="virement?t=<?= $virement['token'] ?>" class="text-[#0A112F] hover:text-blue-600">Détails</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <h2 class="text-2xl font-bold text-[#0A112F] mb-6">Séjours gérés par <?= htmlspecialchars($organisateur['nom']) ?></h2>
            
            <?php if (empty($camps)): ?>
                <div class="bg-white rounded-3xl shadow-sm p-12 text-center border border-gray-100">
                    <p class="text-gray-500 mb-6">Cet organisme n'a encore publié aucun séjour.</p>
                    <a href="create_camp" class="text-[#0A112F] font-bold hover:underline">Créer un nouveau séjour &rarr;</a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    
                    <?php foreach ($camps as $camp): 
                        // Calculs pour la carte
                        $quota = max(1, intval($camp['quota_global']));
                        $inscrits = intval($camp['nb_inscrits']);
                        $percent = min(100, round(($inscrits / $quota) * 100));
                        // Utilise l'image du camp ou une image par défaut
                        $img = !empty($camp['image_url']) ? htmlspecialchars($camp['image_url']) : 'assets/default_camp.jpg'; 
                    ?>
                    <div class="bg-white rounded-2xl shadow-sm hover:shadow-md transition border border-gray-200 overflow-hidden flex flex-col">
                        <div class="relative h-32 w-full overflow-hidden">
                            <img src="<?= $img ?>" alt="Cover" class="w-full h-full object-cover">
                        </div>
                        <div class="p-4 flex-1 flex flex-col">
                            <h3 class="font-bold text-lg text-gray-900 truncate"><?= htmlspecialchars($camp['nom']) ?></h3>
                            <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($camp['ville']) ?></p>
                            
                            <div class="mt-4 mb-2">
                                <div class="flex justify-between text-xs font-semibold mb-1">
                                    <span class="text-gray-500">Inscrits</span>
                                    <span class="text-[#0A112F]"><?= $inscrits ?> / <?= $quota ?></span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-1 overflow-hidden">
                                    <div class="bg-[#0A112F] h-1 rounded-full" style="width: <?= $percent ?>%"></div>
                                </div>
                            </div>
                            
                            <a href="gestion_camp?t=<?= $camp['token'] ?>" class="mt-auto block w-full text-center py-2 rounded-lg bg-gray-100 text-gray-700 text-sm font-bold hover:bg-gray-200 transition">
                                Gérer les inscrits
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?>