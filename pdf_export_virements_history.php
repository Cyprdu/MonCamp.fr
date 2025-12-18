<?php
// pdf_export_virements_history.php

require_once 'api/config.php';
require_once 'libs/fpdf/fpdf.php'; // Assurez-vous que le chemin est correct

// Sécurité
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_directeur'])) {
    http_response_code(403);
    exit;
}

$organisateurId = filter_input(INPUT_GET, 'organisateur_id', FILTER_VALIDATE_INT);
$userId = $_SESSION['user']['id'];

if (!$organisateurId) {
    die("ID d'organisme manquant.");
}

try {
    // 1. Récupérer le nom de l'organisme
    $stmtOrga = $pdo->prepare("SELECT nom FROM organisateurs WHERE id = ? AND user_id = ?");
    $stmtOrga->execute([$organisateurId, $userId]);
    $organisateur = $stmtOrga->fetch(PDO::FETCH_ASSOC);

    if (!$organisateur) {
        die("Organisme introuvable ou accès non autorisé.");
    }

    // 2. Récupérer l'historique des virements
    $stmtVirements = $pdo->prepare("
        SELECT token, montant_total, montant_apres_commission, date_demande, effectue, date_virement_effectue, date_virement_estime
        FROM virements
        WHERE organisateur_id = ?
        ORDER BY date_demande DESC
    ");
    $stmtVirements->execute([$organisateurId]);
    $virements = $stmtVirements->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Erreur de base de données.");
}

// --- Logique PDF FPDF ---

class PDF extends FPDF
{
    protected $organisateurNom;

    function setOrganisateurNom($nom)
    {
        $this->organisateurNom = $nom;
    }

    // En-tête
    function Header()
    {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 7, utf8_decode('Historique des Demandes de Virement'), 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, utf8_decode('Organisme : ' . $this->organisateurNom), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode('Généré le ' . date('d/m/Y H:i')), 0, 1, 'C');
        $this->Ln(10);
    }

    // Pied de page
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Page ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Tableau des virements
    function BasicTable($header, $data)
    {
        // En-têtes
        $w = array(30, 30, 30, 60, 40); // Largeurs des colonnes
        $this->SetFillColor(200, 220, 255);
        $this->SetFont('Arial', 'B', 10);
        
        // S'assurer que le tableau ne dépasse pas la page
        $this->CheckPageBreak(10);
        
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 7, utf8_decode($header[$i]), 1, 0, 'C', true);
        }
        $this->Ln();

        // Données
        $this->SetFont('Arial', '', 9);
        $fill = false;
        foreach ($data as $row) {
            $this->CheckPageBreak(7); // Vérifie si un saut de page est nécessaire

            // Ligne 1: Demande
            $this->Cell($w[0], 7, date('d/m/Y', strtotime($row['date_demande'])), 'LR', 0, 'C', $fill);
            
            // Ligne 2: Montant Net
            $this->Cell($w[1], 7, number_format($row['montant_apres_commission'], 2, ',', ' ') . ' €', 'LR', 0, 'R', $fill);
            
            // Ligne 3: Montant Brut
            $this->Cell($w[2], 7, number_format($row['montant_total'], 2, ',', ' ') . ' €', 'LR', 0, 'R', $fill);
            
            // Ligne 4: Statut et Dates
            $statut = $row['effectue'] == 1 ? 'Effectué' : 'En attente';
            if ($row['effectue'] == 1) {
                $date_info = 'Viré le ' . date('d/m/Y', strtotime($row['date_virement_effectue']));
            } else {
                $date_info = 'Est. le ' . date('d/m/Y', strtotime($row['date_virement_estime']));
            }
            $this->Cell($w[3], 7, utf8_decode($statut . ' / ' . $date_info), 'LR', 0, 'L', $fill);
            
            // Ligne 5: Token (simplifié)
            $token_short = substr($row['token'], 0, 15) . '...';
            $this->SetFont('Arial', 'I', 8);
            $this->Cell($w[4], 7, $token_short, 'LR', 0, 'C', $fill);
            $this->SetFont('Arial', '', 9);
            
            $this->Ln();
            $fill = !$fill;
        }
        // Ligne de fermeture
        $this->Cell(array_sum($w), 0, '', 'T');
    }
    
    // Fonction utilitaire pour vérifier le saut de page
    function CheckPageBreak($h)
    {
        // Si la hauteur restante est inférieure à $h, on ajoute une page
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
        }
    }
}

$pdf = new PDF();
$pdf->setOrganisateurNom($organisateur['nom']);
$pdf->AliasNbPages();
$pdf->AddPage();

if (empty($virements)) {
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->Cell(0, 10, utf8_decode("Aucune demande de virement n'a été trouvée pour cet organisme."), 0, 1, 'C');
} else {
    $header = array('Date Demande', 'Montant Net', 'Montant Brut', utf8_decode('Statut et Date'), 'Token (Début)');
    $pdf->BasicTable($header, $virements);
}


// Sortie
$pdf->Output('I', 'Historique_Virements_' . $organisateur['nom'] . '.pdf');
exit;
?>