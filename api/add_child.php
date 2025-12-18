<?php
// Fichier: api/add_child.php
require_once 'config.php';

// Verification Librairie FPDF
$fpdfPath = '../libs/fpdf/fpdf.php';
if (file_exists($fpdfPath)) {
    require_once $fpdfPath;
} else {
    if (isset($_POST['mode_fiche_sanitaire']) && $_POST['mode_fiche_sanitaire'] === 'create') {
        die("Erreur Fatale : La librairie FPDF est absente dans /libs/fpdf/.");
    }
}

if (!isset($_SESSION['user']['id'])) { sendJson(['error' => 'Non connecté'], 403); }

// --- Helpers ---
function cleanInput($key) { return !empty($_POST[$key]) ? trim($_POST[$key]) : null; }
function generateToken($ext) { return bin2hex(random_bytes(32)) . '.' . $ext; }

try {
    // 1. CARNET DE SANTÉ (Obligatoire)
    $carnetToken = null;
    if (isset($_FILES['carnet_sante']) && $_FILES['carnet_sante']['error'] === 0) {
        $ext = pathinfo($_FILES['carnet_sante']['name'], PATHINFO_EXTENSION);
        $carnetToken = generateToken($ext);
        move_uploaded_file($_FILES['carnet_sante']['tmp_name'], __DIR__ . '/../uploads/sante/' . $carnetToken);
    } else {
        throw new Exception("Le carnet de santé est obligatoire.");
    }

    // 2. FICHE SANITAIRE
    $ficheSanitaireToken = null;
    $sigToken = null;
    $modeFiche = $_POST['mode_fiche_sanitaire'] ?? 'upload';

    if ($modeFiche === 'upload') {
        if (isset($_FILES['file_fiche_sanitaire']) && $_FILES['file_fiche_sanitaire']['error'] === 0) {
            $extF = pathinfo($_FILES['file_fiche_sanitaire']['name'], PATHINFO_EXTENSION);
            $ficheSanitaireToken = generateToken($extF);
            move_uploaded_file($_FILES['file_fiche_sanitaire']['tmp_name'], __DIR__ . '/../uploads/sanitaire/' . $ficheSanitaireToken);
        }
    } else {
        if (!class_exists('FPDF')) throw new Exception("Erreur interne PDF");

        // --- TRAITEMENT SIGNATURE ---
        if (!empty($_POST['signature_data'])) {
            $data = $_POST['signature_data'];
            $data = str_replace('data:image/png;base64,', '', $data);
            $data = str_replace(' ', '+', $data);
            $imgData = base64_decode($data);
            
            $sigToken = generateToken('png');
            $dirSig = __DIR__ . '/../uploads/signatures/';
            if (!is_dir($dirSig)) mkdir($dirSig, 0755, true);
            file_put_contents($dirSig . $sigToken, $imgData);
        }

        // --- GÉNÉRATION PDF MODELE OFFICIEL ---
        $pdf = new FPDF('P','mm','A4');
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(false); 
        $pdf->SetFont('Arial', '', 8);

        // --- EN-TÊTE ---
        // Logo (Ministère)
        $logoPath = __DIR__ . '/../img/ministaire.jpg'; // Orthographe selon votre demande
        if(file_exists($logoPath)) {
            $pdf->Image($logoPath, 10, 10, 25); 
        }

        // Bloc Titre (Gris)
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetXY(45, 10);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(120, 10, utf8_decode("FICHE SANITAIRE DE LIAISON"), 1, 1, 'C', true);
        
        $pdf->SetXY(45, 20);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(120, 6, utf8_decode("DOCUMENT CONFIDENTIEL"), 0, 1, 'C');
        
        $pdf->SetXY(45, 26);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(120, 6, utf8_decode("Joindre obligatoirement la copie du carnet de vaccination"), 1, 1, 'C', true);

        // --- BLOC IDENTITÉ (Largeur Page) ---
        $pdf->SetXY(10, 40);
        $pdf->SetFillColor(220, 220, 220);
        $pdf->Rect(10, 40, 190, 25, 'F'); // Grand rectangle gris
        
        $pdf->SetXY(12, 42); $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(180, 6, utf8_decode("NOM DU MINEUR : " . strtoupper(cleanInput('nom'))), 'B', 1);
        
        $pdf->SetX(12);
        $pdf->Cell(180, 6, utf8_decode("PRENOM : " . cleanInput('prenom')), 'B', 1);
        
        $pdf->SetX(12);
        $pdf->Cell(80, 6, utf8_decode("DATE DE NAISSANCE : " . date('d/m/Y', strtotime(cleanInput('date_naissance')))), 0, 0);
        
        $sexe = cleanInput('sexe');
        $chkM = ($sexe == 'Homme' || $sexe == 'M') ? 'X' : ' ';
        $chkF = ($sexe == 'Femme' || $sexe == 'F') ? 'X' : ' ';
        $pdf->Cell(80, 6, utf8_decode("SEXE :      M  [ $chkM ]         F  [ $chkF ]"), 0, 1);

        // Texte Intro
        $pdf->SetXY(10, 68);
        $pdf->SetFont('Arial', '', 8);
        $pdf->MultiCell(190, 3, utf8_decode("Cette fiche permet de recueillir des informations utiles concernant votre enfant (l'arrêté du 20 février 2003 relatif au suivi sanitaire des mineurs en séjour de vacances ou en accueil de loisirs)."), 0, 'J');

        // ============================================================
        // DÉBUT DES COLONNES
        // Colonne Gauche (X=10, W=90) | Colonne Droite (X=110, W=90)
        // ============================================================
        $yStart = 78;

        // --- COLONNE GAUCHE : VACCINATIONS & RENSEIGNEMENTS ---
        
        $pdf->SetXY(10, $yStart);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(90, 5, utf8_decode("1-VACCINATION (Voir carnet de santé)"), 0, 1);

        // Tableau Vaccins (Complexe pour matcher l'image)
        $pdf->SetFont('Arial', '', 6.5);
        $x = 10;
        $y = $pdf->GetY() + 1;
        $pdf->SetXY($x, $y);

        // Largeur colonnes
        $w1 = 20; $w2 = 5; $w3 = 5; $w4 = 18; $w5 = 20; $w6 = 22; // Total ~90

        // Headers
        $pdf->Cell($w1, 8, "OBLIGATOIRES", 1, 0, 'C');
        $pdf->Cell($w2, 8, "Oui", 1, 0, 'C');
        $pdf->Cell($w3, 8, "Non", 1, 0, 'C');
        $pdf->Cell($w4, 8, "DATES", 1, 0, 'C');
        $pdf->Cell($w5, 8, "RECOMMANDES", 1, 0, 'C');
        $pdf->Cell($w6, 8, "DATES", 1, 1, 'C');

        $dtp = cleanInput('vaccin_dtp');
        $rh = 5; // Row height

        // Fonction pour dessiner une ligne du tableau
        function drawVaccinRow($pdf, $w, $h, $nameObli, $dateObli, $nameRec, $dateRec) {
            $pdf->SetX(10);
            // Obligatoire
            $pdf->Cell($w[0], $h, utf8_decode($nameObli), 1);
            $hasDate = !empty($dateObli);
            $pdf->Cell($w[1], $h, ($hasDate?'X':''), 1, 0, 'C');
            $pdf->Cell($w[2], $h, (!$hasDate?'X':''), 1, 0, 'C');
            $pdf->Cell($w[3], $h, $dateObli, 1, 0, 'C');
            // Recommandé
            $pdf->Cell($w[4], $h, utf8_decode($nameRec), 1);
            $pdf->Cell($w[5], $h, utf8_decode($dateRec), 1, 1, 'C');
        }

        // Lignes
        drawVaccinRow($pdf, [$w1,$w2,$w3,$w4,$w5,$w6], $rh, "Diphtérie", $dtp, "Coqueluche", "");
        drawVaccinRow($pdf, [$w1,$w2,$w3,$w4,$w5,$w6], $rh, "Tétanos", $dtp, "Haemophilus", "");
        drawVaccinRow($pdf, [$w1,$w2,$w3,$w4,$w5,$w6], $rh, "Poliomyélite", $dtp, "Rubéole", "");
        drawVaccinRow($pdf, [$w1,$w2,$w3,$w4,$w5,$w6], $rh, "", "", "Rougeole", "");
        drawVaccinRow($pdf, [$w1,$w2,$w3,$w4,$w5,$w6], $rh, "", "", "Oreillons", "");
        drawVaccinRow($pdf, [$w1,$w2,$w3,$w4,$w5,$w6], $rh, "", "", "Hépatite B", "");
        
        $bcg = cleanInput('vaccin_bcg');
        drawVaccinRow($pdf, [$w1,$w2,$w3,$w4,$w5,$w6], $rh, "", "", "BCG", $bcg);
        
        $autre = cleanInput('vaccin_autre');
        drawVaccinRow($pdf, [$w1,$w2,$w3,$w4,$w5,$w6], $rh, "", "", "Autre", $autre);

        $pdf->Ln(2);
        $pdf->SetX(10);
        $pdf->MultiCell(90, 3, utf8_decode("SI LE MINEUR N'A PAS LES VACCINS OBLIGATOIRES JOINDRE UN CERTIFICAT MÉDICAL DE CONTRE-INDICATION."), 0, 'L');

        // 2 - Renseignements Mineur
        $pdf->Ln(5);
        $pdf->SetX(10);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(90, 5, utf8_decode("2-RENSEIGNEMENTS CONCERNANT LE MINEUR"), 0, 1);
        
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetX(10);
        $pdf->Cell(90, 6, utf8_decode("Poids : ".cleanInput('poids')." kg      Taille : ".cleanInput('taille')." cm"), 0, 1);
        
        $pdf->Ln(2);
        $pdf->SetX(10);
        $pdf->MultiCell(90, 4, utf8_decode("Suit-il un traitement médical pendant le séjour ?\n[ ] Oui     [ X ] Non\n\nSi oui, joindre une ordonnance récente et les médicaments correspondants dans leur emballage d'origine marqués au nom de l'enfant avec la notice."), 0, 'J');


        // --- COLONNE DROITE : ALLERGIES, RECOS, RESPONSABLES ---
        
        $xR = 110; // Début colonne droite
        
        // Allergies
        $pdf->SetXY($xR, $yStart);
        $pdf->SetFont('Arial', '', 8);
        
        $sante = cleanInput('infos_sante');
        $hasAl = !empty($sante) ? 'X' : ' '; 
        $noAl = empty($sante) ? 'X' : ' ';
        
        $pdf->Cell(50, 5, "ALLERGIES ALIMENTAIRES :", 0, 0);
        $pdf->Cell(30, 5, "[ $hasAl ] oui   [ $noAl ] non", 0, 1);
        
        $pdf->SetX($xR);
        $pdf->Cell(50, 5, "MEDICAMENTEUSES :", 0, 0);
        $pdf->Cell(30, 5, "[ $hasAl ] oui   [ $noAl ] non", 0, 1);
        
        $pdf->SetX($xR);
        $pdf->Cell(85, 5, utf8_decode("Précisez : " . $sante), 'B', 1);
        
        $pdf->Ln(2); $pdf->SetX($xR);
        $pdf->MultiCell(85, 3, utf8_decode("Si oui, joindre un certificat médical précisant la cause de l'allergie, les signes évocateurs et la conduite à tenir."));

        // Recommandations
        $pdf->Ln(5); $pdf->SetX($xR);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(85, 5, utf8_decode("3-RECOMMANDATIONS UTILES DES PARENTS"), 0, 1);
        
        $pdf->SetX($xR); $pdf->SetFont('Arial', '', 8);
        $regime = cleanInput('regime_alimentaire');
        $pdf->MultiCell(85, 3, utf8_decode("Port des lunettes, lentilles, appareil dentaire, énurésie, régime...\n> " . ($regime ? $regime : "Aucun régime spécifique")), 0, 'L');
        $pdf->SetX($xR); $pdf->Cell(85, 5, "...................................................................................", 'B', 1);
        $pdf->SetX($xR); $pdf->Cell(85, 5, "...................................................................................", 'B', 1);

        // Responsables
        $pdf->Ln(5); $pdf->SetX($xR);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(85, 5, utf8_decode("4-RESPONSABLES DU MINEUR"), 0, 1);
        
        $pdf->SetX($xR); $pdf->SetFont('Arial', '', 8);
        $r1 = cleanInput('resp1_nom') . " " . cleanInput('resp1_prenom');
        $pdf->Cell(85, 5, utf8_decode("Responsable N°1 : $r1"), 0, 1);
        $pdf->SetX($xR); $pdf->Cell(85, 5, utf8_decode("ADRESSE : " . cleanInput('adresse') . " " . cleanInput('ville')), 'B', 1);
        $pdf->SetX($xR); $pdf->Cell(85, 5, utf8_decode("TEL DOM : " . cleanInput('tel_fixe_enfant') . "   TEL PORT : " . cleanInput('resp1_tel')), 'B', 1);
        
        $pdf->Ln(2); $pdf->SetX($xR);
        $r2 = cleanInput('resp2_nom') . " " . cleanInput('resp2_prenom');
        $pdf->Cell(85, 5, utf8_decode("Responsable N°2 : " . ($r2 ? $r2 : "")), 0, 1);
        $pdf->SetX($xR); $pdf->Cell(85, 5, utf8_decode("TEL DOM : ........................   TEL PORT : " . cleanInput('resp2_tel')), 'B', 1);

        // Médecin
        $pdf->Ln(5); $pdf->SetX($xR);
        $pdf->Cell(85, 5, utf8_decode("NOM ET TEL MEDECIN TRAITANT :"), 0, 1);
        $pdf->SetX($xR);
        $pdf->Cell(85, 5, utf8_decode(cleanInput('medecin_nom') . " - " . cleanInput('medecin_tel')), 'B', 1);

        // Signature
        $pdf->Ln(8); $pdf->SetX($xR);
        $pdf->SetFont('Arial', '', 7);
        $pdf->MultiCell(85, 3, utf8_decode("Je soussigné(e) $r1, déclare exacts les renseignements portés sur cette fiche et m'engage à les réactualiser si nécessaire."), 0, 'J');
        
        $pdf->Ln(4); $pdf->SetX($xR);
        $pdf->Cell(40, 6, utf8_decode("Date : " . date('d/m/Y')), 0, 0);
        $pdf->Cell(40, 6, utf8_decode("Signature :"), 0, 1);
        
        if ($sigToken && file_exists($dirSig . $sigToken)) {
            $pdf->Image($dirSig . $sigToken, $xR + 25, $pdf->GetY() - 5, 35, 15);
        }

        // Sauvegarde
        $ficheSanitaireToken = generateToken('pdf');
        $dirSanitaire = __DIR__ . '/../uploads/sanitaire/';
        if (!is_dir($dirSanitaire)) mkdir($dirSanitaire, 0755, true);
        $pdf->Output('F', $dirSanitaire . $ficheSanitaireToken);
    }

    // 3. INSERTION SQL
    $vaccinsJson = json_encode([
        'dtp' => cleanInput('vaccin_dtp'),
        'bcg' => cleanInput('vaccin_bcg'),
        'autre' => cleanInput('vaccin_autre')
    ]);

    $sql = "INSERT INTO enfants (
        parent_id, prenom, nom, date_naissance, sexe, civilite, 
        adresse, code_postal, ville, pays, tel_mobile_enfant, tel_fixe_enfant, email_enfant,
        infos_sante, regime_alimentaire, 
        poids, taille, medecin_nom, medecin_tel, vaccins_data,
        carnet_sante_token, fiche_sanitaire_token, signature_token,
        
        resp1_civilite, resp1_nom, resp1_prenom, resp1_email, resp1_tel, resp1_statut, resp1_profession,
        resp2_civilite, resp2_nom, resp2_prenom, resp2_email, resp2_tel, resp2_statut, resp2_profession,
        
        droit_image, autorisation_contact, accord_parental, cgv_accepte, newsletter_accepte, commentaires,
        date_creation
    ) VALUES (
        ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, 
        ?, ?, ?, ?, ?, 
        ?, ?, ?,
        
        ?, ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, ?, ?, 
        
        ?, ?, ?, ?, ?, ?, 
        NOW()
    )";

    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        $_SESSION['user']['id'], cleanInput('prenom'), cleanInput('nom'), cleanInput('date_naissance'), cleanInput('sexe'), cleanInput('civilite'),
        cleanInput('adresse'), cleanInput('code_postal'), cleanInput('ville'), cleanInput('pays'), cleanInput('tel_mobile_enfant'), cleanInput('tel_fixe_enfant'), cleanInput('email_enfant'),
        cleanInput('infos_sante'), cleanInput('regime_alimentaire'),
        cleanInput('poids'), cleanInput('taille'), cleanInput('medecin_nom'), cleanInput('medecin_tel'), $vaccinsJson,
        $carnetToken, $ficheSanitaireToken, $sigToken,
        
        cleanInput('resp1_civilite'), cleanInput('resp1_nom'), cleanInput('resp1_prenom'), cleanInput('resp1_email'), cleanInput('resp1_tel'), cleanInput('resp1_statut'), cleanInput('resp1_profession'),
        cleanInput('resp2_civilite'), cleanInput('resp2_nom'), cleanInput('resp2_prenom'), cleanInput('resp2_email'), cleanInput('resp2_tel'), cleanInput('resp2_statut'), cleanInput('resp2_profession'),
        
        isset($_POST['droit_image'])?1:0, 
        isset($_POST['autorisation_contact'])?1:0, 
        isset($_POST['accord_parental'])?1:0, 
        isset($_POST['cgv_accepte'])?1:0, 
        isset($_POST['newsletter_accepte'])?1:0, 
        cleanInput('commentaires')
    ]);

    header('Location: ../' . cleanInput('redirect_url') . '?success=child_added');

} catch (Exception $e) {
    die("<h1>Erreur</h1><p>" . $e->getMessage() . "</p><p><a href='javascript:history.back()'>Retour</a></p>");
}
?>