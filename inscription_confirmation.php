<?php
// Fichier : inscription_confirmation.php
require_once 'api/config.php';

// Récupération des paramètres
$success = isset($_GET['success']) && $_GET['success'] == '1';
$cancel = isset($_GET['cancel']) && $_GET['cancel'] == '1';
$tokens = $_GET['tokens'] ?? '';
$camp_id = $_GET['camp_id'] ?? '';

// LOGIQUE DE NETTOYAGE EN CAS D'ÉCHEC / ANNULATION
// Si l'utilisateur annule, on supprime les inscriptions en attente pour qu'il puisse recommencer proprement
if ($cancel && !empty($tokens)) {
    try {
        $tokenArray = explode(',', $tokens);
        // On crée une chaîne de '?, ?, ?' pour la requête SQL
        $placeholders = implode(',', array_fill(0, count($tokenArray), '?'));
        
        $sql = "DELETE FROM inscriptions WHERE reservation_token IN ($placeholders) AND statut_paiement = 'EN_ATTENTE'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($tokenArray);
    } catch (Exception $e) {
        // Erreur silencieuse (log serveur uniquement)
        error_log("Erreur suppression inscription annulée : " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $success ? 'Confirmation' : 'Erreur' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script>
    <style>
        body { margin: 0; padding: 0; overflow: hidden; }
    </style>
</head>
<body class="bg-white flex flex-col items-center justify-center h-screen w-screen text-center font-sans">

    <?php if ($success): ?>
        
        <div class="w-80 h-80 mb-4">
            <dotlottie-player 
                src="Animation%20-%201720788531370.json" 
                background="transparent" 
                speed="1" 
                style="width: 100%; height: 100%" 
                autoplay>
            </dotlottie-player>
        </div>
        
        <h1 class="text-5xl font-bold text-gray-900 mb-2">Merci !</h1>
        <p class="text-gray-500 text-lg">Paiement validé avec succès.</p>

        <script>
            // Redirection automatique après 3.5 secondes
            setTimeout(() => {
                window.location.href = 'reservations';
            }, 3500);
        </script>

    <?php else: ?>

        <div class="w-64 h-64 mb-6">
            <dotlottie-player 
                src="fail.json" 
                background="transparent" 
                speed="1" 
                style="width: 100%; height: 100%" 
                autoplay>
            </dotlottie-player>
        </div>

        <h1 class="text-5xl font-bold text-gray-900 mb-2">Oups...</h1>
        <p class="text-gray-500 text-lg">Le paiement n'a pas abouti.</p>

        <script>
            // Retour automatique à la page du camp après 3.5 secondes
            setTimeout(() => {
                window.location.href = 'camp_details?id=<?= htmlspecialchars($camp_id) ?>'; 
            }, 3500);
        </script>

    <?php endif; ?>

</body>
</html>