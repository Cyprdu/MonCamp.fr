<?php
// Fichier: /api/config.php

// Correction: Démarrer la session uniquement si aucune n'est active pour éviter le Notice PHP
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} 

// AJOUT CRITIQUE: Définir l'URL de base pour les redirections Stripe
define('BASE_URL', 'https://moncamp.fr/'); // Assurez-vous que c'est votre URL de base (inclure le / final)

// Configuration de la base de données (XAMPP par défaut)
define('DB_HOST', 'localhost');
define('DB_NAME', 'u632349801_php_ecom');
define('DB_USER', 'u632349801_php_ecom');
define('DB_PASS', 'u632349801_php_ecomA@');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En production, ne jamais afficher l'erreur brute
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de connexion à la base de données']);
    exit;
}

// ----------------------------------------------------
// --- CONFIGURATION STRIPE ---
// ----------------------------------------------------

// 1. Inclure le SDK Stripe (généré par Composer)
// IMPORTANT: Assurez-vous que le chemin est correct. 'vendor' est dans le dossier parent.
require_once __DIR__ . '/../vendor/autoload.php'; 

// 2. Définir les clés API
// REMPLACER ces clés par vos clés de test et de production
define('STRIPE_SECRET_KEY', 'sk_test_51PbovWDbWlNQ0MFBs2Lsdn6YX81EILbhlcamYWDDbTyuMhStiSofSstgEBQLIm2d87jLFNHiAvYJtBQlI7z4twoD00a5YuYDbw'); 
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51PbovWDbWlNQ0MFByW3pfU8j5pfzyJGGQlBG4yKKzC8mTrLegllii9FckHlsZgofsIxpfl7InG7W662ycsW01k3W002c0DsrJt');
define('STRIPE_WEBHOOK_SECRET', 'whsec_Bxj1WDBf5b9zsh7zvgB9tdyiZAz3x0WD'); // Clé du webhook Stripe
define('STRIPE_CURRENCY', 'eur'); 

// 3. Initialiser le client Stripe
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);


// ----------------------------------------------------
// --- FONCTIONS HELPERS ---
// ----------------------------------------------------

/**
 * Fonction helper pour répondre en JSON
 *
 * @param array $data Le tableau de données à encoder en JSON.
 * @param int $code Le code de statut HTTP (par défaut 200).
 */
function sendJson($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}