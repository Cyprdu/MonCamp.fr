<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Assurez-vous que le chemin vers l'autoload est correct
// Si ce fichier est dans /api/, l'autoload est dans ../vendor/autoload.php
require_once __DIR__ . '/../vendor/autoload.php';

function sendMailGmail($toEmail, $toName, $subject, $bodyContent) {
    $mail = new PHPMailer(true);

    try {
        // --- Configuration Serveur SMTP Google ---
        $mail->isSMTP();                                            
        $mail->Host       = 'smtp.gmail.com';                     
        $mail->SMTPAuth   = true;                                   
        $mail->Username   = 'colomap.secu@gmail.com';  // <--- VOTRE GMAIL ICI
        $mail->Password   = 'vaud jrwe vpoy dqdo';    // <--- VOTRE MOT DE PASSE D'APPLICATION (les 16 caractères)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Cryptage TLS
        $mail->Port       = 587;                                    

        // --- Expéditeur et Destinataire ---
        $mail->setFrom('colomap.secu@gmail.com', 'ColoMap'); // Doit être le même que Username
        $mail->addAddress($toEmail, $toName);     

        // --- Contenu ---
        $mail->isHTML(true); // On active le HTML pour faire joli
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $bodyContent;
        // Version texte brut pour les vieux clients mail
        $mail->AltBody = strip_tags($bodyContent);

        $mail->send();
        return true;
    } catch (Exception $e) {
        // En prod, on log l'erreur au lieu de l'afficher
        error_log("Erreur d'envoi Mailer: {$mail->ErrorInfo}");
        return false;
    }
}
?>