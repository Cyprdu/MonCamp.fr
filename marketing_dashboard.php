<?php
// Fichier: marketing_dashboard.php
require_once 'api/config.php';

// Sécurité
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id'])) { header('Location: login.php'); exit; }

$orga_id = $_GET['id'] ?? 0;

// 1. Récupérer l'organisme et vérifier l'appartenance
$stmt = $pdo->prepare("SELECT * FROM organisateurs WHERE id = ? AND user_id = ?");
$stmt->execute([$orga_id, $_SESSION['user']['id']]);
$orga = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$orga) die("Organisme introuvable ou accès refusé.");

// 2. Récupérer les camps de cet organisme
$stmtCamps = $pdo->prepare("
    SELECT id, nom, date_debut, date_fin, token, boost_vedette_fin, boost_urgence_fin, date_bump 
    FROM camps 
    WHERE organisateur_id = ? AND supprime = 0 
    ORDER BY date_debut DESC
");
$stmtCamps->execute([$orga_id]);
$camps = $stmtCamps->fetchAll(PDO::FETCH_ASSOC);

require_once 'partials/header.php';
?>

<title>Marketing - <?= htmlspecialchars($orga['nom']) ?></title>

<div class="min-h-screen bg-gray-50 text-gray-900 font-sans py-10">
    <div class="max-w-6xl mx-auto px-4">

        <a href="organisateurs.php" class="text-sm text-gray-500 hover:text-black mb-6 inline-block">&larr; Retour aux organismes</a>

        <div class="bg-white rounded-lg border border-gray-200 p-6 mb-8 flex flex-col md:flex-row justify-between items-center gap-6 shadow-sm">
            <div>
                <h1 class="text-2xl font-bold"><?= htmlspecialchars($orga['nom']) ?></h1>
                <p class="text-gray-500 text-sm">Tableau de bord Marketing</p>
            </div>
            
            <div class="flex items-center gap-6 bg-gray-50 px-6 py-3 rounded-lg border border-gray-100">
                <div class="text-right">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Solde Disponible</p>
                    <p class="text-2xl font-bold font-mono"><?= number_format($orga['solde_points'], 0, ',', ' ') ?> pts</p>
                </div>
                <button onclick="openShopModal()" class="bg-black hover:bg-gray-800 text-white px-5 py-2.5 rounded text-sm font-medium transition shadow-md flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Acheter des points
                </button>
            </div>
        </div>

        <h2 class="text-lg font-bold mb-4">Vos séjours éligibles au boost</h2>

        <div class="grid grid-cols-1 gap-4">
            <?php foreach($camps as $camp): ?>
                <div class="bg-white border border-gray-200 rounded-lg p-5 flex flex-col md:flex-row items-center justify-between hover:border-gray-300 transition">
                    
                    <div class="flex-grow mb-4 md:mb-0">
                        <div class="flex items-center gap-3">
                            <h3 class="font-bold text-lg"><?= htmlspecialchars($camp['nom']) ?></h3>
                            <?php if($camp['boost_vedette_fin'] && new DateTime($camp['boost_vedette_fin']) > new DateTime()): ?>
                                <span class="bg-indigo-100 text-indigo-700 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wide">À la une</span>
                            <?php endif; ?>
                            <?php if($camp['boost_urgence_fin'] && new DateTime($camp['boost_urgence_fin']) > new DateTime()): ?>
                                <span class="bg-red-100 text-red-700 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wide">Urgence</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm text-gray-500">
                            Du <?= date('d/m/Y', strtotime($camp['date_debut'])) ?> au <?= date('d/m/Y', strtotime($camp['date_fin'])) ?>
                        </p>
                    </div>

                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <a href="boost.php?t=<?= $camp['token'] ?>" class="w-full md:w-auto text-center border border-gray-300 text-gray-700 hover:border-black hover:text-black px-4 py-2 rounded text-sm font-medium transition bg-white">
                            Gérer la visibilité
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if(empty($camps)): ?>
                <div class="p-10 text-center bg-white border border-dashed border-gray-300 rounded-lg text-gray-500">
                    Aucun séjour trouvé pour cet organisme.
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<div id="shopModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeShopModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-8 py-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-900">Recharger le compte : <?= htmlspecialchars($orga['nom']) ?></h3>
                    <button onclick="closeShopModal()" class="text-gray-400 hover:text-black">&times;</button>
                </div>
                
                <div class="space-y-3">
                    <div onclick="buyPoints('decouverte')" class="cursor-pointer border border-gray-200 rounded p-4 flex items-center justify-between hover:border-black transition">
                        <div><span class="block font-bold">Pack Découverte</span><span class="text-sm text-gray-500">100 points</span></div>
                        <span class="font-mono font-bold">10,00 €</span>
                    </div>
                    <div onclick="buyPoints('standard')" class="cursor-pointer border border-black bg-gray-50 rounded p-4 flex items-center justify-between hover:bg-gray-100 transition">
                        <div><span class="block font-bold">Pack Standard</span><span class="text-sm text-gray-600">600 points (+20%)</span></div>
                        <span class="font-mono font-bold">50,00 €</span>
                    </div>
                    <div onclick="buyPoints('agence')" class="cursor-pointer border border-gray-200 rounded p-4 flex items-center justify-between hover:border-black transition">
                        <div><span class="block font-bold">Pack Agence</span><span class="text-sm text-gray-500">1500 points (+50%)</span></div>
                        <span class="font-mono font-bold">100,00 €</span>
                    </div>
                </div>
                <p class="text-center text-xs text-gray-400 mt-6">Paiement sécurisé via Stripe</p>
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
        const modal = document.querySelector('#shopModal .bg-white');
        modal.classList.add('opacity-50', 'pointer-events-none');

        fetch('api/create_stripe_boost_session.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ 
                pack: packType, 
                organisateur_id: <?= $orga_id ?> // On envoie l'ID direct ici
            })
        })
        .then(res => res.json())
        .then(session => {
            if(session.error) { alert(session.error); location.reload(); } 
            else { stripe.redirectToCheckout({ sessionId: session.id }); }
        })
        .catch(err => { alert("Erreur réseau"); location.reload(); });
    }
</script>

<?php require_once 'partials/footer.php'; ?>