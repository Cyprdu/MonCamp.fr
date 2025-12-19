<?php
// Fichier : inscription_confirmation.php
require_once 'api/config.php';

// Paramètres
$success = isset($_GET['success']) && $_GET['success'] == '1';
$cancel  = isset($_GET['cancel']) && $_GET['cancel'] == '1';
$tokens  = $_GET['tokens'] ?? '';
$camp_id = $_GET['camp_id'] ?? '';

// Nettoyage en cas d'annulation
if ($cancel && !empty($tokens)) {
    try {
        $tokenArray   = explode(',', $tokens);
        $placeholders = implode(',', array_fill(0, count($tokenArray), '?'));

        $sql = "DELETE FROM inscriptions 
                WHERE reservation_token IN ($placeholders) 
                AND statut_paiement = 'EN_ATTENTE'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($tokenArray);
    } catch (Exception $e) {
        error_log("Erreur suppression inscription annulée : " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Lottie -->
    <script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script>

    <style>
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
    </style>
</head>

<body class="flex items-center justify-center w-screen h-screen bg-white">

<?php if ($success): ?>

    <!-- Animation succès (3x plus grande) -->
    <dotlottie-player
        src="chek.json"
        background="transparent"
        speed="1"
        autoplay
        style="width: 900px; height: 900px;">
    </dotlottie-player>

    <script>
        setTimeout(() => {
            window.location.href = 'reservations';
        }, 3500);
    </script>

<?php else: ?>

    <!-- Animation échec (3x plus grande) -->
    <dotlottie-player
        src="fail.json"
        background="transparent"
        speed="1"
        autoplay
        style="width: 900px; height: 900px;">
    </dotlottie-player>

    <script>
        setTimeout(() => {
            window.location.href = 'camp_details?id=<?= htmlspecialchars($camp_id) ?>';
        }, 3500);
    </script>

<?php endif; ?>

</body>
</html>
