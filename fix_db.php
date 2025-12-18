<?php
// fix_db.php
require_once 'api/config.php';

echo "<h1>Réparation de la Base de Données...</h1>";

try {
    // 1. Ajouter la colonne 'verification_token' si elle manque
    echo "<p>Tentative d'ajout de 'verification_token'...</p>";
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) NULL AFTER password");
        echo "<p style='color:green'>✅ Colonne 'verification_token' ajoutée.</p>";
    } catch (Exception $e) {
        echo "<p style='color:orange'>ℹ️ " . $e->getMessage() . " (Existe probablement déjà)</p>";
    }

    // 2. Ajouter la colonne 'is_verified' si elle manque
    echo "<p>Tentative d'ajout de 'is_verified'...</p>";
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0 AFTER verification_token");
        echo "<p style='color:green'>✅ Colonne 'is_verified' ajoutée.</p>";
    } catch (Exception $e) {
        echo "<p style='color:orange'>ℹ️ " . $e->getMessage() . " (Existe probablement déjà)</p>";
    }

    // 3. IMPORTANT : Valider tous les comptes EXISTANTS
    // Sinon vous resterez bloqué dehors !
    echo "<p>Validation des anciens comptes...</p>";
    $stmt = $pdo->exec("UPDATE users SET is_verified = 1 WHERE is_verified = 0 OR is_verified IS NULL");
    echo "<p style='color:green'>✅ $stmt utilisateurs existants ont été marqués comme vérifiés (Vous pouvez vous reconnecter).</p>";

    echo "<h2>✨ Opération terminée !</h2>";
    echo "<p><a href='login.php'>Cliquez ici pour vous connecter</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>Erreur critique :</h2>";
    echo $e->getMessage();
}
?>