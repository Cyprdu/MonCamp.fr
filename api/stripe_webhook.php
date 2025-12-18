<?php
// Fichier: api/stripe_webhook.php
// Webhook Stripe pour valider les inscriptions et créditer l'organisateur.

require_once 'config.php'; 
require_once '../vendor/autoload.php'; // Charge la librairie Stripe si installée via Composer

// Configuration
$webhookSecret = STRIPE_WEBHOOK_SECRET; 

// Récupération du payload
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$event = null;

// 1. Vérification de la signature (Sécurité Critique)
try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $webhookSecret
    );
} catch (\UnexpectedValueException $e) {
    http_response_code(400); // Payload invalide
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400); // Signature invalide
    exit();
} catch (Exception $e) {
    error_log("Stripe Webhook Error: " . $e->getMessage());
    http_response_code(500);
    exit();
}

// 2. Traitement de l'événement
if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;

    // Vérifier que le paiement est bien réussi
    if ($session->payment_status === 'paid') {
        
        $payment_intent_id = $session->payment_intent;
        $montant_total_eur = $session->amount_total / 100; // Stripe est en centimes
        $metadata_tokens = $session->metadata->reservation_token ?? null;

        if (!$metadata_tokens) {
            error_log("Webhook: Token de réservation manquant pour la session " . $session->id);
            http_response_code(200);
            exit();
        }

        // Conversion de la chaîne "token1,token2" en tableau
        $tokens = explode(',', $metadata_tokens);
        // Nettoyage des tokens (espaces éventuels)
        $tokens = array_map('trim', $tokens);
        $tokens = array_filter($tokens); // Retire les entrées vides

        if (empty($tokens)) {
            http_response_code(200);
            exit();
        }

        try {
            $pdo->beginTransaction();

            // ---------------------------------------------------------
            // A. VÉRIFICATION IDEMPOTENCE (Anti-Doublon)
            // ---------------------------------------------------------
            // On vérifie si ce PaymentIntent a déjà été traité en base.
            // Si oui, on arrête tout pour ne pas créditer le solde 2 fois.
            $stmtCheck = $pdo->prepare("SELECT id FROM inscriptions WHERE stripe_payment_intent_id = ? LIMIT 1");
            $stmtCheck->execute([$payment_intent_id]);
            if ($stmtCheck->fetch()) {
                // Déjà traité, on valide juste la réception
                $pdo->rollBack();
                http_response_code(200);
                exit();
            }

            // ---------------------------------------------------------
            // B. RÉCUPÉRATION DE L'ORGANISATEUR
            // ---------------------------------------------------------
            // On prend le premier token pour trouver le camp et l'organisateur.
            // (Tous les enfants d'un même paiement vont au même camp/organisateur).
            $firstToken = $tokens[0];
            $stmtOrga = $pdo->prepare("
                SELECT c.organisateur_id 
                FROM inscriptions i 
                JOIN camps c ON i.camp_id = c.id 
                WHERE i.reservation_token = ?
            ");
            $stmtOrga->execute([$firstToken]);
            $organisateur_id = $stmtOrga->fetchColumn();

            // ---------------------------------------------------------
            // C. MISE À JOUR DES INSCRIPTIONS
            // ---------------------------------------------------------
            // On prépare la requête pour mettre à jour tous les enfants concernés.
            // On définit le montant_paye égal au prix_final (car payé intégralement).
            $sqlUpdate = "UPDATE inscriptions 
                          SET statut = 'Confirmé', 
                              statut_paiement = 'PAYE', 
                              stripe_payment_intent_id = ?, 
                              mode_paiement = 'CARTE',
                              montant_paye = prix_final 
                          WHERE reservation_token = ?";
            
            $stmtUpdate = $pdo->prepare($sqlUpdate);

            foreach ($tokens as $token) {
                $stmtUpdate->execute([$payment_intent_id, $token]);
            }

            // ---------------------------------------------------------
            // D. CRÉDITER LE PORTEFEUILLE ORGANISATEUR
            // ---------------------------------------------------------
            if ($organisateur_id) {
                // On crédite le montant TOTAL de la session Stripe
                $sqlWallet = "UPDATE organisateurs SET portefeuille = portefeuille + ? WHERE id = ?";
                $stmtWallet = $pdo->prepare($sqlWallet);
                $stmtWallet->execute([$montant_total_eur, $organisateur_id]);
            }

            $pdo->commit();

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Webhook DB Error: " . $e->getMessage());
            http_response_code(500); // Stripe réessaiera plus tard
            exit();
        }
    }
}

// Réponse OK à Stripe
http_response_code(200);
?>