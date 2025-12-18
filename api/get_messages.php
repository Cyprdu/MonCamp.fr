<?php
// Fichier : api/get_messages.php
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Non connecté']);
    exit;
}

$myId = $_SESSION['user']['id'];
$convId = $_GET['conv_id'] ?? 0;

if (!$convId) {
    echo json_encode([]);
    exit;
}

try {
    // 1. SÉCURITÉ : Vérifier que je suis participant à cette conversation
    $checkSql = "SELECT id FROM conversations WHERE id = ? AND (user_1_id = ? OR user_2_id = ?)";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$convId, $myId, $myId]);
    
    if ($checkStmt->rowCount() === 0) {
        // L'utilisateur tente d'accéder à une conversation qui n'est pas la sienne !
        http_response_code(403);
        echo json_encode(['error' => 'Accès interdit à cette conversation.']);
        exit;
    }

    // 2. Si c'est bon, on récupère les messages
    $sql = "SELECT m.*, u.prenom, u.nom 
            FROM messages m 
            JOIN users u ON m.sender_id = u.id 
            WHERE m.conversation_id = ? 
            ORDER BY m.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$convId]);
    $messages = $stmt->fetchAll();

    echo json_encode($messages);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>