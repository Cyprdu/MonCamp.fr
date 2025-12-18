<?php
// virement.php

// 1. CONFIG
require_once 'api/config.php';

// 2. SÉCURITÉ
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_directeur'])) {
    header('Location: index.php');
    exit;
}

// 3. LOGIQUE MÉTIER
$token = filter_input(INPUT_GET, 't', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
$virement = null;
$error = null;

if (!$token || strlen($token) !== 60) {
    $error = "Token de virement invalide ou manquant.";
} else {
    try {
        // Récupérer les détails du virement et vérifier l'appartenance à l'utilisateur
        $sql = "
            SELECT v.*, o.nom as organisateur_nom, o.id as organisateur_id 
            FROM virements v
            JOIN organisateurs o ON v.organisateur_id = o.id
            WHERE v.token = ? AND v.user_id = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$token, $_SESSION['user']['id']]);
        $virement = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$virement) {
            $error = "Demande de virement introuvable ou vous n'êtes pas autorisé à la consulter.";
        }

    } catch (Exception $e) {
        $error = "Erreur SQL : " . $e->getMessage();
    }
}

// 4. AFFICHAGE HTML
require_once 'partials/header.php';
?>

<title>Confirmation de Virement</title>

<div class="min-h-screen bg-gray-50 py-10 font-sans">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Erreur</p>
                <p><?= $error ?></p>
            </div>
            <a href="public_infos.php" class="text-[#0A112F] font-bold hover:underline block mt-4">&larr; Retour à la sélection d'organisme</a>

        <?php elseif ($virement): ?>

            <div class="text-center mb-8">
                <div class="mx-auto h-16 w-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h1 class="text-3xl font-extrabold text-[#0A112F]">Demande de Virement Enregistrée</h1>
                <p class="text-gray-500 mt-2">Votre demande de virement pour l'organisme **<?= htmlspecialchars($virement['organisateur_nom']) ?>** a été soumise à l'administration.</p>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-8 space-y-6">
                
                <h2 class="text-xl font-bold text-gray-900 border-b pb-3">Détails de la Transaction</h2>
                
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="font-semibold text-gray-600">Organisme concerné :</div>
                    <div class="text-gray-900"><?= htmlspecialchars($virement['organisateur_nom']) ?></div>

                    <div class="font-semibold text-gray-600">Montant Brut Demandé :</div>
                    <div class="text-gray-900 font-bold"><?= number_format($virement['montant_total'], 2, ',', ' ') ?>€</div>
                    
                    <div class="font-semibold text-gray-600">Commission ColoMap (<?= number_format($virement['commission_rate'], 2, ',', ' ') ?>%) :</div>
                    <div class="text-red-600">- <?= number_format($virement['montant_total'] - $virement['montant_apres_commission'], 2, ',', ' ') ?>€</div>
                    
                    <div class="font-semibold text-gray-600 text-lg">Montant Net à Virer :</div>
                    <div class="text-green-600 font-extrabold text-lg"><?= number_format($virement['montant_apres_commission'], 2, ',', ' ') ?>€</div>

                    <div class="font-semibold text-gray-600">Date de la demande :</div>
                    <div class="text-gray-900"><?= date('d/m/Y à H:i', strtotime($virement['date_demande'])) ?></div>
                    
                    <div class="font-semibold text-gray-600">Statut du Virement :</div>
                    <div class="text-gray-900">
                        <?php if ($virement['effectue'] == 1): ?>
                            <span class="text-green-600 font-bold">Effectué</span> le <?= date('d/m/Y', strtotime($virement['date_virement_effectue'])) ?>
                        <?php else: ?>
                            <span class="text-yellow-600 font-bold">En attente</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($virement['effectue'] == 0 && $virement['date_virement_estime']): ?>
                        <div class="font-semibold text-gray-600">Date Estimée :</div>
                        <div class="text-blue-600 font-bold"><?= date('d/m/Y', strtotime($virement['date_virement_estime'])) ?></div>
                    <?php endif; ?>
                    </div>

                <h2 class="text-xl font-bold text-gray-900 border-b pb-3 pt-4">Coordonnées Bancaires Utilisées</h2>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="font-semibold text-gray-600">IBAN :</div>
                    <div class="text-gray-900 font-mono break-all"><?= htmlspecialchars($virement['iban']) ?></div>
                    
                    <div class="font-semibold text-gray-600">BIC / SWIFT :</div>
                    <div class="text-gray-900"><?= htmlspecialchars($virement['bic_swift']) ?></div>
                </div>
                
                <p class="text-sm text-gray-600 pt-4">
                    Le traitement des virements s'effectue manuellement par l'administration.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 mt-6">
                    <a href="pdf_export_virement.php?t=<?= $token ?>" target="_blank" class="flex-1 inline-flex items-center justify-center gap-2 py-3 rounded-xl bg-red-600 text-white font-bold hover:bg-red-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2v5h-2m0 0h-2m2 0v-5m-9-1h-2v5h2m0 0h-2m2 0v-5m-2-12h10v10h-10v-10zm-2 10h14v10h-14v-10z"/></svg>
                        Exporter la demande en PDF
                    </a>
                    <a href="dashboard_organisme.php?organisateur_id=<?= $virement['organisateur_id'] ?>" class="flex-1 block w-full text-center py-3 rounded-xl bg-gray-600 text-white font-bold hover:bg-gray-700 transition">
                        Retour au Tableau de Bord
                    </a>
                </div>
            </div>
            
        <?php endif; ?>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?>