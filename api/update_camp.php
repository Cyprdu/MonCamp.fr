<?php
// Fichier: /api/update_camp.php
require_once 'config.php';

// 1. Sécurité : Directeur uniquement
if (!isset($_SESSION['user']['id']) || !$_SESSION['user']['is_directeur']) {
    // Redirection ou erreur si accès direct
    die("Accès interdit.");
}

// On vérifie que des données sont envoyées
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Méthode non autorisée.");
}

$campId = $_POST['camp_id'] ?? 0;

if (!$campId) {
    die("Identifiant du camp manquant.");
}

try {
    // 2. VÉRIFICATION PROPRIÉTAIRE (CRUCIAL)
    // On vérifie que ce camp appartient bien à un organisateur géré par l'utilisateur connecté
    $stmtCheck = $pdo->prepare("
        SELECT c.id, c.image_url 
        FROM camps c 
        JOIN organisateurs o ON c.organisateur_id = o.id 
        WHERE c.id = ? AND o.user_id = ?
    ");
    $stmtCheck->execute([$campId, $_SESSION['user']['id']]);
    $currentCamp = $stmtCheck->fetch();

    if (!$currentCamp) {
        die("Erreur : Ce camp n'existe pas ou ne vous appartient pas.");
    }

    // 3. GESTION IMAGE (On garde l'ancienne si pas de nouvelle)
    $imagePath = $currentCamp['image_url']; 
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploadDir = '../uploads/camps/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('camp_') . '.' . $ext;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
            $imagePath = 'uploads/camps/' . $fileName;
            // Optionnel : supprimer l'ancienne image ici pour nettoyer le serveur
        }
    }

    // 4. PRÉPARATION DES DONNÉES
    // Checkbox HTML : si pas cochée, elle n'est pas envoyée dans le POST, donc on met 0
    $prive = isset($_POST['prive']) ? 1 : 0;
    $inscription_en_ligne = isset($_POST['inscription_en_ligne']) ? 1 : 0;
    $inscription_hors_ligne = $inscription_en_ligne ? 0 : 1;

    // Calcul du prix d'appel (le plus bas des tarifs ou le prix simple)
    $prix_affiche = 0;
    $tarifs = [];
    
    if ($inscription_en_ligne) {
        $tarifs = json_decode($_POST['tarifs'] ?? '[]', true);
        if (!empty($tarifs)) {
            // On prend le prix le plus bas pour l'affichage "A partir de..."
            $minPrice = null;
            foreach($tarifs as $t) {
                // On ignore les montants libres (0) pour le "à partir de", sauf s'il n'y a que ça
                if($t['price'] > 0) {
                    if($minPrice === null || $t['price'] < $minPrice) $minPrice = $t['price'];
                }
            }
            $prix_affiche = $minPrice ?? 0;
        }
    } else {
        $prix_affiche = $_POST['prix_simple'] ?? 0;
    }

    // 5. UPDATE SQL (C'est ici que la magie opère : on MODIFIE la ligne existante)
    // Note : On ne touche PAS à la colonne 'vues' ni 'date_creation'
    $sql = "UPDATE camps SET 
        nom = ?, 
        description = ?, 
        ville = ?, 
        adresse = ?, 
        code_postal = ?, 
        prix = ?, 
        image_url = ?,
        age_min = ?, 
        age_max = ?, 
        date_debut = ?, 
        date_fin = ?,
        inscription_en_ligne = ?, 
        inscription_hors_ligne = ?,
        organisateur_id = ?, 
        date_limite_inscription = ?, 
        remise_fratrie = ?,
        quota_global = ?, 
        quota_fille = ?, 
        quota_garcon = ?,
        lien_externe = ?, 
        adresse_retour_dossier = ?,
        gestion_animateur = ?, 
        quota_max_anim = ?, 
        quota_anim_fille = ?, 
        quota_anim_garcon = ?,
        anim_plus_18 = ?, 
        bafa_obligatoire = ?, 
        remuneration_anim = ?, 
        anim_doit_payer = ?, 
        prix_anim = ?,
        prive = ?,
        
        -- IMPORTANT : On remet le statut en 'Attente de validation' car le contenu a changé
        valide = 0, 
        en_attente = 1,
        refuse = 0
        
        WHERE id = ?"; // On cible l'ID existant

    $stmt = $pdo->prepare($sql);
    
    $params = [
        $_POST['nom'], 
        $_POST['description'], // Si vous avez renommé le champ 'activites' en 'description' dans le HTML
        $_POST['ville'], 
        $_POST['adresse'], 
        $_POST['cp'], 
        $prix_affiche, 
        $imagePath,
        $_POST['age_min'], 
        $_POST['age_max'], 
        $_POST['date_debut'], 
        $_POST['date_fin'],
        $inscription_en_ligne, 
        $inscription_hors_ligne,
        $_POST['organisateur_id'], 
        !empty($_POST['date_limite_inscription']) ? $_POST['date_limite_inscription'] : null, 
        $_POST['remise_fratrie'],
        $_POST['quota_global'], 
        $_POST['quota_fille'], 
        $_POST['quota_garcon'],
        $_POST['lien_externe'], 
        $_POST['adresse_retour_dossier'],
        (isset($_POST['gestion_animateur']) ? 1 : 0), 
        $_POST['quota_max_anim'], 
        $_POST['quota_anim_fille'], 
        $_POST['quota_anim_garcon'],
        (isset($_POST['anim_plus_18']) ? 1 : 0), 
        (isset($_POST['bafa_obligatoire']) ? 1 : 0), 
        (isset($_POST['remuneration_anim']) ? 1 : 0), 
        (isset($_POST['anim_doit_payer']) ? 1 : 0), 
        $_POST['prix_anim'],
        $prive,
        $campId // L'ID pour le WHERE
    ];

    $stmt->execute($params);

    // 6. MISE À JOUR DES TARIFS (Suppression anciens -> Ajout nouveaux)
    // On supprime les liens tarifs existants pour ce camp
    $pdo->prepare("DELETE FROM camps_tarifs WHERE camp_id = ?")->execute([$campId]);
    
    // On remet les nouveaux
    if ($inscription_en_ligne && !empty($tarifs)) {
        $stmtTarif = $pdo->prepare("INSERT INTO camps_tarifs (camp_id, tarif_id) VALUES (?, ?)");
        foreach ($tarifs as $t) {
            $stmtTarif->execute([$campId, $t['id']]);
        }
    }

    // 7. REDIRECTION
    header("Location: ../mes_camps.php?success=updated");
    exit;

} catch (Exception $e) {
    die("Erreur lors de la mise à jour : " . $e->getMessage());
}
?>