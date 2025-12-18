<?php
// api/user_register.php
require_once 'config.php';
require_once 'mail_config.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) { echo json_encode(['error' => 'Données invalides.']); exit; }

// Récupération
$nom = trim($input['nom'] ?? '');
$prenom = trim($input['prenom'] ?? '');
$email = trim($input['mail'] ?? '');
$password = $input['password'] ?? '';
$naissance = $input['naissance'] ?? null;
$sexe = $input['sexe'] ?? 'Non précisé';
$role = $input['role'] ?? 'parent';
$bafa = !empty($input['bafa']) ? 1 : 0;
$is_animateur = ($role === 'animateur') ? 1 : 0;

if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
    echo json_encode(['error' => 'Tous les champs sont requis.']);
    exit;
}

try {
    // Unicité Email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Cet email est déjà utilisé.']);
        exit;
    }

    // Hashage
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Génération des codes
    $verificationCode = rand(100000, 999999);
    $urlToken = substr(bin2hex(random_bytes(13)), 0, 25);

    // Insertion
    $sql = "INSERT INTO users (
        nom, prenom, email, password, verification_token, url_token, is_verified, 
        created_at, is_animateur, bafa
    ) VALUES (
        ?, ?, ?, ?, ?, ?, 0, 
        NOW(), ?, ?
    )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nom, $prenom, $email, $passwordHash, $verificationCode, $urlToken, $is_animateur, $bafa]);

    // --- CONSTRUCTION DE L'EMAIL (Design Corrigé) ---
    $subject = "Activez votre compte ColoMap";
    
    $server = $_SERVER['SERVER_NAME'];
    $linkValidate = "https://" . $server . "/validation.php?t=" . $urlToken . "&code=" . $verificationCode;
    $linkCancel = "https://" . $server . "/api/cancel_registration.php?t=" . $urlToken;
    $logoUrl = "https://veyret.freeboxos.fr:45474/share/PSu2SneCf_lg32kW/favico.png";

    $body = "
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f9fafb; margin: 0; padding: 0; color: #333; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #eaeaea; }
        
        /* Header blanc avec logo */
        .header { background-color: #ffffff; padding: 40px 20px 20px 20px; text-align: center; border-bottom: 1px solid #f0f0f0; }
        .header img { height: 70px; width: auto; display: block; margin: 0 auto 15px auto; }
        .header h1 { color: #0A112F; margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
        
        .content { padding: 40px 40px; text-align: center; }
        .content p { font-size: 16px; line-height: 1.6; color: #555; margin-bottom: 25px; }
        .h2-title { color: #0A112F; font-size: 20px; margin-top: 0; font-weight: 600; }
        
        /* Code Box Style */
        .code-box { background-color: #f3f4f6; color: #0A112F; font-size: 32px; font-weight: bold; text-align: center; padding: 20px; margin: 30px 0; letter-spacing: 10px; border-radius: 12px; border: 2px solid #e5e7eb; }
        
        /* Button Style */
        .btn-container { margin: 35px 0; }
        .btn { background-color: #0A112F; color: #ffffff !important; text-decoration: none; padding: 16px 40px; border-radius: 12px; font-weight: 600; font-size: 16px; display: inline-block; box-shadow: 0 4px 6px rgba(10, 17, 47, 0.15); }
        .btn:hover { background-color: #1a234b; }
        
        .footer { background-color: #f9fafb; padding: 25px; text-align: center; font-size: 13px; color: #888; border-top: 1px solid #eaeaea; }
        .cancel-link { color: #ef4444; text-decoration: none; border-bottom: 1px dashed #ef4444; }
    </style>
    </head>
    <body>
       <div class='container'>
         <div class='header'>
           <img src='$logoUrl' alt='ColoMap'>
           <h1>Bienvenue sur ColoMap</h1>
         </div>
         <div class='content'>
           <h2 class='h2-title'>Bonjour $prenom,</h2>
           <p>Nous sommes ravis de vous compter parmi nous. Pour sécuriser votre compte, veuillez confirmer votre adresse e-mail en cliquant ci-dessous :</p>
           
           <div class='btn-container'>
               <a href='$linkValidate' class='btn'>Valider mon compte</a>
           </div>
           
           <p style='font-size:14px; color:#999; margin-top:30px;'>Ou utilisez ce code de sécurité :</p>
           <div class='code-box'>$verificationCode</div>
         </div>
         <div class='footer'>
           <p style='margin-bottom:10px;'>Vous n'avez pas demandé cette inscription ? <br>
           <a href='$linkCancel' class='cancel-link'>Cliquez ici pour supprimer vos données</a>.</p>
           &copy; " . date('Y') . " ColoMap.
         </div>
       </div>
    </body>
    </html>
    ";

    if (function_exists('sendMailGmail')) {
        sendMailGmail($email, "$prenom $nom", $subject, $body);
    } else {
         $headers = "MIME-Version: 1.0" . "\r\n";
         $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
         $headers .= "From: ColoMap <no-reply@" . $server . ">";
         mail($email, $subject, $body, $headers);
    }

    echo json_encode([
        'success' => 'Compte créé !',
        'redirect_token' => $urlToken
    ]);

} catch (PDOException $e) {
    error_log("Register SQL: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur technique.']);
} catch (Exception $e) {
    error_log("Register Mail: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur envoi email.']);
}
?>