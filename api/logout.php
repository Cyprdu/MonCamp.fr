<?php
// Fichier: /api/logout.php
session_start();
require_once 'config.php'; // Connexion DB nécessaire pour nettoyer le token

// 1. Si l'utilisateur est connecté, on nettoie le token en base de données
if (isset($_SESSION['user']['id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
    } catch (Exception $e) {
        // On continue même si la DB échoue (l'important est de déconnecter)
    }
}

// 2. Destruction de la session PHP standard
$_SESSION = array(); // Vide les variables de session

// Si on veut détruire complètement la session, effacez également le cookie de session.
// Note : cela détruira la session et pas seulement les données de session !
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy(); // Détruit la session

// 3. DESTRUCTION DU COOKIE "SE SOUVENIR DE MOI"
// C'est l'étape clé pour ta demande : on le périme en mettant une date passée.
if (isset($_COOKIE['remember_me'])) {
    // Les paramètres (path, domain...) doivent être identiques à ceux de la création dans login.php
    setcookie('remember_me', '', time() - 3600, "/", "", false, true);
    unset($_COOKIE['remember_me']);
}

// 4. Redirection vers la page de connexion
header("Location: ../");
exit();
?>