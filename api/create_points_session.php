<?php
// Fichier: api/create_points_session.php
require_once 'config.php';
require_once '../vendor/autoload.php';

session_start();
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY); // Assure-toi que cette constante est dans config.php

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$points = intval($input['points'] ?? 0);
$userId = $_SESSION['user']['id'] ?? 0;

if ($points <= 0 || !$userId) {
    echo json_encode(['error' => 'Erreur données']);
    exit;
}

// Logique de prix des packs (Hardcodé pour sécurité)
$amountCentimes = 0;
$packName = "";

if ($points == 100) {
    $amountCentimes = 1000; // 10.00€
    $packName = "Pack Découverte (100 pts)";
} elseif ($points == 600) {
    $amountCentimes = 5000; // 50.00€
    $packName = "Pack Pro (600 pts)";
} elseif ($points == 1500) {
    $amountCentimes = 10000; // 100.00€
    $packName = "Pack Agence (1500 pts)";
} else {
    echo json_encode(['error' => 'Pack invalide']);
    exit;
}

try {
    // Récupérer l'ID organisateur lié au user pour les métadonnées
    $stmt = $pdo->prepare("SELECT id FROM organisateurs WHERE user_id = ?");
    $stmt->execute([$userId]);
    $orgaId = $stmt->fetchColumn();

    if (!$orgaId) throw new Exception("Profil organisateur introuvable");

    $domain = "https://moncamp.fr"; // TODO: Mettre en dynamique

    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $amountCentimes,
                'product_data' => [
                    'name' => $packName,
                    'description' => "Crédits marketing pour MonCamp.fr",
                ],
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => $domain . '/dashboard_organisme.php?success_points=1',
        'cancel_url'  => $domain . '/dashboard_organisme.php',
        'metadata' => [
            'type' => 'buy_points',    // Marqueur pour le webhook
            'organisateur_id' => $orgaId,
            'points_amount' => $points
        ],
    ]);

    echo json_encode(['id' => $checkout_session->id]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>