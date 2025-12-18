<?php
// Fichier: api/add_camp.php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user']['id']) || !$_SESSION['user']['is_directeur']) {
    header('Location: ../');
    exit;
}

// Fonction pour générer un token aléatoire unique
function generateToken($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// === GESTION IMAGE ===
$imagePath = '';
$maxSize = 5 * 1024 * 1024; // 5 Mo

if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    if ($_FILES['image']['size'] > $maxSize) {
        die("Erreur : L'image est trop volumineuse (Max 5 Mo).");
    }
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($_FILES['image']['type'], $allowedTypes)) {
        die("Erreur : Format d'image invalide.");
    }

    $uploadDir = '../uploads/camps/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('camp_') . '.' . $fileExt;
    $destPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
        $imagePath = 'uploads/camps/' . $fileName;
    } else {
        die("Erreur upload image.");
    }
} else {
    die("Erreur : Image obligatoire.");
}

// === RÉCUPÉRATION DONNÉES ===
$nom = $_POST['nom'];
$description = $_POST['description'];
$ville = $_POST['ville'];
$adresse = $_POST['adresse'] ?? '';
$cp = $_POST['cp'];
$age_min = $_POST['age_min'];
$age_max = $_POST['age_max'];
$date_debut = $_POST['date_debut'];
$date_fin = $_POST['date_fin'];

// L'organisateur est maintenant obligatoire dans tous les cas
$organisateur_id = $_POST['organisateur_id'] ?? null;
if (!$organisateur_id) {
    die("Erreur : L'organisme est obligatoire.");
}

// Point 1 : Gestion Camp Privé
$prive = isset($_POST['prive']) ? 1 : 0;
// Point 2 : Génération Token
$token = generateToken(11); // ex: Xy78kLmP0qZ

$inscription_en_ligne = isset($_POST['inscription_en_ligne']) ? 1 : 0;
$prix_affiche = 0;
$tarifs = [];

// Correction Prix et Tarifs
if ($inscription_en_ligne) {
    $tarifsJson = $_POST['tarifs'] ?? '[]';
    $tarifs = json_decode($tarifsJson, true);
    
    if (!empty($tarifs) && is_array($tarifs)) {
        // On extrait les prix et on prend le plus petit pour l'affichage
        $prixList = array_column($tarifs, 'prix'); 
        if (!empty($prixList)) {
            $prix_affiche = min($prixList);
        }
    }
} else {
    $prix_affiche = $_POST['prix_simple'] ?? 0;
}

// Si le prix est toujours 0 ou vide, on force 0 pour éviter erreur SQL
$prix_affiche = floatval($prix_affiche);

// Quotas & Options
$quota_global = $_POST['quota_global'] ?? 0;
$quota_fille = $_POST['quota_fille'] ?? 0;
$quota_garcon = $_POST['quota_garcon'] ?? 0;
$remise_fratrie = $_POST['remise_fratrie'] ?? 0;
$date_limite = !empty($_POST['date_limite_inscription']) ? $_POST['date_limite_inscription'] : null;
$lien_externe = $_POST['lien_externe'] ?? '';
$adresse_retour = $_POST['adresse_retour_dossier'] ?? '';

// Animateurs
$gestion_anim = isset($_POST['gestion_animateur']) ? 1 : 0;
$quota_max_anim = $_POST['quota_max_anim'] ?? 0;
$quota_anim_fille = $_POST['quota_anim_fille'] ?? 0;
$quota_anim_garcon = $_POST['quota_anim_garcon'] ?? 0;
$anim_plus_18 = isset($_POST['anim_plus_18']) ? 1 : 0;
$bafa_obligatoire = isset($_POST['bafa_obligatoire']) ? 1 : 0;
$remuneration_anim = isset($_POST['remuneration_anim']) ? 1 : 0;
$anim_doit_payer = isset($_POST['anim_doit_payer']) ? 1 : 0;
$prix_anim = ($anim_doit_payer) ? ($_POST['prix_anim'] ?? 0) : 0;

try {
    $sql = "INSERT INTO camps (
        nom, description, ville, adresse, code_postal, prix, image_url,
        age_min, age_max, date_debut, date_fin, 
        inscription_en_ligne, inscription_hors_ligne,
        organisateur_id, date_limite_inscription, remise_fratrie,
        quota_global, quota_fille, quota_garcon,
        lien_externe, adresse_retour_dossier,
        gestion_animateur, quota_max_anim, quota_anim_fille, quota_anim_garcon,
        anim_plus_18, bafa_obligatoire, remuneration_anim, anim_doit_payer, prix_anim,
        valide, en_attente, refuse,
        prive, token 
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, 
        ?, ?, ?,
        ?, ?, ?,
        ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        0, 1, 0,
        ?, ?
    )";
    
    $hors_ligne = $inscription_en_ligne ? 0 : 1;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $nom, $description, $ville, $adresse, $cp, $prix_affiche, $imagePath,
        $age_min, $age_max, $date_debut, $date_fin,
        $inscription_en_ligne, $hors_ligne,
        $organisateur_id, $date_limite, $remise_fratrie,
        $quota_global, $quota_fille, $quota_garcon,
        $lien_externe, $adresse_retour,
        $gestion_anim, $quota_max_anim, $quota_anim_fille, $quota_anim_garcon,
        $anim_plus_18, $bafa_obligatoire, $remuneration_anim, $anim_doit_payer, $prix_anim,
        $prive, $token
    ]);
    
    $campId = $pdo->lastInsertId();

    // Insertion des Tarifs (si inscription en ligne)
    if ($inscription_en_ligne && !empty($tarifs)) {
        $sqlTarif = "INSERT INTO camps_tarifs (camp_id, tarif_id) VALUES (?, ?)";
        $stmtTarif = $pdo->prepare($sqlTarif);
        foreach ($tarifs as $t) {
            $stmtTarif->execute([$campId, $t['id']]);
        }
    }

    // NOUVEAU : Insertion des moyens de paiement acceptés
    if (isset($_POST['paiements']) && is_array($_POST['paiements'])) {
        $sqlPaiement = "INSERT INTO camps_paiements (camp_id, paiement_id) VALUES (?, ?)";
        $stmtPaiement = $pdo->prepare($sqlPaiement);
        foreach ($_POST['paiements'] as $paiementId) {
            $stmtPaiement->execute([$campId, $paiementId]);
        }
    }

    // Redirection vers l'URL sécurisée
    header("Location: ../camp_details.php?t=" . $token);
    exit;

} catch (Exception $e) {
    die("Erreur SQL : " . $e->getMessage());
}
?>