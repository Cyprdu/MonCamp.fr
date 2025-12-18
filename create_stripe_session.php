<?php
// api/create_stripe_session_multi.php

require_once 'config.php';

// Sécurité
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['error' => 'Méthode non autorisée.'], 405);
}
if (!isset($_SESSION['user'])) {
    sendJson(['error' => 'Authentification requise.'], 401);
}

$userId = $_SESSION['user']['id'];
$input = json_decode(file_get_contents('php://input'), true);

$campToken = $input['camp_token'] ?? '';
$campId = filter_var($input['camp_id'], FILTER_VALIDATE_INT);
$inscriptionsData = $input['inscriptions'] ?? [];
$totalAmountFloat = filter_var($input['total_amount'], FILTER_VALIDATE_FLOAT);

if (!$campId || empty($inscriptionsData) || $totalAmountFloat === false || $totalAmountFloat <= 0) {
    sendJson(['error' => 'Données d\'inscription incomplètes ou montant invalide.'], 400);
}

// Montant total en centimes pour Stripe
$montantTotalCents = round($totalAmountFloat * 100);

try {
    // A. Récupérer les données critiques du camp et de l'organisateur
    $sqlCamp = "SELECT id, nom, organisateur_id FROM camps WHERE token = ? AND id = ?";
    $stmtCamp = $pdo->prepare($sqlCamp);
    $stmtCamp->execute([$campToken, $campId]);
    $camp = $stmtCamp->fetch();

    if (!$camp) {
        sendJson(['error' => 'Camp introuvable.'], 404);
    }
    
    // -------------------------------------------------------------------
    // B. GÉNÉRATION SÉCURISÉE DU TOKEN UNIQUE
    // Boucle pour garantir que le token n'existe pas déjà (même après collision ou crash)
    // -------------------------------------------------------------------
    $reservationToken = '';
    $maxAttempts = 5;
    $isUnique = false;
    
    for ($i = 0; $i < $maxAttempts; $i++) {
        $reservationToken = bin2hex(random_bytes(30)); 
        
        $stmtCheck = $pdo->prepare("SELECT 1 FROM inscriptions WHERE reservation_token = ?");
        $stmtCheck->execute([$reservationToken]);
        
        if ($stmtCheck->rowCount() === 0) {
            $isUnique = true;
            break; // Token unique trouvé
        }
    }
    
    if (!$isUnique) {
        // Si 5 tentatives échouent (extrêmement rare), on arrête le processus.
        sendJson(['error' => 'Impossible de générer un token de réservation unique. Veuillez réessayer.'], 500);
    }

    // --- Début de la transaction ---
    $pdo->beginTransaction();

    $allLineItems = [];

    // C. Création des inscriptions temporaires dans la BDD
    // Le token est déjà garanti unique ici, on devrait donc pas avoir de 23000 sur l'insertion suivante.
    $sqlInsert = "
        INSERT INTO inscriptions (camp_id, enfant_id, statut, prix_final, tarif_id, statut_paiement, reservation_token)
        VALUES (?, ?, 'Confirmé', ?, ?, 'EN_ATTENTE', ?)
    ";
    $stmtInsert = $pdo->prepare($sqlInsert);

    foreach ($inscriptionsData as $item) {
        $enfantId = filter_var($item['child_id'], FILTER_VALIDATE_INT);
        $tarifId = filter_var($item['tarif_id'], FILTER_VALIDATE_INT);
        $price = filter_var($item['price'], FILTER_VALIDATE_FLOAT);
        $priceCents = round($price * 100);
        
        if (!$enfantId || !$tarifId || $price === false) {
             $pdo->rollBack();
             sendJson(['error' => 'Erreur de validation des données enfant/tarif.'], 400);
        }

        // Insérer l'inscription temporaire (toutes avec le même reservation_token)
        // Le reservation_token est utilisé ici.
        $stmtInsert->execute([
            $campId, 
            $enfantId, 
            $price, 
            $tarifId, 
            $reservationToken
        ]);
        
        // Préparer les items pour la session Stripe
        $allLineItems[] = [
            'price_data' => [
                'currency' => STRIPE_CURRENCY,
                'unit_amount' => $priceCents,
                'product_data' => [
                    'name' => utf8_decode('Inscription: Enfant ID ' . $enfantId . ' au camp ' . $camp['nom']), 
                ],
            ],
            'quantity' => 1,
        ];
    }
    
    // D. Création de la Session Checkout Stripe
    // ... (Le reste du code Stripe est inchangé)
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $allLineItems,
        'mode' => 'payment',
        
        'success_url' => BASE_URL . 'inscription_confirmation.php?token=' . urlencode($reservationToken) . '&status=success',
        'cancel_url' => BASE_URL . 'inscription_confirmation.php?token=' . urlencode($reservationToken) . '&status=cancel', 
        
        'metadata' => [
            'reservation_token' => $reservationToken, 
            'camp_id' => $campId,
            'organisateur_id_local' => $camp['organisateur_id'],
            'user_id' => $userId,
            'total_amount_eur' => $totalAmountFloat,
        ],
    ]);
    
    // E. Mise à jour de toutes les inscriptions temporaires avec l'ID de session Stripe
    $sqlUpdateSession = "UPDATE inscriptions SET stripe_session_id = ? WHERE reservation_token = ?";
    $stmtUpdateSession = $pdo->prepare($sqlUpdateSession);
    $stmtUpdateSession->execute([$checkout_session->id, $reservationToken]);

    $pdo->commit();

    // Renvoyer l'ID de session au client pour la redirection
    sendJson(['id' => $checkout_session->id]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur Stripe: " . $e->getMessage());
    sendJson(['error' => 'Erreur de serveur lors de la création du paiement: ' . $e->getMessage()], 500);
}
?>