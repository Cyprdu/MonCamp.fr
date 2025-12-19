<?php
// Fichier: fix_db_final.php
require_once 'api/config.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔧 Réparation Complète de la Base de Données</h1>";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. AJOUT DES COLONNES MANQUANTES SUR 'CAMPS'
    // On définit toutes les colonnes nécessaires avec leur définition SQL
    $colonnes_camps = [
        "theme" => "VARCHAR(255) NULL COMMENT 'Thématique principale'",
        "date_creation" => "DATETIME DEFAULT CURRENT_TIMESTAMP",
        "video_url" => "VARCHAR(255) NULL",
        "solde_points" => "INT DEFAULT 0", // Parfois mis sur camps par erreur, on nettoie pas mais on ignore
        "boost_vedette_fin" => "DATETIME NULL",
        "boost_urgence_fin" => "DATETIME NULL",
        "date_bump" => "DATETIME NULL",
        "supprime" => "TINYINT(1) DEFAULT 0",
        "prive" => "TINYINT(1) DEFAULT 0",
        "token" => "VARCHAR(50) NULL"
    ];

    echo "<h3>Vérification de la table 'camps'...</h3><ul>";
    foreach ($colonnes_camps as $col => $def) {
        try {
            // Tente de sélectionner la colonne pour voir si elle existe
            $pdo->query("SELECT $col FROM camps LIMIT 1");
            echo "<li style='color:gray'>Colonne <b>$col</b> existe déjà.</li>";
        } catch (Exception $e) {
            // Si erreur, elle n'existe pas, on la crée
            try {
                $pdo->exec("ALTER TABLE camps ADD COLUMN $col $def");
                echo "<li style='color:green'>✅ Colonne <b>$col</b> créée avec succès.</li>";
            } catch (Exception $e2) {
                echo "<li style='color:red'>❌ Erreur création $col : " . $e2->getMessage() . "</li>";
            }
        }
    }
    echo "</ul>";

    // 2. AJOUT DES COLONNES SUR 'ORGANISATEURS'
    echo "<h3>Vérification de la table 'organisateurs'...</h3><ul>";
    try {
        $pdo->query("SELECT solde_points FROM organisateurs LIMIT 1");
        echo "<li style='color:gray'>Colonne <b>solde_points</b> existe déjà.</li>";
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE organisateurs ADD COLUMN solde_points INT DEFAULT 0");
        echo "<li style='color:green'>✅ Colonne <b>solde_points</b> créée.</li>";
    }
    
    try {
        $pdo->query("SELECT portefeuille FROM organisateurs LIMIT 1");
        echo "<li style='color:gray'>Colonne <b>portefeuille</b> existe déjà.</li>";
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE organisateurs ADD COLUMN portefeuille DECIMAL(10,2) DEFAULT 0.00");
        echo "<li style='color:green'>✅ Colonne <b>portefeuille</b> créée.</li>";
    }
    echo "</ul>";

    // 3. MISE À JOUR DES DONNÉES (Fix des NULL)
    echo "<h3>Initialisation des données...</h3>";
    
    // Si date_creation est NULL (car vient d'être créé), on met NOW()
    $pdo->exec("UPDATE camps SET date_creation = NOW() WHERE date_creation IS NULL OR date_creation = '0000-00-00 00:00:00'");
    
    // Si date_bump est NULL, on copie date_creation
    $pdo->exec("UPDATE camps SET date_bump = date_creation WHERE date_bump IS NULL");
    
    // Si token est vide, on en génère un basique (temporaire)
    $pdo->exec("UPDATE camps SET token = CONCAT('fix-', id, '-', UUID_SHORT()) WHERE token IS NULL OR token = ''");

    echo "<p style='color:green'>✅ Données dates et tokens initialisées.</p>";

    // 4. CRÉATION DES INDEX (Performance)
    echo "<h3>Optimisation (Index)...</h3>";
    $indices = [
        "idx_camp_token" => "ALTER TABLE camps ADD INDEX idx_camp_token (token)",
        "idx_camp_sorting" => "ALTER TABLE camps ADD INDEX idx_camp_sorting (organisateur_id, date_bump)",
        "idx_camp_supprime" => "ALTER TABLE camps ADD INDEX idx_camp_supprime (supprime)"
    ];

    foreach ($indices as $name => $sql) {
        try {
            $pdo->exec($sql);
            echo "<p style='color:green'>✅ Index $name créé.</p>";
        } catch (Exception $e) {
            echo "<p style='color:gray'>ℹ️ Index $name existe probablement déjà.</p>";
        }
    }

    echo "<h2 style='text-align:center; border:2px solid green; padding:10px; margin-top:20px; color:green'>✨ TOUT EST RÉPARÉ ! ✨</h2>";
    echo "<p style='text-align:center'>Vous pouvez maintenant relancer votre recherche.</p>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>Erreur critique : " . $e->getMessage() . "</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>