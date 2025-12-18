<?php
// Fichier: api/google_callback.php
session_start();
require_once 'config.php';
require_once 'google_config.php';

if (isset($_GET['code'])) {
    // 1. Echange du code contre le token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);

        // 2. Récupération des infos Google
        $google_oauth = new Google_Service_Oauth2($client);
        
        try {
            $google_account_info = $google_oauth->userinfo->get();
        } catch (Exception $e) {
            header('Location: ../login.php?error=google_api_error');
            exit();
        }

        // 3. Extraction des données
        $google_id = $google_account_info->id;
        $email = $google_account_info->email;
        $prenom = $google_account_info->givenName;
        $nom = $google_account_info->familyName;
        $photo = $google_account_info->picture;

        // 4. Traitement en Base de données
        try {
            // On vérifie si l'email existe déjà
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // --- CAS A : L'UTILISATEUR EXISTE DÉJÀ ---
                // On met à jour l'ID Google, la photo
                // ET on force is_verified à 1 (car Google a prouvé que c'est son email)
                
                $stmt = $pdo->prepare("UPDATE users SET google_id = ?, photo_url = ?, is_verified = 1 WHERE id = ?");
                $stmt->execute([$google_id, $photo, $user['id']]);
                
                // On recharge l'user mis à jour
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user['id']]);
                $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);

            } else {
                // --- CAS B : NOUVEL UTILISATEUR ---
                // On l'insère SANS la colonne 'role' qui n'existe pas.
                // On active le compte (is_active = 1) et on vérifie l'email (is_verified = 1)
                
                $sql = "INSERT INTO users (nom, prenom, email, google_id, photo_url, is_active, is_verified) 
                        VALUES (?, ?, ?, ?, ?, 1, 1)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nom, $prenom, $email, $google_id, $photo]);
                
                // On récupère le nouvel user pour la session
                $new_id = $pdo->lastInsertId();
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$new_id]);
                $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            header('Location: ../index.php');
            exit();

        } catch (PDOException $e) {
            die("Erreur SQL : " . $e->getMessage());
        }

    } else {
        header('Location: ../login.php?error=google_auth_failed');
        exit();
    }
} else {
    header('Location: ../login.php');
    exit();
}
?>