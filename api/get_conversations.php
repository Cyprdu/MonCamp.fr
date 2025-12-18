<?php
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) { sendJson([], 403); }
$myId = $_SESSION['user']['id'];

try {
    // On récupère aussi le nom du camp lié (table camps)
    $sql = "SELECT c.id as conversation_id, c.last_message_at,
            cmp.nom as camp_nom,
            CASE 
                WHEN c.user_1_id = ? THEN u2.nom 
                ELSE u1.nom 
            END as contact_nom,
            CASE 
                WHEN c.user_1_id = ? THEN u2.prenom 
                ELSE u1.prenom 
            END as contact_prenom,
            (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
            (SELECT is_read FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_is_read,
            (SELECT sender_id FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_sender_id
            
            FROM conversations c
            JOIN users u1 ON c.user_1_id = u1.id
            JOIN users u2 ON c.user_2_id = u2.id
            LEFT JOIN camps cmp ON c.camp_id = cmp.id
            WHERE c.user_1_id = ? OR c.user_2_id = ?
            ORDER BY c.last_message_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$myId, $myId, $myId, $myId]);
    $convs = $stmt->fetchAll();

    sendJson($convs);
} catch (Exception $e) { sendJson(['error' => $e->getMessage()], 500); }
?>