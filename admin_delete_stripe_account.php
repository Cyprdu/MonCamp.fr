<?php
// admin_delete_stripe_account.php

// 1. CONFIGURATION
require_once 'api/config.php';

use Stripe\Stripe;
use Stripe\Account;
use Stripe\Exception\ApiErrorException;

// 2. SÉCURITÉ : Restreindre aux Admins/Directeurs
if (!isset($_SESSION['user']) || (empty($_SESSION['user']['is_directeur']) && empty($_SESSION['user']['is_admin']))) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = ''; // 'success' or 'error'

// 3. TRAITEMENT DE LA SUPPRESSION (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_organisateur_id'])) {
    $orgaIdToDelete = intval($_POST['delete_organisateur_id']);

    try {
        // A. Récupérer l'ID Stripe en base
        $stmt = $pdo->prepare("SELECT id, nom, stripe_account_id FROM organisateurs WHERE id = ?");
        $stmt->execute([$orgaIdToDelete]);
        $orga = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($orga && !empty($orga['stripe_account_id'])) {
            $stripeAccountId = $orga['stripe_account_id'];
            $shouldCleanDatabase = false;

            // B. Appel API Stripe : SUPPRESSION
            try {
                $account = Account::retrieve($stripeAccountId);
                $account->delete();
                $message = "Le compte Stripe de <strong>" . htmlspecialchars($orga['nom']) . "</strong> a été supprimé correctement chez Stripe.";
                $shouldCleanDatabase = true;
            } catch (ApiErrorException $e) {
                // ANALYSE DE L'ERREUR POUR DÉBLOCAGE
                $errBody = $e->getMessage();
                
                // Si le compte n'existe pas ou plus, ou si l'accès est refusé (cas d'un compte orphelin)
                // On force le nettoyage de la BDD locale pour ne pas rester bloqué
                if (strpos($errBody, 'No such account') !== false || 
                    strpos($errBody, 'does not have access to account') !== false ||
                    strpos($errBody, 'account does not exist') !== false) {
                    
                    $message = "Le compte Stripe n'existait déjà plus (erreur Stripe : <em>" . htmlspecialchars($errBody) . "</em>).<br><strong>Action :</strong> La liaison locale a été forcée à la suppression.";
                    $shouldCleanDatabase = true;
                } else {
                    // C'est une autre erreur (ex: réseau, API down), on bloque et on affiche
                    throw $e;
                }
            }

            // C. Nettoyage de la Base de Données (Si autorisé)
            if ($shouldCleanDatabase) {
                $updateStmt = $pdo->prepare("UPDATE organisateurs SET stripe_account_id = NULL WHERE id = ?");
                $updateStmt->execute([$orgaIdToDelete]);
                $messageType = 'success';
            }

        } else {
            $message = "Organisme introuvable ou pas de compte Stripe lié.";
            $messageType = 'error';
        }

    } catch (Exception $e) {
        $message = "Erreur technique : " . $e->getMessage();
        $messageType = 'error';
    }
}

// 4. RÉCUPÉRATION DE LA LISTE DES COMPTES CONNECTÉS
$sql = "SELECT id, nom, email, stripe_account_id, portefeuille FROM organisateurs WHERE stripe_account_id IS NOT NULL";
$organisateurs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

require_once 'partials/header.php';
?>

<div class="min-h-screen bg-gray-100 py-10 font-sans">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-extrabold text-[#0A112F]">Gestion des Comptes Stripe Connectés</h1>
            <a href="public_infos.php" class="text-sm text-gray-500 hover:underline">Retour au tableau de bord</a>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-md shadow-sm <?= $messageType === 'success' ? 'bg-green-100 text-green-800 border-l-4 border-green-500' : 'bg-red-100 text-red-800 border-l-4 border-red-500' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 24 24"><path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.928 0-1.302.92-2.36 2.67-2.36 1.265 0 2.435.665 3.276 1.633l1.884-2.252c-1.341-1.533-3.213-2.58-5.19-2.58C8.267.663 5.405 3.328 5.405 6.96c0 3.864 2.618 5.706 6.49 7.046 2.313.805 3.037 1.571 3.037 2.958 0 1.41-1.127 2.418-2.968 2.418-1.764 0-3.32-.962-4.13-2.25l-2.023 2.052C7.172 21.41 9.49 22.586 12 22.586c5.36 0 8.405-2.83 8.405-6.696 0-4.18-2.91-5.908-6.429-6.74z"/></svg>
                <h2 class="text-lg font-bold text-gray-700">Comptes Actifs</h2>
            </div>

            <?php if (empty($organisateurs)): ?>
                <div class="p-12 text-center text-gray-500">
                    <p>Aucun compte Stripe connecté trouvé dans la base de données.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Organisme</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Compte Stripe (ID)</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solde Interne</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($organisateurs as $orga): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900"><?= htmlspecialchars($orga['nom']) ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($orga['email']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                            <?= htmlspecialchars($orga['stripe_account_id']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= number_format($orga['portefeuille'], 2, ',', ' ') ?> €
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <form method="POST" onsubmit="return confirm('ATTENTION : Cette action est irréversible et supprimera la liaison.\n\nÊtes-vous sûr ?');">
                                            <input type="hidden" name="delete_organisateur_id" value="<?= $orga['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900 font-bold bg-red-50 hover:bg-red-100 px-3 py-1 rounded transition">
                                                Forcer la suppression
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="mt-4 text-xs text-gray-500">
            <p><strong>Note technique :</strong> Cette page tente de supprimer le compte via l'API Stripe. Si le compte n'existe plus chez Stripe (erreur d'accès), la liaison locale sera automatiquement supprimée pour débloquer la situation.</p>
        </div>

    </div>
</div>

<?php require_once 'partials/footer.php'; ?>