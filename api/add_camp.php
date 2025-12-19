<?php
// Fichier: api/add_camp.php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

// 1. Vérif Auth
if (!isset($_SESSION['user']['id']) || !$_SESSION['user']['is_directeur']) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

try {
    // 2. Récupération & Nettoyage
    $nom = $_POST['nom'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // NOUVEAUX CHAMPS
    $theme = $_POST['theme'] ?? '';
    $type = $_POST['type'] ?? ''; // Les tags concaténés "Mer, Surf"
    $pays = $_POST['pays'] ?? 'France';
    $itinerant = isset($_POST['itinerant']) ? 1 : 0;
    $video_url = $_POST['video_url'] ?? '';

    $ville = $_POST['ville'] ?? '';
    $adresse = $_POST['adresse'] ?? '';
    $cp = $_POST['cp'] ?? '';
    $date_debut = $_POST['date_debut'] ?? '';
    $date_fin = $_POST['date_fin'] ?? '';
    $organisateur_id = $_POST['organisateur_id'] ?? null;
    
    // Options
    $prive = isset($_POST['prive']) ? 1 : 0;
    $inscription_en_ligne = isset($_POST['inscription_en_ligne']) ? 1 : 0;
    
    // Gestion Prix
    $prix = 0;
    $tarifs = [];
    if ($inscription_en_ligne) {
        $tarifsJson = $_POST['tarifs'] ?? '[]';
        $tarifs = json_decode($tarifsJson, true);
        if (!empty($tarifs)) {
            $prix = min(array_column($tarifs, 'prix'));
        }
    } else {
        $prix = floatval($_POST['prix_simple'] ?? 0);
    }

    // Gestion Image
    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('camp_') . '.' . $ext;
        $uploadDir = '../uploads/camps/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
            $imagePath = 'uploads/camps/' . $filename;
        }
    }

    // Token
    $token = bin2hex(random_bytes(16));

    // 3. INSERTION SQL
    $sql = "INSERT INTO camps (
        organisateur_id, nom, description, 
        theme, type, pays, itinerant, video_url, -- Nouveaux champs
        ville, adresse, code_postal, prix, image_url,
        date_debut, date_fin, 
        inscription_en_ligne, prive, token,
        valide, date_creation, date_bump
    ) VALUES (
        ?, ?, ?, 
        ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, 
        ?, ?, 
        ?, ?, ?,
        1, NOW(), NOW()
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $organisateur_id, $nom, $description,
        $theme, $type, $pays, $itinerant, $video_url,
        $ville, $adresse, $cp, $prix, $imagePath,
        $date_debut, $date_fin,
        $inscription_en_ligne, $prive, $token
    ]);
    
    $campId = $pdo->lastInsertId();

    // 4. INSERTION TARIFS
    if ($inscription_en_ligne && !empty($tarifs)) {
        $stmtT = $pdo->prepare("INSERT INTO camps_tarifs (camp_id, tarif_id) VALUES (?, ?)");
        foreach ($tarifs as $t) {
            $stmtT->execute([$campId, $t['id']]);
        }
    }
    
    // 5. INSERTION PAIEMENTS
    if(isset($_POST['paiements']) && is_array($_POST['paiements'])) {
        $stmtP = $pdo->prepare("INSERT INTO camps_paiements (camp_id, paiement_id) VALUES (?, ?)");
        foreach($_POST['paiements'] as $pid) {
            $stmtP->execute([$campId, $pid]);
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>