<?php
// api/cancel_registration.php
require_once 'config.php';

$token = $_GET['t'] ?? '';

if (empty($token)) {
    die("Lien invalide.");
}

try {
    // On vérifie que l'utilisateur n'est PAS encore vérifié pour éviter de supprimer un compte actif par erreur
    $stmt = $pdo->prepare("SELECT id FROM users WHERE url_token = ? AND is_verified = 0");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Suppression
        $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $del->execute([$user['id']]);
        
        // Affichage HTML simple de confirmation
        ?>
        <!DOCTYPE html>
        <html>
        <head><title>Annulation</title></head>
        <body style="font-family: sans-serif; text-align: center; padding-top: 50px; background: #f9fafb;">
            <div style="max-width: 500px; margin: auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <h1 style="color: #ef4444;">Inscription annulée</h1>
                <p>Votre adresse e-mail et vos données ont été supprimées de notre base de données.</p>
                <a href="../index.php" style="color: #0A112F;">Retour à l'accueil</a>
            </div>
        </body>
        </html>
        <?php
    } else {
        echo "Lien expiré ou compte déjà activé.";
    }

} catch (Exception $e) {
    echo "Erreur lors de l'annulation.";
}
?>