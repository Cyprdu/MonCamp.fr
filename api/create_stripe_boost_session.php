<?php
// Fichier: api/create_stripe_boost_session.php
require_once 'config.php';
require_once '../vendor/autoload.php'; // Assurez-vous que l'autoload Composer est là pour Stripe

// Configuration Stripe (Utilisez vos clés API Secrètes)
\Stripe\Stripe::setApiKey('sk_test_...'); // REMPLACEZ PAR VOTRE CLÉ SECRÈTE

header('Content-Type: application/json');

// Récupération JSON
$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$points = intval($input['points'] ?? 0);

if (empty($token) || $points <= 0) {
    echo json_encode(['error' => 'Données invalides']);
    exit;
}

// Vérification du camp en BDD
$stmt = $pdo->prepare("SELECT id, nom FROM camps WHERE token = ?");
$stmt->execute([$token]);
$camp = $stmt->fetch();

if (!$camp) {
    echo json_encode(['error' => 'Séjour introuvable']);
    exit;
}

// --- CALCUL DU PRIX (LOGIQUE MÉTIER) ---
// On calcule tout en centimes pour Stripe
$amountCentimes = 0;

if ($points == 100) {
    $amountCentimes = 499; // 4.99€
} elseif ($points == 500) {
    $amountCentimes = 999; // 9.99€
} elseif ($points == 1000) {
    $amountCentimes = 1499; // 14.99€
} elseif ($points > 1000) {
    // Formule : Base 14.99€ + 0.01€ par point supplémentaire
    // 1499 centimes + (points supp * 1 centime)
    // Incrément de 10 géré par le frontend, mais on accepte tout > 1000 ici
    $extraPoints = $points - 1000;
    $amountCentimes = 1499 + ($extraPoints * 1); // 1 centime par point
} else {
    echo json_encode(['error' => 'Montant de points invalide']);
    exit;
}

// Nom du produit pour Stripe
$productName = "Boost Visibilité: " . $points . " points";
$productDesc = "Pour le séjour: " . $camp['nom'];

try {
    $domain = "https://votre-site.com"; // REMPLACEZ PAR VOTRE URL EN PROD

    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $amountCentimes, // Montant calculé
                'product_data' => [
                    'name' => $productName,
                    'description' => $productDesc,
                ],
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => $domain . '/boost.php?t=' . $token . '&success=true',
        'cancel_url' => $domain . '/boost.php?t=' . $token,
        'metadata' => [
            'type' => 'boost_camp',      // IDENTIFIANT IMPORTANT POUR LE WEBHOOK
            'camp_id' => $camp['id'],    // ID du camp à créditer
            'camp_token' => $token,
            'points_amount' => $points   // Nombre de points à ajouter
        ],
    ]);

    echo json_encode(['id' => $checkout_session->id]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>