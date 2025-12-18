<?php
// Fichier : api/create_stripe_session_multi.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

if (!defined('STRIPE_SECRET_KEY')) die("Erreur Config Stripe.");
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) die("Non connecté.");

$baseUrl = defined('DOMAIN') ? rtrim(DOMAIN, '/') : "http://" . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF']));

// Données entrantes
$camp_token = $_POST['camp_t'] ?? '';
$child_ids = $_POST['child_ids'] ?? [];
$tarifs_selected = $_POST['tarifs'] ?? []; // Array [child_id => tarif_id]
$custom_prices = $_POST['custom_prices'] ?? []; // Array [child_id => price]

if (empty($camp_token) || empty($child_ids)) die("Données manquantes.");

try {
    // 1. Récupération Camp
    $stmt = $pdo->prepare("SELECT * FROM camps WHERE token = ?");
    $stmt->execute([$camp_token]);
    $camp = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$camp) die("Camp introuvable.");

    // Récupération Remise Fratrie (%)
    $remise_percent = floatval($camp['remise_fratrie'] ?? 0);

    $line_items = [];
    $reservation_tokens = [];

    $count = 0;
    foreach ($child_ids as $child_id) {
        
        // Sécurité Enfant
        $stmtChild = $pdo->prepare("SELECT prenom, nom FROM enfants WHERE id = ? AND parent_id = ?");
        $stmtChild->execute([$child_id, $_SESSION['user']['id']]);
        $child = $stmtChild->fetch(PDO::FETCH_ASSOC);
        if (!$child) continue;

        // --- DÉTERMINATION DU PRIX DE BASE (Selon Tarif Spécifique de l'enfant) ---
        $tarif_id = $tarifs_selected[$child_id] ?? 'default';
        $prix_base = 0;
        $nom_tarif = "Standard";

        if ($tarif_id === 'default') {
            $prix_base = floatval($camp['prix']);
        } else {
            // Verif Tarif
            $stmtTarif = $pdo->prepare("SELECT t.* FROM tarifs t JOIN camps_tarifs ct ON t.id = ct.tarif_id WHERE t.id = ? AND ct.camp_id = ?");
            $stmtTarif->execute([$tarif_id, $camp['id']]);
            $tarifData = $stmtTarif->fetch(PDO::FETCH_ASSOC);

            if (!$tarifData) {
                // Fallback si tarif hacké/invalide
                $prix_base = floatval($camp['prix']); 
            } else {
                $nom_tarif = $tarifData['nom'];
                
                // Gestion Montant Libre
                if ($tarifData['montant_libre'] == 1) {
                    $val_custom = floatval($custom_prices[$child_id] ?? 0);
                    $prix_base = ($val_custom > 0) ? $val_custom : floatval($tarifData['prix']);
                } else {
                    $prix_base = floatval($tarifData['prix']);
                }
            }
        }

        // --- CALCUL PRIX FINAL (Remise Fratrie) ---
        $prix_final = $prix_base;
        $desc = "Séjour : " . $camp['nom'] . " ($nom_tarif)";

        if ($count > 0 && $remise_percent > 0) {
            $montant_remise = $prix_base * ($remise_percent / 100);
            $prix_final = $prix_base - $montant_remise;
            $desc .= " + Remise Fratrie -{$remise_percent}%";
        }

        $prix_final = round($prix_final, 2);
        if ($prix_final < 0) $prix_final = 0;

        // --- INSERTION BDD ---
        $token_inscription = bin2hex(random_bytes(16));
        $reservation_tokens[] = $token_inscription;
        
        $tarif_id_sql = ($tarif_id !== 'default') ? $tarif_id : null;

        $sql = "INSERT INTO inscriptions (camp_id, enfant_id, tarif_id, statut, prix_final, statut_paiement, reservation_token) 
                VALUES (?, ?, ?, 'En attente', ?, 'EN_ATTENTE', ?)";
        $pdo->prepare($sql)->execute([$camp['id'], $child_id, $tarif_id_sql, $prix_final, $token_inscription]);

        // --- ITEM STRIPE ---
        $line_items[] = [
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => "Inscription : " . $child['prenom'] . " " . $child['nom'],
                    'description' => $desc,
                ],
                'unit_amount' => intval($prix_final * 100),
            ],
            'quantity' => 1,
        ];

        $count++;
    }

    if (empty($line_items)) die("Erreur : Aucune inscription valide.");

    $tokens_string = implode(',', $reservation_tokens);

    // Session Stripe
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
        'success_url' => $baseUrl . '/inscription_confirmation.php?success=1&tokens=' . $tokens_string . '&camp_id=' . $camp['id'],
        'cancel_url' => $baseUrl . '/inscription_confirmation.php?cancel=1&tokens=' . $tokens_string . '&camp_id=' . $camp['id'],
        'metadata' => [
            'reservation_token' => $tokens_string,
            'camp_id' => $camp['id'],
            'user_id' => $_SESSION['user']['id'],
            'type' => 'multi_inscription'
        ],
    ]);

    header("Location: " . $checkout_session->url);
    exit;

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}
?>