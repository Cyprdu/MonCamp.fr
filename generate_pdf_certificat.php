<?php
// Fichier : generate_pdf_certificat.php

// 1. Initialisation et Sécurité
require('libs/fpdf/fpdf.php'); 
require_once 'api/config.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    // Redirection vers le login si pas connecté, ou arrêt du script
    die("Erreur : Vous devez être connecté pour télécharger ce certificat.");
}

// Récupération de l'ID utilisateur correct (C'est ici que ça bloquait avant)
$user_id = $_SESSION['user']['id'];

// Vérification de l'ID inscription
if (!isset($_GET['inscription_id']) || empty($_GET['inscription_id'])) {
    die("Erreur : ID d'inscription manquant.");
}
$inscription_id = $_GET['inscription_id'];

// 2. Récupération des données réelles en BDD
try {
    // On joint les tables pour avoir toutes les infos en une requête
    // Le "AND e.parent_id = ?" assure la sécurité : on ne trouve rien si ce n'est pas le bon parent
    $sql = "
        SELECT 
            i.date_inscription, i.prix_final, i.montant_paye, i.statut_paiement,
            c.nom AS camp_nom, c.date_debut, c.date_fin, c.ville, c.adresse, c.code_postal,
            e.nom AS enfant_nom, e.prenom AS enfant_prenom, e.date_naissance,
            o.nom AS organisateur_nom, o.email AS organisateur_email, o.tel AS organisateur_tel
        FROM inscriptions i
        JOIN camps c ON i.camp_id = c.id
        JOIN enfants e ON i.enfant_id = e.id
        JOIN organisateurs o ON c.organisateur_id = o.id
        WHERE i.id = ? AND e.parent_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$inscription_id, $user_id]);
    $details = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$details) {
        die("Accès refusé : Inscription introuvable ou vous n'êtes pas le parent responsable.");
    }

} catch (Exception $e) {
    die("Erreur technique : " . $e->getMessage());
}

// 3. Génération du PDF
class PDF extends FPDF {
    function Header() {
        // En-tête simple si vous n'avez pas de logo spécifique défini
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, utf8_decode('ColoMap - Certificat d\'inscription'), 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Page ' . $this->PageNo()), 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetTitle(utf8_decode("Certificat - " . $details['enfant_prenom']));

// Titre principal
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, utf8_decode('CERTIFICAT D\'INSCRIPTION'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 5, utf8_decode('Organisé par : ' . $details['organisateur_nom']), 0, 1, 'C');
$pdf->Ln(15);

// Bloc Détails du Camp
$pdf->SetFillColor(230, 240, 255); // Bleu clair
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, utf8_decode('  Détails du Séjour'), 0, 1, 'L', true);
$pdf->Ln(2);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 8, 'Camp :', 0);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode($details['camp_nom']), 0, 1);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 8, utf8_decode('Dates :'), 0);
$pdf->Cell(0, 8, 'Du ' . date('d/m/Y', strtotime($details['date_debut'])) . ' au ' . date('d/m/Y', strtotime($details['date_fin'])), 0, 1);

$pdf->Cell(50, 8, 'Lieu :', 0);
$pdf->MultiCell(0, 8, utf8_decode($details['adresse'] . ', ' . $details['code_postal'] . ' ' . $details['ville']), 0, 1);
$pdf->Ln(5);

// Bloc Détails de l'Enfant
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, utf8_decode('  Participant'), 0, 1, 'L', true);
$pdf->Ln(2);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 8, utf8_decode('Nom Prénom :'), 0);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode($details['enfant_nom'] . ' ' . $details['enfant_prenom']), 0, 1);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 8, utf8_decode('Date de naissance :'), 0);
$pdf->Cell(0, 8, date('d/m/Y', strtotime($details['date_naissance'])), 0, 1);
$pdf->Ln(5);

// Bloc Paiement
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, utf8_decode('  Statut Financier'), 0, 1, 'L', true);
$pdf->Ln(2);

$pdf->SetFont('Arial', '', 12);
$statut_texte = ($details['statut_paiement'] === 'PAYE' || $details['montant_paye'] >= $details['prix_final']) ? 'PAYÉ' : 'EN ATTENTE / PARTIEL';

$pdf->Cell(50, 8, utf8_decode('Montant total :'), 0);
$pdf->Cell(0, 8, number_format($details['prix_final'], 2, ',', ' ') . ' EUR', 0, 1);

$pdf->Cell(50, 8, utf8_decode('Montant réglé :'), 0);
$pdf->Cell(0, 8, number_format($details['montant_paye'], 2, ',', ' ') . ' EUR', 0, 1);

$pdf->Cell(50, 8, utf8_decode('Statut :'), 0);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode($statut_texte), 0, 1);
$pdf->Ln(15);

// Mention de bas de page
$pdf->SetFont('Arial', 'I', 10);
$pdf->SetTextColor(100, 100, 100);
$texte_legal = "Ce document certifie l'inscription de l'enfant susmentionné. Il a été généré automatiquement le " . date('d/m/Y') . " sur la plateforme ColoMap.";
if (!empty($details['organisateur_email'])) {
    $texte_legal .= "\nPour toute question, contactez l'organisateur : " . $details['organisateur_email'];
}
$pdf->MultiCell(0, 5, utf8_decode($texte_legal), 0, 'C');

// Sortie du fichier
$nom_fichier = "Certificat_" . str_replace(' ', '_', $details['enfant_nom']) . "_" . date('Ymd') . ".pdf";
$pdf->Output('I', $nom_fichier);
?>