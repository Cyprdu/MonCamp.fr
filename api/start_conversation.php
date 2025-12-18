<?php
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) { sendJson(['error' => 'Connectez-vous'], 403); }

$input = json_decode(file_get_contents('php://input'), true);
$targetUserId = $input['organisateurId'] ?? 0; // ID User du Directeur
$campId = $input['campId'] ?? 0; // ID du Camp

if (!$targetUserId || !$campId) { sendJson(['error' => 'Données manquantes'], 400); }

try {
    $myId = $_SESSION['user']['id'];

    // 1. Vérifier si conversation existe déjà POUR CE CAMP
    $sqlCheck = "SELECT id FROM conversations 
                 WHERE camp_id = ? 
                 AND ((user_1_id = ? AND user_2_id = ?) OR (user_1_id = ? AND user_2_id = ?))";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([$campId, $myId, $targetUserId, $targetUserId, $myId]);
    $existing = $stmtCheck->fetch();

    if ($existing) {
        sendJson(['conversationId' => $existing['id']]);
    } else {
        // 2. Créer la conversation liée au CAMP
        $stmtIns = $pdo->prepare("INSERT INTO conversations (user_1_id, user_2_id, camp_id, last_message_at) VALUES (?, ?, ?, NOW())");
        $stmtIns->execute([$myId, $targetUserId, $campId]);
        $newConvId = $pdo->lastInsertId();

        // 3. RECUPERATION INFOS POUR MESSAGE AUTO
        // On récupère le nom du directeur, le nom du camp et l'email de l'organisme
        $stmtInfos = $pdo->prepare("
            SELECT 
                u.prenom as dir_prenom, u.nom as dir_nom,
                c.nom as camp_nom,
                o.email as org_email
            FROM camps c
            JOIN organisateurs o ON c.organisateur_id = o.id
            JOIN users u ON o.user_id = u.id
            WHERE c.id = ?
        ");
        $stmtInfos->execute([$campId]);
        $infos = $stmtInfos->fetch();

        if ($infos) {
            $nomDirecteur = $infos['dir_prenom'] . ' ' . $infos['dir_nom'];
            $nomCamp = $infos['camp_nom'];
            $emailOrg = $infos['org_email'];

            // 4. GENERATION MESSAGE AUTOMATIQUE
            $autoMsg = "Bonjour, vous tentez de contacter $nomDirecteur, directeur du séjour \"$nomCamp\".\n\nVous pouvez échanger ici directement, ou envoyer un email à l'organisme : $emailOrg.";

            // On insère le message comme s'il venait du directeur (targetUserId)
            $stmtMsg = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, content, created_at) VALUES (?, ?, ?, NOW())");
            $stmtMsg->execute([$newConvId, $targetUserId, $autoMsg]);
        }

        sendJson(['conversationId' => $newConvId]);
    }

} catch (Exception $e) { sendJson(['error' => $e->getMessage()], 500); }
?>