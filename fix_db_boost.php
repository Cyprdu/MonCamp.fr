<?php
// Fichier: fix_db_boost.php
require_once 'api/config.php';
echo "<h1>Réparation de la Base de Données...</h1>";

try {
    // 1. Ajout colonne 'solde_points' sur organisateurs
    try {
        $pdo->exec("ALTER TABLE organisateurs ADD COLUMN solde_points INT DEFAULT 0");
        echo "<p style='color:green'>✅ Colonne 'solde_points' ajoutée.</p>";
    } catch (Exception $e) {
        echo "<p style='color:orange'>ℹ️ Colonne 'solde_points' existe déjà.</p>";
    }

    // 2. Ajout colonnes Boost sur camps
    $cols = [
        "boost_vedette_fin" => "DATETIME NULL COMMENT 'Fin Vedette'",
        "boost_urgence_fin" => "DATETIME NULL COMMENT 'Fin Urgence'",
        "date_bump" => "DATETIME NULL COMMENT 'Date de tri'",
        "supprime" => "TINYINT(1) DEFAULT 0",
        "video_url" => "VARCHAR(255) NULL"
    ];

    foreach ($cols as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE camps ADD COLUMN $col $def");
            echo "<p style='color:green'>✅ Colonne '$col' ajoutée.</p>";
        } catch (Exception $e) {
            echo "<p style='color:orange'>ℹ️ Colonne '$col' existe déjà.</p>";
        }
    }

    // 3. Initialisation de date_bump (CRITIQUE pour le tri)
    $pdo->exec("UPDATE camps SET date_bump = date_creation WHERE date_bump IS NULL");
    echo "<p style='color:green'>✅ Dates de tri (date_bump) initialisées.</p>";

    // 4. Ajout des Index (CRITIQUE pour la vitesse)
    try {
        $pdo->exec("ALTER TABLE camps ADD INDEX idx_camp_token (token)");
        $pdo->exec("ALTER TABLE camps ADD INDEX idx_camp_sorting (organisateur_id, date_bump)");
        echo "<p style='color:green'>✅ Index de performance créés.</p>";
    } catch (Exception $e) {
         echo "<p style='color:orange'>ℹ️ Index déjà présents.</p>";
    }

    echo "<h2>✨ Terminé ! Vous pouvez supprimer ce fichier.</h2>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>Erreur critique : " . $e->getMessage() . "</h2>";
}
?>