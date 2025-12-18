<?php
/**
 * Page : info_inscrit.php
 * Objectif : Dashboard complet de l'inscription pour le parent
 */

require_once 'api/config.php';
require_once 'partials/header.php';

// 1. Sécurité : Vérification de connexion
if (!isset($_SESSION['user'])) {
    header('Location: login');
    exit;
}

$camp_id = $_GET['camp_id'] ?? '';
$child_id = $_GET['child_id'] ?? '';

if (empty($camp_id) || empty($child_id)) {
    header('Location: reservations');
    exit;
}

// 2. Récupération des données complètes (Jointures SQL pour tout avoir en une seule requête)
try {
    $sql = "
        SELECT 
            i.id AS inscription_id, i.statut AS statut_inscription, i.statut_paiement, i.date_inscription, i.prix_final,
            c.id AS camp_real_id, c.nom AS camp_nom, c.description, c.ville, c.adresse, c.code_postal, c.date_debut, c.date_fin, c.image_url,
            e.id AS enfant_real_id, e.prenom AS enfant_prenom, e.nom AS enfant_nom, e.date_naissance, e.infos_sante, e.taille, e.poids,
            o.id AS orga_id, o.nom AS orga_nom, o.email AS orga_email, o.tel AS orga_tel, o.user_id AS orga_user_id, o.logo_url
        FROM inscriptions i
        JOIN camps c ON i.camp_id = c.id
        JOIN enfants e ON i.enfant_id = e.id
        JOIN organisateurs o ON c.organisateur_id = o.id
        WHERE i.camp_id = ? AND i.enfant_id = ? AND e.parent_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$camp_id, $child_id, $_SESSION['user']['id']]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        // Sécurité anti-IDOR : Si pas de résultat, c'est que l'enfant n'est pas au parent ou n'existe pas
        die('<div class="container mx-auto py-20 text-center"><h1 class="text-3xl font-bold text-red-600">Accès Refusé</h1><p class="mt-4 text-gray-600">Inscription introuvable ou vous n\'avez pas les droits.</p><a href="reservations" class="inline-block mt-6 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Retour aux réservations</a></div>');
    }

} catch (Exception $e) {
    die("Erreur technique : " . $e->getMessage());
}

// 3. Préparation des variables d'affichage
$date_debut = new DateTime($data['date_debut']);
$date_fin = new DateTime($data['date_fin']);
$duree = $date_fin->diff($date_debut)->days;
$is_paid = ($data['statut_paiement'] === 'PAYE');
$image_camp = !empty($data['image_url']) ? htmlspecialchars($data['image_url']) : 'https://placehold.co/1200x400/e2e8f0/2563eb?text=Camp';
$logo_orga = !empty($data['logo_url']) ? htmlspecialchars($data['logo_url']) : 'https://placehold.co/100x100/f3f4f6/9ca3af?text=Orga';

// Calcul de l'avancement pour la barre de progression
$progress_step = 1; // 1: Inscrit
if ($data['statut_inscription'] === 'Confirmé' || $data['statut_inscription'] === 'Validé') $progress_step = 2;
if ($is_paid) $progress_step = 3;

?>

