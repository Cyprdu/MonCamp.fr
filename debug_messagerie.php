<?php
require_once 'api/config.php';
// ID d'une conversation à tester (à récupérer depuis votre URL ou BDD)
$testConvoId = $_GET['id'] ?? 1; 
?>
<h1>Debug Messagerie SQL</h1>
<?php
if(!isset($_SESSION['user']['id'])) die("Connectez-vous d'abord.");

echo "<p>Test sur la conversation ID: $testConvoId</p>";

try {
    // Vérif Participants
    $stmtPart = $pdo->prepare("SELECT * FROM conversation_participants WHERE conversation_id = ?");
    $stmtPart->execute([$testConvoId]);
    $parts = $stmtPart->fetchAll();
    
    echo "<h3>Participants :</h3><pre>";
    print_r($parts);
    echo "</pre>";

    // Vérif Messages
    $stmtMsg = $pdo->prepare("SELECT * FROM messages WHERE conversation_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmtMsg->execute([$testConvoId]);
    $msgs = $stmtMsg->fetchAll();

    echo "<h3>5 derniers messages :</h3><pre>";
    print_r($msgs);
    echo "</pre>";

} catch (Exception $e) {
    echo "Erreur SQL : " . $e->getMessage();
}
?>