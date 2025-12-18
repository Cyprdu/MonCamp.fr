<?php
// api/request_virement.php

require_once 'config.php';

use Stripe\Stripe;
use Stripe\Account;
use Stripe\Transfer;
use Stripe\Payout;
use Stripe\Exception\ApiErrorException;

if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_directeur']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit;
}

$organisateurId = intval($_POST['organisateur_id']);
$montantDemande = floatval($_POST['montant_total_demande']);
$tokenBank = $_POST['stripe_bank_token'];
$userId = $_SESSION['user']['id'];

try {
    // 1. Récupération
    $stmt = $pdo->prepare("SELECT * FROM organisateurs WHERE id = ? AND user_id = ?");
    $stmt->execute([$organisateurId, $userId]);
    $orga = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orga || empty($orga['stripe_account_id'])) {
        throw new Exception("Compte introuvable ou non vérifié.");
    }
    if ($montantDemande > floatval($orga['portefeuille'])) {
        throw new Exception("Solde insuffisant.");
    }

    $stripeAccountId = $orga['stripe_account_id'];

    // 2. Attacher le RIB (pour ce virement)
    $account = Account::retrieve($stripeAccountId);
    $account->external_accounts->create([
        'external_account' => $tokenBank,
        'default_for_currency' => true,
    ]);

    // 3. Calculs
    $commission = round($montantDemande * 0.01, 2);
    $net = $montantDemande - $commission;
    $cents = intval(round($net * 100));

    // 4. Transaction SQL + Stripe
    $pdo->beginTransaction();

    // Transfert Plateforme -> Connect
    Transfer::create([
        'amount' => $cents,
        'currency' => 'eur',
        'destination' => $stripeAccountId,
        'description' => "Virement ColoMap: " . $orga['nom'],
    ]);

    // Payout Connect -> Banque
    Payout::create([
        'amount' => $cents,
        'currency' => 'eur',
    ], ['stripe_account' => $stripeAccountId]);

    // 5. Enregistrement DB
    $token = bin2hex(random_bytes(30));
    $newSolde = floatval($orga['portefeuille']) - $montantDemande;

    $sql = "INSERT INTO virements (token, organisateur_id, user_id, montant_total, commission_rate, montant_apres_commission, nom_organisme, iban, bic_swift, effectue, date_virement_effectue) VALUES (?, ?, ?, ?, 1.00, ?, ?, 'STRIPE', 'STRIPE', 1, NOW())";
    $pdo->prepare($sql)->execute([$token, $organisateurId, $userId, $montantDemande, $net, $orga['nom']]);
    
    $pdo->prepare("UPDATE organisateurs SET portefeuille = ? WHERE id = ?")->execute([$newSolde, $organisateurId]);

    $pdo->commit();

    // REDIRECTION DIRECTE VERS LE REÇU
    header("Location: ../virement.php?t=" . $token);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // Retour erreur formulaire
    header("Location: ../demande_de_virement_info.php?organisateur_id=$organisateurId&error=" . urlencode($e->getMessage()));
    exit;
}