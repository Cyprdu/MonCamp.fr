<?php
// Fichier : api/get_unread_count.php
require_once 'config.php';

// Si pas connecté, on renvoie 0
if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$myId = $_SESSION['user']['id'];

try {
    // On compte les messages :
    // 1. Qui appartiennent à une conversation où je suis (user_1 ou user_2)
    // 2. Dont je ne suis PAS l'expéditeur (sender_id != moi)
    // 3. Qui ne sont pas lus (is_read = 0)
    
    $sql = "
        SELECT COUNT(m.id) as total
        FROM messages m
        JOIN conversations c ON m.conversation_id = c.id
        WHERE (c.user_1_id = ? OR c.user_2_id = ?) 
        AND m.sender_id != ? 
        AND m.is_read = 0
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$myId, $myId, $myId]);
    $result = $stmt->fetch();

    echo json_encode(['count' => intval($result['total'])]);

} catch (Exception $e) {
    echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
}
?>