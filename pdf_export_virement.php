<?php
// pdf_export_virement.php

require_once 'api/config.php';
require_once 'libs/fpdf/fpdf.php'; // Assurez-vous que le chemin est correct

// Sécurité : Vérifie que l'utilisateur est connecté et est un directeur
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_directeur'])) {
    http_response_code(403);
    exit;
}

$token = filter_input(INPUT_GET, 't', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
$userId = $_SESSION['user']['id'];
$virement = null;

if (!$token || strlen($token) !== 60) {
    die("Token de virement invalide ou manquant.");
}

try {
    // 1. Récupérer les détails du virement et vérifier l'appartenance à l'utilisateur
    // On récupère également les informations administratives de l'organisme pour les afficher
    $sql = "
        SELECT v.*, o.nom as organisateur_nom, o.adresse_complete, o.code_postal_orga, o.ville_orga
        FROM virements v
        JOIN organisateurs o ON v.organisateur_id = o.id
        WHERE v.token = ? AND v.user_id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$token, $userId]);
    $virement = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$virement) {
        die("Demande de virement introuvable ou accès non autorisé.");
    }

} catch (Exception $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// --- Logique PDF FPDF ---

class PDF extends FPDF
{
    protected $virementData;

    function setVirementData($data)
    {
        $this->virementData = $data;
    }

    // En-tête du document
    function Header()
    {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 7, utf8_decode('Demande de Virement - Réf. ' . substr($this->virementData['token'], 0, 10) . '...'), 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, utf8_decode('Organisme : ' . $this->virementData['organisateur_nom']), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode('Date de la demande : ' . date('d/m/Y H:i', strtotime($this->virementData['date_demande']))), 0, 1, 'C');
        $this->Ln(10);
    }

    // Pied de page du document
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Document non contractuel. Page ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Fonction pour dessiner une boîte de section
    function SectionBox($title)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(220, 220, 220);
        $this->Cell(0, 8, utf8_decode($title), 0, 1, 'L', true);
        $this->SetLineWidth(0.2);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(2);
    }

    // Contenu principal du PDF
    function Content()
    {
        $v = $this->virementData;
        $this->SetMargins(15, 15);
        
        // --- 1. Bloc Adresses & Contact ---
        $this->SectionBox("Informations de l'Organisme");
        $this->SetFont('Arial', '', 10);
        
        $this->Cell(90, 5, utf8_decode("Nom : " . $v['organisateur_nom']), 0, 0, 'L');
        $this->Cell(0, 5, utf8_decode("Demandeur : " . $v['prenom_user'] . ' ' . $v['nom_user']), 0, 1, 'L');
        
        $this->Cell(90, 5, utf8_decode("Email : " . $v['email_organisme']), 0, 0, 'L');
        $this->Cell(0, 5, utf8_decode("Téléphone : " . $v['tel_organisme']), 0, 1, 'L');
        
        $adresse = $v['adresse_complete'] . ', ' . $v['code_postal_orga'] . ' ' . $v['ville_orga'];
        $this->Cell(0, 5, utf8_decode("Adresse Administrative : " . $adresse), 0, 1, 'L');
        $this->Ln(5);


        // --- 2. Bloc Montants ---
        $this->SectionBox("Détails du Montant");
        
        // Montant Total Brut
        $this->SetFillColor(240, 240, 255);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(80, 8, utf8_decode('Montant BRUT de la demande :'), 1, 0, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, number_format($v['montant_total'], 2, ',', ' ') . ' €', 1, 1, 'R', true);

        // Commission
        $commission = $v['montant_total'] - $v['montant_apres_commission'];
        $this->SetFont('Arial', '', 10);
        $this->Cell(80, 6, utf8_decode('Commission ColoMap (' . number_format($v['commission_rate'], 2) . '%) :'), 1, 0, 'L');
        $this->SetFont('Arial', 'I', 10);
        $this->Cell(0, 6, '- ' . number_format($commission, 2, ',', ' ') . ' €', 1, 1, 'R');
        
        // Montant Net Final
        $this->SetFillColor(200, 255, 200);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(80, 8, utf8_decode('Montant NET à virer :'), 1, 0, 'L', true);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, number_format($v['montant_apres_commission'], 2, ',', ' ') . ' €', 1, 1, 'R', true);
        $this->Ln(5);

        
        // --- 3. Bloc Coordonnées Bancaires ---
        $this->SectionBox(utf8_decode("Coordonnées Bancaires de Réception"));
        $this->SetFont('Arial', '', 10);
        
        $this->Cell(80, 7, 'IBAN :', 1, 0, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 7, $v['iban'], 1, 1, 'L');
        
        $this->SetFont('Arial', '', 10);
        $this->Cell(80, 7, utf8_decode('BIC / SWIFT :'), 1, 0, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 7, $v['bic_swift'], 1, 1, 'L');
        $this->Ln(5);


        // --- 4. Bloc Statut ---
        $this->SectionBox("Statut et Traitement");
        
        $this->SetFont('Arial', '', 10);
        $this->Cell(80, 7, utf8_decode('Statut de la demande :'), 1, 0, 'L');
        
        $statut = $v['effectue'] == 1 ? 'Effectué' : 'En attente de traitement';
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 7, utf8_decode($statut), 1, 1, 'L');
        
        // Affichage des dates
        if ($v['effectue'] == 1) {
            $date_vir = date('d/m/Y', strtotime($v['date_virement_effectue']));
            $this->Cell(80, 7, utf8_decode('Date du virement effectif :'), 1, 0, 'L');
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(0, 7, $date_vir, 1, 1, 'L');
        } else {
            $date_estimee = date('d/m/Y', strtotime($v['date_virement_estime']));
            $this->Cell(80, 7, utf8_decode('Date de virement estimée (J+3 ouvrés) :'), 1, 0, 'L');
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(0, 7, $date_estimee, 1, 1, 'L');
        }
        
        $this->Ln(5);
        $this->SetFont('Arial', 'I', 8);
        $this->MultiCell(0, 4, utf8_decode("Token de référence unique : " . $v['token']), 0, 'L');

    }
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->setVirementData($virement);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->Content();

// Sortie
$filename = 'Demande_Virement_' . $virement['organisateur_nom'] . '_' . date('Ymd', strtotime($virement['date_demande'])) . '.pdf';
$pdf->Output('I', utf8_decode($filename));
exit;
?>