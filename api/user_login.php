<?php
// api/user_login.php
require_once 'config.php';

header('Content-Type: application/json');

// Récupération du JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'Données invalides.']);
    exit;
}

$email = trim($input['mail'] ?? ''); // Correspond à 'mail' du JS
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['error' => 'Veuillez remplir tous les champs.']);
    exit;
}

try {
    // 1. Récupérer l'user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Vérification
    if ($user && password_verify($password, $user['password'])) {
        
        // CHECK VERIFICATION EMAIL
        // Si la colonne n'existe pas encore (cas rare), on considère validé par défaut
        $isVerified = isset($user['is_verified']) ? $user['is_verified'] : 1;

        if ($isVerified == 0) {
            // IMPORTANT : On renvoie une erreur spécifique pour que le JS puisse réagir si besoin
            echo json_encode([
                'error' => 'Compte non vérifié. Veuillez vérifier vos e-mails.',
                'need_validation' => true,
                'email' => $email
            ]);
            exit;
        }

        // Connexion OK
        if (session_status() === PHP_SESSION_NONE) session_start();
        unset($user['password']); // Sécurité
        $_SESSION['user'] = $user;

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Email ou mot de passe incorrect.']);
    }

} catch (PDOException $e) {
    error_log("Erreur Login : " . $e->getMessage());
    echo json_encode(['error' => 'Erreur de connexion serveur.']);
}
?>