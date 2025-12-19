<?php
// Fichier: api/stripe_webhook.php
// Webhook Stripe : Gère les Inscriptions (Euros) et l'Achat de Points (Crédits)

require_once 'config.php';
require_once '../vendor/autoload.php'; // Charge la librairie Stripe

// Configuration
// Assurez-vous que cette constante est définie dans config.php ou remplacez-la par votre clé 'whsec_...'
$webhookSecret = defined('STRIPE_WEBHOOK_SECRET') ? STRIPE_WEBHOOK_SECRET : 'whsec_votre_cle_secrete_webhook';

// Récupération du payload brut
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$event = null;

// ====================================================================
// 1. VÉRIFICATION DE SÉCURITÉ (Signature Stripe)
// ====================================================================
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

// ====================================================================
// 2. TRAITEMENT DE L'ÉVÉNEMENT
// ====================================================================
if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;

    // On s'assure que le paiement est bien validé ('paid')
    if ($session->payment_status === 'paid') {
        
        $payment_intent_id = $session->payment_intent;
        $metadata = $session->metadata;

        // Connexion BDD (via config.php normalement, mais on s'assure de l'accès)
        global $pdo; 

        // --------------------------------------------------------------------
        // CAS A : ACHAT DE PACK DE POINTS (Boost Marketing)
        // --------------------------------------------------------------------
        if (isset($metadata->type) && $metadata->type === 'buy_points') {
            
            $orgaId = isset($metadata->organisateur_id) ? intval($metadata->organisateur_id) : 0;
            $pointsToAdd = isset($metadata->points_amount) ? intval($metadata->points_amount) : 0;

            if ($orgaId > 0 && $pointsToAdd > 0) {
                try {
                    $pdo->beginTransaction();

                    // Mise à jour du solde de points de l'organisateur
                    // COALESCE gère le cas où solde_points serait NULL
                    $stmt = $pdo->prepare("UPDATE organisateurs SET solde_points = COALESCE(solde_points, 0) + ? WHERE id = ?");
                    $stmt->execute([$pointsToAdd, $orgaId]);

                    // (Optionnel) Ici, vous pourriez insérer une ligne dans une table 'factures_points' pour la compta

                    $pdo->commit();
                    error_log("WEBHOOK SUCCESS: $pointsToAdd points ajoutés à l'organisateur $orgaId.");

                } catch (Exception $e) {
                    $pdo->rollBack();
                    error_log("WEBHOOK ERROR (Points): " . $e->getMessage());
                    http_response_code(500); // Demande à Stripe de réessayer plus tard
                    exit();
                }
            } else {
                error_log("WEBHOOK ERROR: Données manquantes pour l'achat de points.");
            }
        }

        // --------------------------------------------------------------------
        // CAS B : INSCRIPTION ENFANT (Réservation de séjour)
        // --------------------------------------------------------------------
        elseif (isset($metadata->reservation_token)) {
            
            $montant_total_eur = $session->amount_total / 100; // Stripe envoie des centimes, on convertit en Euros
            $metadata_tokens = $metadata->reservation_token;

            // Conversion de la chaîne "token1,token2" en tableau
            $tokens = explode(',', $metadata_tokens);
            $tokens = array_map('trim', $tokens);
            $tokens = array_filter($tokens); // Nettoyage

            if (!empty($tokens)) {
                try {
                    $pdo->beginTransaction();

                    // 1. IDEMPOTENCE : On vérifie si ce paiement a déjà été traité
                    // On regarde si une inscription porte déjà ce PaymentIntentID
                    $stmtCheck = $pdo->prepare("SELECT id FROM inscriptions WHERE stripe_payment_intent_id = ? LIMIT 1");
                    $stmtCheck->execute([$payment_intent_id]);
                    
                    if ($stmtCheck->fetch()) {
                        // Déjà traité, on sort proprement pour ne pas créditer deux fois
                        $pdo->rollBack();
                        error_log("WEBHOOK INFO: Paiement $payment_intent_id déjà traité.");
                        http_response_code(200);
                        exit();
                    }

                    // 2. RÉCUPÉRATION DE L'ORGANISATEUR
                    // On utilise le premier token pour trouver le camp et l'organisateur associé
                    $firstToken = $tokens[0];
                    $stmtOrga = $pdo->prepare("
                        SELECT c.organisateur_id 
                        FROM inscriptions i 
                        JOIN camps c ON i.camp_id = c.id 
                        WHERE i.reservation_token = ?
                    ");
                    $stmtOrga->execute([$firstToken]);
                    $organisateur_id = $stmtOrga->fetchColumn();

                    // 3. MISE À JOUR DES INSCRIPTIONS
                    // On valide l'inscription, on passe le statut à Confirmé/PAYE
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

                    // 4. CRÉDITER LE PORTEFEUILLE ORGANISATEUR (En Euros)
                    if ($organisateur_id) {
                        // On déduit la commission plateforme ici si nécessaire (ex: 1%)
                        // Pour l'instant, on crédite tout le montant :
                        $sqlWallet = "UPDATE organisateurs SET portefeuille = portefeuille + ? WHERE id = ?";
                        $stmtWallet = $pdo->prepare($sqlWallet);
                        $stmtWallet->execute([$montant_total_eur, $organisateur_id]);
                    }

                    $pdo->commit();
                    error_log("WEBHOOK SUCCESS: Inscription validée pour les tokens " . $metadata_tokens);

                } catch (Exception $e) {
                    $pdo->rollBack();
                    error_log("WEBHOOK ERROR (Inscription): " . $e->getMessage());
                    http_response_code(500);
                    exit();
                }
            }
        } 
        
        // --------------------------------------------------------------------
        // CAS INCONNU
        // --------------------------------------------------------------------
        else {
            error_log("WEBHOOK WARNING: Type d'événement checkout inconnu ou métadonnées manquantes.");
        }
    }
}

// Réponse 200 OK à Stripe pour confirmer la bonne réception
http_response_code(200);
?>