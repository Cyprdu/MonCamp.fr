<?php
// api/save_identity.php

require_once 'config.php';

use Stripe\Stripe;
use Stripe\Account;
use Stripe\File;
use Stripe\Token;
use Stripe\Exception\ApiErrorException;

if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_directeur']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$organisateurId = intval($_POST['organisateur_id']);
$tokenAccount = $_POST['stripe_account_token'];
$userId = $_SESSION['user']['id'];

try {
    // 1. Check Organisateur
    $stmt = $pdo->prepare("SELECT * FROM organisateurs WHERE id = ? AND user_id = ?");
    $stmt->execute([$organisateurId, $userId]);
    $orga = $stmt->fetch();
    if (!$orga) throw new Exception("Organisme invalide.");

    // 2. Upload Document vers Stripe
    if (!isset($_FILES['identity_document']) || $_FILES['identity_document']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Le document est requis.");
    }

    $file = File::create([
        'purpose' => 'identity_document',
        'file' => fopen($_FILES['identity_document']['tmp_name'], 'r'),
    ]);

    // 3. Création Compte Stripe (Custom)
    // Etape A: Création avec les infos de base (Token identité)
    $accountParams = [
        'type' => 'custom',
        'country' => 'FR',
        'account_token' => $tokenAccount, // Contient Nom, Adresse, DOB, TOS
        'business_profile' => ['mcc' => '8398', 'url' => 'https://colomap.fr'],
        'capabilities' => ['transfers' => ['requested' => true]],
        'settings' => ['payouts' => ['schedule' => ['interval' => 'manual']]]
    ];

    $account = Account::create($accountParams);
    $stripeAccountId = $account->id;

    // 4. Ajout du Document (Etape B)
    // On doit créer un Token pour le fichier pour mettre à jour le compte existant
    $tokenDoc = Token::create([
        'account' => [
            'individual' => [
                'verification' => [
                    'document' => ['front' => $file->id]
                ]
            ]
        ]
    ]);

    // Mise à jour du compte avec le document
    Account::update($stripeAccountId, ['account_token' => $tokenDoc->id]);

    // 5. Sauvegarde en Base
    $up = $pdo->prepare("UPDATE organisateurs SET stripe_account_id = ? WHERE id = ?");
    $up->execute([$stripeAccountId, $organisateurId]);

    // Succès
    header("Location: ../dashboard_organisme.php?organisateur_id=$organisateurId&success=Identité vérifiée avec succès. Vous pouvez maintenant effectuer des virements.");

} catch (Exception $e) {
    // En cas d'erreur, on revient au dashboard avec le message
    $msg = urlencode($e->getMessage());
    header("Location: ../dashboard_organisme.php?organisateur_id=$organisateurId&error=$msg");
}