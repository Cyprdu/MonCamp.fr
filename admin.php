<?php
// Fichier: admin_messages.php
require_once 'api/config.php';
require_once 'partials/header.php';

// Sécurité Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] != 1) {
    echo "<script>window.location.href='index.php';</script>";
    exit();
}

// TINYMCE (Version CDNJS)
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" integrity="sha512-6JR4bbn8rCKvrkOGMcleNghLnuGYQZIVh2jlFoORJBqc0qOVmOFbRy9pM2f4mMEcBbIKmALBdaVOZcn+tHOkWQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
  document.addEventListener("DOMContentLoaded", function() {
      tinymce.init({
        selector: '.rich-editor',
        height: 300,
        menubar: false,
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
        branding: false,
        promotion: false,
        content_style: 'body { font-family:Inter,sans-serif; font-size:14px }',
      });
  });
</script>
<?php

// Fonction Email (SANS LE CONTENU DE LA REPONSE)
function sendReplyEmail($toEmail, $toName, $question, $token) {
    // Construction URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $baseUrl = $protocol . $_SERVER['HTTP_HOST'];
    $link = $baseUrl . "/view_reply.php?t=" . $token;
    
    $subject = "Une réponse vous attend - ColoMap";

    // TEMPLATE EMAIL TEASING
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; margin-top: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(90deg, #2563eb, #9333ea); padding: 30px; text-align: center; color: white; }
            .header h1 { margin: 0; font-size: 24px; font-weight: 800; }
            .content { padding: 40px 30px; color: #374151; line-height: 1.6; text-align: center; }
            .question-box { text-align: left; background-color: #f9fafb; border-left: 4px solid #9ca3af; padding: 15px; margin: 20px 0; font-style: italic; color: #6b7280; font-size: 14px; }
            .btn { display: inline-block; background-color: #2563eb; color: #ffffff !important; text-decoration: none; padding: 14px 28px; border-radius: 6px; font-weight: bold; font-size: 16px; margin-top: 10px; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3); }
            .btn:hover { background-color: #1d4ed8; }
            .footer { background-color: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>ColoMap Support</h1>
            </div>
            <div class='content'>
                <p style='font-size: 18px;'>Bonjour <strong>$toName</strong>,</p>
                <p>Notre équipe a traité votre demande concernant :</p>
                
                <div class='question-box'>
                    \"" . nl2br(htmlspecialchars(substr($question, 0, 150))) . "...\"
                </div>

                <p style='margin-bottom: 25px;'>Pour des raisons de sécurité et de formatage, la réponse est consultable uniquement sur notre plateforme sécurisée.</p>

                <a href='$link' class='btn'>Lire la réponse</a>
                
                <p style='margin-top: 30px; font-size: 13px; color: #6b7280;'>Ou copiez ce lien : <a href='$link' style='color:#6b7280;'>$link</a></p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " ColoMap. Tous droits réservés.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: ColoMap Support <auth@moncamp.fr>" . "\r\n"; 
    $headers .= "Reply-To: auth@moncamp.fr" . "\r\n";

    return mail($toEmail, $subject, $message, $headers);
}

// TRAITEMENT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_id'])) {
    $msg_id = intval($_POST['reply_id']);
    $reponse = $_POST['reponse']; 
    
    if (!empty($reponse)) {
        $stmt = $pdo->prepare("UPDATE contact_messages SET reponse = ?, statut = 'Traité', replied_at = NOW() WHERE id = ?");
        if($stmt->execute([$reponse, $msg_id])) {
            $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id = ?");
            $stmt->execute([$msg_id]);
            $msgData = $stmt->fetch();

            if($msgData) {
                // On n'envoie plus $reponse dans l'email
                $sent = sendReplyEmail(
                    $msgData['email'], 
                    $msgData['prenom'], 
                    $msgData['message'], 
                    $msgData['token']
                );
                if($sent) $success = "Réponse enregistrée et notification envoyée !";
                else $error = "Erreur envoi mail.";
            }
        } else {
            $error = "Erreur BDD.";
        }
    }
}

// AFFICHAGE (Reste identique à avant pour la partie admin)
$view = $_GET['view'] ?? 'pending'; 
$filter_motif = $_GET['motif'] ?? 'all'; 
$sql = "SELECT * FROM contact_messages WHERE statut = " . ($view === 'history' ? "'Traité'" : "'En attente'");
$params = [];
if ($filter_motif !== 'all') {
    if ($filter_motif === 'Autre') { $sql .= " AND motif LIKE 'Autre :%'"; } 
    else { $sql .= " AND motif = ?"; $params[] = $filter_motif; }
}
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
$motifs_list = ["Renseignement séjour", "Inscription / Réservation", "Compte / Connexion", "Partenariat / Organisateur", "Recrutement / Animation", "Autre"];
?>

<div class="bg-gray-100 min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-6xl">
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <h1 class="text-3xl font-bold text-gray-800">Gestion des Messages</h1>
            <div class="flex gap-2">
                <a href="?view=pending&motif=<?php echo urlencode($filter_motif); ?>" class="px-4 py-2 rounded-lg font-bold transition <?php echo $view === 'pending' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-gray-50'; ?>">À traiter</a>
                <a href="?view=history&motif=<?php echo urlencode($filter_motif); ?>" class="px-4 py-2 rounded-lg font-bold transition <?php echo $view === 'history' ? 'bg-green-600 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-gray-50'; ?>">Historique</a>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6 flex items-center gap-4">
            <span class="text-gray-500 font-bold text-sm">Filtrer :</span>
            <form action="" method="GET" class="flex-grow">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                <select name="motif" onchange="this.form.submit()" class="w-full md:w-auto bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg p-2.5">
                    <option value="all" <?php echo $filter_motif === 'all' ? 'selected' : ''; ?>>Tous</option>
                    <?php foreach ($motifs_list as $m): ?>
                        <option value="<?php echo $m; ?>" <?php echo $filter_motif === $m ? 'selected' : ''; ?>><?php echo $m; ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if (isset($success)) echo "<div class='bg-green-100 text-green-700 p-4 rounded mb-4 border-l-4 border-green-500'>$success</div>"; ?>
        <?php if (isset($error)) echo "<div class='bg-red-100 text-red-700 p-4 rounded mb-4 border-l-4 border-red-500'>$error</div>"; ?>

        <div class="space-y-4">
            <?php foreach ($messages as $msg): ?>
                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 <?php echo $view === 'pending' ? 'border-orange-500' : 'border-green-500'; ?>">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-lg"><?php echo htmlspecialchars($msg['prenom'] . ' ' . $msg['nom']); ?></h3>
                        <span class="bg-purple-50 text-purple-700 border border-purple-100 px-3 py-1 rounded-lg text-sm font-bold"><?php echo htmlspecialchars($msg['motif']); ?></span>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg mb-4 text-gray-700 italic">"<?php echo nl2br(htmlspecialchars($msg['message'])); ?>"</div>

                    <?php if ($view === 'pending'): ?>
                        <form method="POST" class="mt-4 bg-blue-50 p-4 rounded-lg">
                            <input type="hidden" name="reply_id" value="<?php echo $msg['id']; ?>">
                            <label class="block text-sm font-bold text-blue-900 mb-2">Réponse (Email de notif envoyé uniquement) :</label>
                            <textarea name="reponse" class="rich-editor w-full border border-blue-200 rounded-lg p-3 mb-3"></textarea>
                            <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-700">Envoyer et Archiver</button>
                        </form>
                    <?php else: ?>
                        <div class="border-t pt-4 mt-4">
                            <div class="text-gray-800 bg-white border border-green-100 p-3 rounded-lg shadow-sm"><?php echo $msg['reponse']; ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php require_once 'partials/footer.php'; ?>