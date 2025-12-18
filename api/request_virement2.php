<?php
// api/request_virement.php

// 1. CONFIG & SÉCURITÉ
require_once 'config.php';

use Stripe\Stripe;
use Stripe\Account;
use Stripe\Transfer;
use Stripe\Payout;
use Stripe\Exception\ApiErrorException;

// Fonction de redirection vers la page de DEBUG
function redirectDebug($status, $message, $organisateurId, $token = '') {
    $url = "../virement_result_debug.php?status=" . urlencode($status) . 
           "&message=" . urlencode($message) . 
           "&organisateur_id=" . $organisateurId .
           "&token=" . $token;
    header('Location: ' . $url);
    exit;
}

// SÉCURITÉ : Accès directeurs uniquement via POST
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_directeur']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    header('Location: ../index.php');
    exit;
}

$organisateurId = filter_input(INPUT_POST, 'organisateur_id', FILTER_VALIDATE_INT);
$userId = $_SESSION['user']['id'];
$montantDemande = floatval($_POST['montant_total_demande'] ?? 0);

// Les tokens générés par le JS
$tokenAccount = $_POST['stripe_account_token'] ?? '';
$tokenBank = $_POST['stripe_bank_token'] ?? '';

// Validation basique
if (!$organisateurId || empty($tokenAccount) || empty($tokenBank)) {
    redirectDebug('error', "Données de sécurité manquantes (Tokens JS non reçus).", $organisateurId);
}

try {
    // A. Récupérer les données de l'organisateur
    $stmtOrga = $pdo->prepare("SELECT * FROM organisateurs WHERE id = ? AND user_id = ?");
    $stmtOrga->execute([$organisateurId, $userId]);
    $organisateur = $stmtOrga->fetch(PDO::FETCH_ASSOC);

    if (!$organisateur) {
        throw new Exception("Organisme introuvable ou droits insuffisants.");
    }

    // Vérifier le solde
    if ($montantDemande > floatval($organisateur['portefeuille'])) {
        throw new Exception("Le montant demandé dépasse le solde disponible.");
    }

    // --- DÉBUT LOGIQUE STRIPE ---

    $stripeAccountId = $organisateur['stripe_account_id'];
    
    // Configuration standard
    // CORRECTION : On ne met PAS 'tos_acceptance' ici car le tokenAccount le contient déjà.
    $accountData = [
        'business_profile' => [
            'mcc' => '8398', // Associations / Organisations caritatives
            'url' => $organisateur['web'] ?: 'https://colomap.fr',
        ],
        'capabilities' => [
            'transfers' => ['requested' => true], // On demande le droit de recevoir de l'argent
        ],
    ];

    try {
        if (empty($stripeAccountId)) {
            // CRÉATION (Nouveau compte)
            $createData = array_merge([
                'type' => 'custom',
                'country' => 'FR',
                'account_token' => $tokenAccount, // Le token contient l'identité ET l'acceptation des TOS
                'settings' => [
                    'payouts' => ['schedule' => ['interval' => 'manual']],
                ],
            ], $accountData);

            $account = Account::create($createData);
            $stripeAccountId = $account->id;

            // Sauvegarder l'ID
            $stmtUp = $pdo->prepare("UPDATE organisateurs SET stripe_account_id = ? WHERE id = ?");
            $stmtUp->execute([$stripeAccountId, $organisateurId]);

        } else {
            // MISE À JOUR (Compte existant)
            $updateData = array_merge([
                'account_token' => $tokenAccount, // Met à jour l'identité et les TOS via le token
            ], $accountData);
            
            Account::update($stripeAccountId, $updateData);
        }

        // 2. ATTACHER LE RIB
        $account = Account::retrieve($stripeAccountId);
        $account->external_accounts->create([
            'external_account' => $tokenBank,
            'default_for_currency' => true,
        ]);

    } catch (ApiErrorException $e) {
        // Gestion fine des erreurs
        $msg = $e->getMessage();
        
        // Si le compte est irrémédiablement buggé (ex: capabilities bloquées), on le supprime pour le recréer au prochain essai
        if (strpos($msg, 'capabilities') !== false || strpos($msg, 'transfers') !== false) {
            $pdo->prepare("UPDATE organisateurs SET stripe_account_id = NULL WHERE id = ?")->execute([$organisateurId]);
            $msg = "Le compte technique existant était incomplet. Nous l'avons réinitialisé. Veuillez cliquer sur RETOUR et réessayer, cela fonctionnera.";
        }
        
        throw new Exception("Erreur Configuration Stripe : " . $msg);
    }

    // 3. EXÉCUTION DU VIREMENT
    $COMISSION_RATE = 1.00;
    $commission = round($montantDemande * ($COMISSION_RATE / 100), 2);
    $montantNet = $montantDemande - $commission;
    $amountCentimes = intval(round($montantNet * 100));

    $pdo->beginTransaction();

    try {
        // A. Transfert : Plateforme -> Compte Connecté
        Transfer::create([
            'amount' => $amountCentimes,
            'currency' => 'eur',
            'destination' => $stripeAccountId,
            'description' => "Virement ColoMap pour " . $organisateur['nom'],
        ]);

        // B. Payout : Compte Connecté -> Banque
        Payout::create([
            'amount' => $amountCentimes,
            'currency' => 'eur',
            'description' => "Virement " . $organisateur['nom'],
        ], [
            'stripe_account' => $stripeAccountId,
        ]);

    } catch (ApiErrorException $e) {
        $pdo->rollBack();
        throw new Exception("Erreur Transaction Financière : " . $e->getMessage());
    }

    // 4. SUCCÈS
    $token = bin2hex(random_bytes(30));
    $nouveauSolde = floatval($organisateur['portefeuille']) - $montantDemande;

    $sqlInsert = "INSERT INTO virements (
        token, organisateur_id, user_id, montant_total, commission_rate, montant_apres_commission, 
        nom_organisme, iban, bic_swift, effectue, date_virement_effectue
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'STRIPE', 1, NOW())";
    
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([
        $token, $organisateurId, $userId, $montantDemande, $COMISSION_RATE, $montantNet, 
        $organisateur['nom'], 'Stripe Token'
    ]);

    $pdo->prepare("UPDATE organisateurs SET portefeuille = ? WHERE id = ?")->execute([$nouveauSolde, $organisateurId]);

    $pdo->commit();
    
    redirectDebug('success', 'Virement effectué avec succès.', $organisateurId, $token);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirectDebug('error', $e->getMessage(), $organisateurId);
}
?>