<main class="bg-gray-50 min-h-screen pb-12">
    
    <div class="relative w-full h-72 lg:h-96 bg-gray-900 shadow-xl overflow-hidden group">
        <img src="<?= $image_camp ?>" alt="Couverture du camp" class="w-full h-full object-cover opacity-60 group-hover:scale-105 transition-transform duration-700">
        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-transparent to-transparent"></div>
        
        <div class="absolute bottom-0 left-0 w-full p-4 sm:p-8">
            <div class="container mx-auto max-w-6xl">
                <a href="reservations" class="inline-flex items-center text-gray-300 hover:text-white mb-4 transition text-sm font-medium">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Retour aux réservations
                </a>
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                    <div>
                        <h1 class="text-3xl md:text-5xl font-bold text-white mb-2 leading-tight"><?= htmlspecialchars($data['camp_nom']) ?></h1>
                        <p class="text-gray-200 text-lg flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <?= htmlspecialchars($data['ville']) ?> (<?= htmlspecialchars($data['code_postal']) ?>)
                        </p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <?php if($is_paid): ?>
                            <span class="px-4 py-2 rounded-full bg-green-500/20 backdrop-blur-md border border-green-400 text-green-100 font-semibold shadow-sm flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Dossier Complet
                            </span>
                        <?php else: ?>
                            <span class="px-4 py-2 rounded-full bg-yellow-500/20 backdrop-blur-md border border-yellow-400 text-yellow-100 font-semibold shadow-sm flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Paiement en attente
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 -mt-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 space-y-8">

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">Suivi du dossier</h2>
                    <div class="relative">
                        <div class="absolute top-1/2 left-0 w-full h-1 bg-gray-200 -translate-y-1/2 rounded"></div>
                        <div class="absolute top-1/2 left-0 h-1 bg-gradient-to-r from-blue-500 to-purple-500 -translate-y-1/2 rounded transition-all duration-1000" style="width: <?= ($progress_step - 1) * 50 ?>%;"></div>
                        
                        <div class="relative flex justify-between">
                            <div class="flex flex-col items-center group">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white bg-gradient-to-r from-blue-500 to-purple-500 ring-4 ring-white z-10 shadow-md">1</div>
                                <span class="mt-2 text-sm font-semibold text-gray-800">Inscription</span>
                                <span class="text-xs text-gray-500"><?= date('d/m/Y', strtotime($data['date_inscription'])) ?></span>
                            </div>
                            <div class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold z-10 ring-4 ring-white shadow-md <?= $progress_step >= 2 ? 'bg-gradient-to-r from-blue-500 to-purple-500 text-white' : 'bg-gray-200 text-gray-500' ?>">2</div>
                                <span class="mt-2 text-sm font-semibold <?= $progress_step >= 2 ? 'text-gray-800' : 'text-gray-400' ?>">Validation</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold z-10 ring-4 ring-white shadow-md <?= $progress_step >= 3 ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-500' ?>">
                                    <?php if($progress_step >= 3): ?><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><?php else: ?>3<?php endif; ?>
                                </div>
                                <span class="mt-2 text-sm font-semibold <?= $progress_step >= 3 ? 'text-green-600' : 'text-gray-400' ?>">Paiement</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 sm:p-8">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Dates et Lieu
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                                <p class="text-sm text-blue-600 font-semibold uppercase tracking-wide">Début du séjour</p>
                                <p class="text-lg font-bold text-gray-800"><?= $date_debut->format('d/m/Y') ?></p>
                                <p class="text-sm text-gray-600">à 14h00 (exemple)</p>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-xl border border-purple-100">
                                <p class="text-sm text-purple-600 font-semibold uppercase tracking-wide">Fin du séjour</p>
                                <p class="text-lg font-bold text-gray-800"><?= $date_fin->format('d/m/Y') ?></p>
                                <p class="text-sm text-gray-600">Durée : <?= $duree ?> jours</p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h3 class="font-semibold text-gray-800 mb-2">Adresse :</h3>
                            <p class="text-gray-600"><?= htmlspecialchars($data['adresse']) ?>, <?= htmlspecialchars($data['code_postal']) ?> <?= htmlspecialchars($data['ville']) ?></p>
                        </div>

                        <div class="prose prose-sm text-gray-600 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                            <?= nl2br(htmlspecialchars($data['description'])) ?>
                        </div>
                    </div>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($data['adresse'] . ' ' . $data['ville']) ?>" target="_blank" class="block bg-gray-50 hover:bg-gray-100 text-center py-3 text-sm text-blue-600 font-medium border-t transition">
                        Voir l'itinéraire sur Google Maps &rarr;
                    </a>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8 relative">
                    <div class="flex justify-between items-start mb-6">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Fiche Enfant
                        </h2>
                        <a href="modif_child?id=<?= $data['enfant_real_id'] ?>" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg transition flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            Modifier
                        </a>
                    </div>

                    <div class="flex items-center mb-6">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-pink-400 to-orange-400 flex items-center justify-center text-2xl font-bold text-white border-4 border-white shadow">
                            <?= strtoupper(substr($data['enfant_prenom'], 0, 1)) ?>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($data['enfant_prenom']) ?> <?= htmlspecialchars($data['enfant_nom']) ?></h3>
                            <p class="text-gray-500 text-sm">Né(e) le <?= date('d/m/Y', strtotime($data['date_naissance'])) ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <span class="block text-xs font-bold text-gray-400 uppercase">Santé / Allergies</span>
                            <span class="text-gray-800"><?= !empty($data['infos_sante']) ? htmlspecialchars($data['infos_sante']) : 'Aucune info' ?></span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <span class="block text-xs font-bold text-gray-400 uppercase">Taille / Poids</span>
                            <span class="text-gray-800">
                                <?= !empty($data['taille']) ? htmlspecialchars($data['taille']).' cm' : '?' ?> / 
                                <?= !empty($data['poids']) ? htmlspecialchars($data['poids']).' kg' : '?' ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mt-4 p-3 bg-yellow-50 text-yellow-800 rounded-lg text-sm flex items-start">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p>N'oubliez pas de mettre à jour la fiche sanitaire de liaison (PDF) avant le départ.</p>
                    </div>
                </div>

            </div>

            <div class="lg:col-span-1 space-y-8">

                <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 text-center">
                    <div class="w-20 h-20 mx-auto bg-gray-100 rounded-full p-1 mb-4 shadow-sm">
                        <img src="<?= $logo_orga ?>" alt="Logo Orga" class="w-full h-full object-contain rounded-full">
                    </div>
                    <h3 class="font-bold text-gray-900 text-lg mb-1"><?= htmlspecialchars($data['orga_nom']) ?></h3>
                    <p class="text-sm text-gray-500 mb-6">Organisateur certifié</p>
                    
                    <div class="space-y-3">
                        <a href="messagerie?new_conv=<?= $data['orga_user_id'] ?>&camp_id=<?= $camp_id ?>" class="block w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition shadow-lg shadow-blue-500/30 flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                            Envoyer un message
                        </a>
                        <?php if(!empty($data['orga_tel'])): ?>
                            <a href="tel:<?= htmlspecialchars($data['orga_tel']) ?>" class="block w-full py-2.5 px-4 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-medium rounded-xl transition flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <?= htmlspecialchars($data['orga_tel']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Documents & Paiement</h3>
                    
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-600">Total séjour</span>
                            <span class="font-bold text-lg text-gray-900"><?= number_format($data['prix_final'], 2, ',', ' ') ?> €</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5">
                            <div class="bg-green-500 h-2.5 rounded-full" style="width: <?= $is_paid ? '100%' : '0%' ?>"></div>
                        </div>
                        <p class="text-xs text-right mt-1 <?= $is_paid ? 'text-green-600' : 'text-orange-500' ?>">
                            <?= $is_paid ? 'Règlement effectué' : 'Reste à payer : ' . number_format($data['prix_final'], 2, ',', ' ') . ' €' ?>
                        </p>
                        
                        <?php if(!$is_paid): ?>
                            <form action="api/create_stripe_session.php" method="POST" class="mt-3">
                                <input type="hidden" name="inscription_id" value="<?= $data['inscription_id'] ?>">
                                <button type="submit" class="w-full py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg text-sm font-bold shadow hover:shadow-lg transition">
                                    Payer maintenant
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-3">
                        <a href="generate_pdf_certificat.php?inscription_id=<?= $data['inscription_id'] ?>" target="_blank" class="flex items-center p-3 rounded-lg border border-gray-200 hover:border-red-300 hover:bg-red-50 transition group">
                            <div class="bg-red-100 p-2 rounded-lg text-red-600 group-hover:text-red-700">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-bold text-gray-800 group-hover:text-red-700">Certificat d'inscription</p>
                                <p class="text-xs text-gray-500">Format PDF</p>
                            </div>
                        </a>

                        <?php if (!empty($data['fiche_sanitaire_token'])): ?>
                        <a href="uploads/sante/<?= htmlspecialchars($data['fiche_sanitaire_token']) ?>" target="_blank" class="flex items-center p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition group">
                            <div class="bg-blue-100 p-2 rounded-lg text-blue-600 group-hover:text-blue-700">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-bold text-gray-800 group-hover:text-blue-700">Fiche Sanitaire</p>
                                <p class="text-xs text-gray-500">Document envoyé</p>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <p class="text-sm text-gray-500">Une question ? <a href="aide.php" class="text-blue-600 hover:underline">Consultez la FAQ</a></p>
                </div>

            </div>
        </div>
    </div>
</main>

<?php require_once 'partials/footer.php'; ?>