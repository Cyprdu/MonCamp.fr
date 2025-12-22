<?php
// Fichier: api/create_stripe_boost_session.php
require_once 'config.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$packKey = $input['pack'] ?? ''; 
$token = $input['token'] ?? null;
$organisateurId = $input['organisateur_id'] ?? null;

if (empty($packKey) || (!$token && !$organisateurId)) {
    echo json_encode(['error' => 'Données manquantes']);
    exit;
}

// Logique pour déterminer la cible (Organisme) et l'URL de retour
$nomStructure = "Votre Compte";
$successUrl = "";
$cancelUrl = "";
$finalOrgaId = 0;

$baseUrl = defined('BASE_URL') ? BASE_URL : 'https://moncamp.fr/';
$baseUrl = rtrim($baseUrl, '/');

try {
    if ($token) {
        // CAS 1 : Achat depuis la page d'un camp (boost.php)
        $stmt = $pdo->prepare("
            SELECT c.organisateur_id, o.nom, c.token 
            FROM camps c 
            JOIN organisateurs o ON c.organisateur_id = o.id 
            WHERE c.token = ?
        ");
        $stmt->execute([$token]);
        $data = $stmt->fetch();
        
        if (!$data) throw new Exception("Séjour introuvable");
        
        $finalOrgaId = $data['organisateur_id'];
        $nomStructure = $data['nom'];
        
        $successUrl = $baseUrl . '/boost.php?t=' . $token . '&success=true';
        $cancelUrl = $baseUrl . '/boost.php?t=' . $token;

    } elseif ($organisateurId) {
        // CAS 2 : Achat depuis le dashboard marketing (marketing.php)
        $stmt = $pdo->prepare("SELECT id, nom FROM organisateurs WHERE id = ? AND user_id = ?");
        $stmt->execute([$organisateurId, $_SESSION['user']['id']]);
        $data = $stmt->fetch();

        if (!$data) throw new Exception("Organisme introuvable ou non autorisé");

        $finalOrgaId = $data['id'];
        $nomStructure = $data['nom'];
        
        $successUrl = $baseUrl . '/marketing.php?id=' . $organisateurId . '&success=true';
        $cancelUrl = $baseUrl . '/marketing.php?id=' . $organisateurId;
    }

    // --- PRIX DES PACKS (Centimes) ---
    $packs = [
        'decouverte' => ['name' => 'Pack Découverte', 'points' => 100, 'amount' => 1000], // 10.00€
        'standard'   => ['name' => 'Pack Standard',   'points' => 600, 'amount' => 5000], // 50.00€
        'agence'     => ['name' => 'Pack Agence',     'points' => 1500, 'amount' => 10000] // 100.00€
    ];

    if (!isset($packs[$packKey])) throw new Exception("Pack invalide");
    $pack = $packs[$packKey];

    // Session Stripe
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $pack['amount'],
                'product_data' => [
                    'name' => $pack['name'] . " (" . $pack['points'] . " pts)",
                    'description' => "Crédits marketing pour : $nomStructure.",
                ],
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
        'metadata' => [
            'type' => 'buy_points', 
            'organisateur_id' => $finalOrgaId, // ID vital pour le webhook
            'points_amount' => $pack['points']
        ],
    ]);

    echo json_encode(['id' => $checkout_session->id]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>