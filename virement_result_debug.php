<?php
// virement_result_debug.php
// Page dédiée au débogage des réponses Stripe

require_once 'api/config.php';

// Sécurité
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_directeur'])) {
    header('Location: index.php');
    exit;
}

$status = $_GET['status'] ?? 'unknown';
$message = $_GET['message'] ?? 'Aucun message';
$organisateurId = $_GET['organisateur_id'] ?? null;
$token = $_GET['token'] ?? null; // Token du virement si succès
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultat du Virement (Debug)</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center p-4">

    <div class="max-w-2xl w-full bg-white rounded-xl shadow-lg overflow-hidden">
        
        <div class="p-6 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-800">Journal de Transaction Stripe</h1>
            <span class="px-3 py-1 rounded-full text-sm font-bold <?= $status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                <?= strtoupper(htmlspecialchars($status)) ?>
            </span>
        </div>

        <div class="p-8">
            
            <?php if ($status === 'success'): ?>
                <div class="flex items-center gap-4 text-green-600 mb-6">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <h2 class="text-xl font-bold">Virement effectué avec succès !</h2>
                        <p class="text-sm text-gray-600">Le transfert a été validé par Stripe.</p>
                    </div>
                </div>
                
                <div class="bg-gray-100 p-4 rounded text-sm font-mono break-all">
                    Token interne : <?= htmlspecialchars($token) ?>
                </div>

            <?php else: ?>
                
                <div class="flex items-center gap-4 text-red-600 mb-6">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div>
                        <h2 class="text-xl font-bold">Échec du Virement</h2>
                        <p class="text-sm text-gray-600">Une erreur technique est survenue chez Stripe.</p>
                    </div>
                </div>

                <div class="mb-4">
                    <p class="font-semibold text-gray-700 mb-2">Message d'erreur complet :</p>
                    <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg font-mono text-sm break-words whitespace-pre-wrap">
<?= htmlspecialchars($message) ?>
                    </div>
                </div>

                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mt-6">
                    <p class="text-sm text-blue-700">
                        <strong>Conseil de débug :</strong> Si l'erreur concerne "capabilities" ou "transfers", cela signifie que le compte Stripe connecté a besoin d'une mise à jour. Le script tente désormais de le faire automatiquement. Réessayez une fois.
                    </p>
                </div>

            <?php endif; ?>

            <div class="mt-8 flex gap-4">
                <?php if ($organisateurId): ?>
                    <a href="demande_de_virement_info.php?organisateur_id=<?= htmlspecialchars($organisateurId) ?>" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 text-center font-bold py-3 px-4 rounded-lg transition">
                        &larr; Retour au formulaire
                    </a>
                <?php endif; ?>
                
                <?php if ($status === 'success'): ?>
                    <a href="virement.php?t=<?= htmlspecialchars($token) ?>" class="flex-1 bg-green-600 hover:bg-green-700 text-white text-center font-bold py-3 px-4 rounded-lg transition">
                        Voir le reçu officiel &rarr;
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>