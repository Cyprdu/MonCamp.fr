<?php
// api/process_password_reset.php
require_once 'config.php';

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

$token = $input['token'] ?? '';
$password = $input['password'] ?? '';

if (empty($token) || empty($password)) {
    echo json_encode(['error' => 'Données manquantes.']);
    exit;
}

try {
    // 1. Vérifier le token valide et non expiré
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['error' => 'Lien expiré ou invalide.']);
        exit;
    }

    // 2. Hash du nouveau mot de passe
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // 3. Update mot de passe + suppression du token (usage unique)
    $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
    $update->execute([$hash, $user['id']]);

    echo json_encode(['success' => 'Mot de passe modifié avec succès !']);

} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur lors de la mise à jour.']);
}
?>