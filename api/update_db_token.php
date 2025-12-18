<?php
// update_db_token.php
require_once 'api/config.php';

echo "<h1>Mise à jour de la BDD...</h1>";

try {
    // Ajout de la colonne url_token
    $pdo->exec("ALTER TABLE users ADD COLUMN url_token VARCHAR(25) NULL AFTER email");
    // On ajoute un index UNIQUE pour être sûr
    $pdo->exec("ALTER TABLE users ADD UNIQUE (url_token)");
    
    echo "<p style='color:green'>✅ Colonne 'url_token' ajoutée avec succès.</p>";
} catch (Exception $e) {
    echo "<p style='color:orange'>ℹ️ " . $e->getMessage() . "</p>";
}
?>