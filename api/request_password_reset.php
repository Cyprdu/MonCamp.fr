<?php
// api/request_password_reset.php
require_once 'config.php';
require_once 'mail_config.php';

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['mail'] ?? '');

if (empty($email)) { echo json_encode(['error' => 'Email requis.']); exit; }

try {
    // 1. Vérifier si l'user existe
    $stmt = $pdo->prepare("SELECT id, prenom, nom FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // 2. Générer Token (32 chars)
        $token = bin2hex(random_bytes(16));
        // Expire dans 1 heure
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // 3. Sauvegarder en base
        $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $update->execute([$token, $expires, $user['id']]);

        // 4. Envoyer l'email
        $server = $_SERVER['SERVER_NAME'];
        $link = "https://" . $server . "/reset_password.php?t=" . $token;
        $logoUrl = "https://veyret.freeboxos.fr:45474/share/PSu2SneCf_lg32kW/favico.png";
        $prenom = htmlspecialchars($user['prenom']);

        $subject = "Réinitialisation de votre mot de passe";
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
        <style>
            body { font-family: sans-serif; background-color: #f9fafb; padding: 20px; }
            .container { max-width: 600px; margin: auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
            .header { background: #ffffff; padding: 30px; text-align: center; border-bottom: 1px solid #eee; }
            .header img { height: 60px; }
            .content { padding: 40px; text-align: center; color: #333; }
            .btn { background: #0A112F; color: white !important; text-decoration: none; padding: 15px 30px; border-radius: 50px; font-weight: bold; display: inline-block; margin: 20px 0; }
            .footer { padding: 20px; text-align: center; font-size: 12px; color: #999; background: #f9fafb; }
        </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='$logoUrl' alt='ColoMap'>
                </div>
                <div class='content'>
                    <h2>Mot de passe oublié ?</h2>
                    <p>Bonjour $prenom,<br>Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.</p>
                    <a href='$link' class='btn'>Changer mon mot de passe</a>
                    <p style='font-size:13px; color:#777;'>Ce lien est valide pendant 1 heure.<br>Si vous n'êtes pas à l'origine de cette demande, ignorez cet e-mail.</p>
                </div>
                <div class='footer'>&copy; ColoMap " . date('Y') . "</div>
            </div>
        </body>
        </html>
        ";

        if (function_exists('sendMailGmail')) {
            sendMailGmail($email, "$prenom " . $user['nom'], $subject, $body);
        } else {
            // Fallback
            $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: ColoMap <no-reply@$server>";
            mail($email, $subject, $body, $headers);
        }
    }

    // Sécurité : On dit TOUJOURS succès pour ne pas révéler si l'email existe ou non
    echo json_encode(['success' => 'Si cet email existe, un lien a été envoyé.']);

} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur technique.']);
}
?>