<?php
// Fichier: marketing.php
require_once 'api/config.php';
require_once 'partials/header.php';

// SÉCURITÉ
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id']) || !$_SESSION['user']['is_directeur']) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$orgaId = $_GET['id'] ?? null;

// --- VUE 1 : SÉLECTION DE L'ORGANISME ---
if (!$orgaId) {
    $stmt = $pdo->prepare("SELECT id, nom, solde_points, logo_url FROM organisateurs WHERE user_id = ? ORDER BY nom ASC");
    $stmt->execute([$userId]);
    $orgas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
    <div class="min-h-screen bg-gray-50 py-12 font-sans text-slate-800">
        <div class="max-w-5xl mx-auto px-4">
            <div class="mb-8">
                <a href="profile.php" class="text-sm text-gray-500 hover:text-black mb-2 inline-block">&larr; Retour tableau de bord</a>
                <h1 class="text-3xl font-bold text-gray-900">Marketing & Visibilité</h1>
                <p class="text-gray-500 mt-2">Sélectionnez l'organisme pour lequel vous souhaitez gérer la publicité.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach($orgas as $orga): ?>
                    <a href="marketing.php?id=<?= $orga['id'] ?>" class="group block bg-white border border-gray-200 rounded-xl p-6 hover:border-black transition-all hover:shadow-lg relative overflow-hidden">
                        <div class="flex justify-between items-start">
                            <div class="flex items-center gap-4">
                                <?php if(!empty($orga['logo_url'])): ?>
                                    <img src="<?= htmlspecialchars($orga['logo_url']) ?>" class="w-12 h-12 rounded object-cover border">
                                <?php else: ?>
                                    <div class="w-12 h-12 rounded bg-gray-100 flex items-center justify-center text-gray-400 font-bold text-xl">
                                        <?= strtoupper(substr($orga['nom'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h3 class="font-bold text-lg text-gray-900 group-hover:text-blue-600 transition-colors"><?= htmlspecialchars($orga['nom']) ?></h3>
                                    <p class="text-xs text-gray-500 uppercase tracking-wide">Solde actuel</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="block text-2xl font-bold font-mono text-gray-900"><?= number_format($orga['solde_points'], 0, ',', ' ') ?></span>
                                <span class="text-xs font-medium text-gray-400">PTS</span>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <span class="text-sm font-medium text-blue-600 group-hover:translate-x-1 transition-transform inline-flex items-center">
                                Gérer & Recharger &rarr;
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
                
                <?php if(empty($orgas)): ?>
                    <div class="col-span-2 text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
                        <p class="text-gray-500">Vous n'avez pas encore créé d'organisme.</p>
                        <a href="create_organisateur.php" class="mt-4 inline-block bg-black text-white px-4 py-2 rounded text-sm font-medium">Créer un organisme</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php
    require_once 'partials/footer.php';
    exit;
}

// --- VUE 2 : DASHBOARD DE L'ORGANISME ---
// Récupération sécurisée de l'organisme
$stmt = $pdo->prepare("SELECT * FROM organisateurs WHERE id = ? AND user_id = ?");
$stmt->execute([$orgaId, $userId]);
$orga = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$orga) die("Organisme introuvable ou accès refusé.");

// Récupération des camps
$stmtCamps = $pdo->prepare("
    SELECT id, nom, date_debut, date_fin, token, boost_vedette_fin, boost_urgence_fin, date_bump 
    FROM camps 
    WHERE organisateur_id = ? AND supprime = 0 
    ORDER BY date_debut DESC
");
$stmtCamps->execute([$orgaId]);
$camps = $stmtCamps->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="min-h-screen bg-gray-50 py-10 font-sans text-slate-800">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        
        <div class="mb-8">
            <a href="marketing.php" class="text-sm text-gray-500 hover:text-black mb-4 inline-flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Changer d'organisme
            </a>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col md:flex-row justify-between items-center gap-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($orga['nom']) ?></h1>
                    <p class="text-sm text-gray-500 mt-1">Gérez votre portefeuille de points et boostez vos séjours.</p>
                </div>
                
                <div class="flex items-center gap-6 bg-slate-50 px-6 py-4 rounded-lg border border-slate-100">
                    <div class="text-right">
                        <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Portefeuille</p>
                        <p class="text-3xl font-bold font-mono text-slate-900"><?= number_format($orga['solde_points'], 0, ',', ' ') ?> <span class="text-sm font-sans font-medium text-slate-500">pts</span></p>
                    </div>
                    <button onclick="openShopModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-lg text-sm font-bold shadow-md transition-all hover:-translate-y-0.5 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Recharger
                    </button>
                </div>
            </div>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="bg-green-50 text-green-800 border border-green-200 p-4 rounded-lg mb-6 text-center font-medium">
                Paiement réussi ! Vos points ont été crédités.
            </div>
        <?php endif; ?>

        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            Vos séjours éligibles
            <span class="bg-gray-100 text-gray-600 text-xs py-0.5 px-2 rounded-full"><?= count($camps) ?></span>
        </h2>

        <div class="space-y-4">
            <?php foreach($camps as $camp): ?>
                <div class="bg-white border border-gray-200 rounded-lg p-5 flex flex-col md:flex-row items-center justify-between hover:border-blue-300 transition-colors group">
                    
                    <div class="flex-grow mb-4 md:mb-0 w-full md:w-auto">
                        <div class="flex items-center gap-3 mb-1">
                            <h3 class="font-bold text-lg text-gray-900"><?= htmlspecialchars($camp['nom']) ?></h3>
                            
                            <?php if($camp['boost_vedette_fin'] && new DateTime($camp['boost_vedette_fin']) > new DateTime()): ?>
                                <span class="bg-indigo-100 text-indigo-700 text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wide border border-indigo-200">À la une</span>
                            <?php endif; ?>
                            
                            <?php if($camp['boost_urgence_fin'] && new DateTime($camp['boost_urgence_fin']) > new DateTime()): ?>
                                <span class="bg-red-100 text-red-700 text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wide border border-red-200">Urgence</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm text-gray-500 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Du <?= date('d/m/Y', strtotime($camp['date_debut'])) ?> au <?= date('d/m/Y', strtotime($camp['date_fin'])) ?>
                        </p>
                    </div>

                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <a href="boost.php?t=<?= $camp['token'] ?>" class="w-full md:w-auto text-center bg-white border border-gray-300 text-gray-700 hover:border-blue-600 hover:text-blue-600 px-5 py-2.5 rounded-lg text-sm font-medium transition shadow-sm flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Booster
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if(empty($camps)): ?>
                <div class="p-12 text-center bg-white border border-dashed border-gray-300 rounded-xl text-gray-500">
                    <p class="mb-4">Aucun séjour actif pour cet organisme.</p>
                    <a href="create_camp.php" class="text-blue-600 font-medium hover:underline">Publier un séjour</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="shopModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="closeShopModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-8 py-8">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Recharger le compte</h3>
                        <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($orga['nom']) ?></p>
                    </div>
                    <button onclick="closeShopModal()" class="text-gray-400 hover:text-gray-600 transition bg-gray-100 p-2 rounded-full">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div onclick="buyPoints('decouverte')" class="cursor-pointer border border-gray-200 rounded-xl p-5 flex items-center justify-between hover:border-blue-600 hover:bg-blue-50 transition group">
                        <div class="flex items-center gap-4">
                            <div class="h-10 w-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold">D</div>
                            <div>
                                <span class="block font-bold text-gray-900 group-hover:text-blue-700">Pack Découverte</span>
                                <span class="text-sm text-gray-500">100 points</span>
                            </div>
                        </div>
                        <span class="font-mono font-bold text-lg text-gray-900">10,00 €</span>
                    </div>

                    <div onclick="buyPoints('standard')" class="cursor-pointer border-2 border-blue-600 bg-white rounded-xl p-5 flex items-center justify-between hover:shadow-md transition relative transform hover:-translate-y-1">
                        <div class="absolute -top-3 right-4 bg-blue-600 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wide shadow-sm">Populaire</div>
                        <div class="flex items-center gap-4">
                            <div class="h-10 w-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">S</div>
                            <div>
                                <span class="block font-bold text-gray-900">Pack Standard</span>
                                <span class="text-sm text-gray-500">600 points <span class="text-green-600 font-bold bg-green-50 px-2 py-0.5 rounded text-xs ml-2">+20% Offert</span></span>
                            </div>
                        </div>
                        <span class="font-mono font-bold text-lg text-blue-700">50,00 €</span>
                    </div>

                    <div onclick="buyPoints('agence')" class="cursor-pointer border border-gray-200 rounded-xl p-5 flex items-center justify-between hover:border-blue-600 hover:bg-blue-50 transition group">
                        <div class="flex items-center gap-4">
                            <div class="h-10 w-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center font-bold">A</div>
                            <div>
                                <span class="block font-bold text-gray-900 group-hover:text-blue-700">Pack Agence</span>
                                <span class="text-sm text-gray-500">1500 points <span class="text-green-600 font-bold bg-green-50 px-2 py-0.5 rounded text-xs ml-2">+50% Offert</span></span>
                            </div>
                        </div>
                        <span class="font-mono font-bold text-lg text-gray-900">100,00 €</span>
                    </div>
                </div>
                
                <p class="text-center text-xs text-gray-400 mt-8 flex items-center justify-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Paiement sécurisé via Stripe
                </p>
            </div>
        </div>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe('<?= STRIPE_PUBLISHABLE_KEY ?>');

    function openShopModal() { document.getElementById('shopModal').classList.remove('hidden'); }
    function closeShopModal() { document.getElementById('shopModal').classList.add('hidden'); }

    function buyPoints(packType) {
        // Feedback visuel
        const modal = document.querySelector('#shopModal .bg-white');
        modal.classList.add('opacity-50', 'pointer-events-none');

        fetch('api/create_stripe_boost_session.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ 
                pack: packType, 
                organisateur_id: <?= $orgaId ?> // ID de l'organisme actuel
            })
        })
        .then(res => res.json())
        .then(session => {
            if(session.error) {
                alert(session.error);
                location.reload();
            } else {
                stripe.redirectToCheckout({ sessionId: session.id });
            }
        })
        .catch(err => {
            alert("Erreur de connexion.");
            location.reload();
        });
    }
</script>

<?php require_once 'partials/footer.php'; ?>