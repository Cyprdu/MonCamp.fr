<?php
// Fichier: boost.php
require_once 'api/config.php';

// 1. Sécurité
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id']) || empty($_SESSION['user']['is_directeur'])) {
    header('Location: index.php');
    exit;
}

$token = $_GET['t'] ?? '';
if (empty($token)) {
    header('Location: mes_camps.php');
    exit;
}

// 2. Récupération des données (Correction SQL : o.nom au lieu de o.nom_structure)
try {
    $stmt = $pdo->prepare("
        SELECT c.*, o.id as orga_id, o.nom, o.solde_points 
        FROM camps c 
        JOIN organisateurs o ON c.organisateur_id = o.id 
        WHERE c.token = ?
    ");
    $stmt->execute([$token]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) die("Séjour introuvable.");

    $camp = $data;
    $soldeGlobal = intval($data['solde_points']);
    $nomStructure = $data['nom']; // Utilisation de la bonne colonne

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

require_once 'partials/header.php';
?>

<title>Marketing - <?= htmlspecialchars($camp['nom']) ?></title>

<div class="min-h-screen bg-gray-50 text-gray-900 font-sans py-10">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">

        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10 pb-6 border-b border-gray-200">
            <div>
                <a href="mes_camps.php" class="text-xs font-semibold text-gray-500 hover:text-black uppercase tracking-wider mb-2 inline-flex items-center transition-colors">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Retour aux séjours
                </a>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">Centre de Visibilité</h1>
                <p class="text-gray-500 mt-1">Gérez l'exposition du séjour : <span class="font-medium text-black"><?= htmlspecialchars($camp['nom']) ?></span></p>
            </div>

            <div class="bg-white p-5 rounded-lg border border-gray-300 shadow-sm flex items-center gap-6">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Portefeuille <?= htmlspecialchars($nomStructure) ?></p>
                    <div class="flex items-baseline gap-1">
                        <span class="text-3xl font-bold text-black" id="displaySolde"><?= number_format($soldeGlobal, 0, ',', ' ') ?></span>
                        <span class="text-sm font-medium text-gray-500">pts disponibles</span>
                    </div>
                </div>
                <button onclick="openShopModal()" class="bg-black hover:bg-gray-800 text-white text-sm font-medium py-2 px-5 rounded transition shadow-sm">
                    Recharger
                </button>
            </div>
        </div>

        <div id="feedbackMessage" class="hidden mb-8 p-4 border text-sm font-medium rounded-sm"></div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <div class="bg-white p-6 rounded-lg border border-gray-200 hover:border-gray-400 transition-all group flex flex-col">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-2 bg-gray-50 rounded text-gray-600 group-hover:bg-black group-hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Instantané</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Remontée immédiate</h3>
                <p class="text-sm text-gray-500 leading-relaxed mb-6 flex-grow">
                    Repositionne ce séjour tout en haut des résultats de recherche maintenant. Idéal pour capter le trafic du jour.
                </p>
                <div class="mt-auto border-t border-gray-100 pt-4 flex justify-between items-center">
                    <span class="font-mono font-bold">10 pts</span>
                    <button onclick="applyBoost('bump', 10)" class="text-sm border border-gray-300 px-4 py-2 rounded hover:bg-gray-50 text-gray-700 font-medium transition">
                        Activer
                    </button>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg border-2 border-black shadow-md relative flex flex-col transform md:-translate-y-2">
                <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-black text-white text-[10px] font-bold px-3 py-1 uppercase tracking-widest">Recommandé</div>
                
                <div class="flex justify-between items-start mb-4">
                    <div class="p-2 bg-gray-100 rounded text-black">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wide text-black bg-gray-100 px-2 py-1 rounded">7 Jours</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Mise à la Une</h3>
                <p class="text-sm text-gray-500 leading-relaxed mb-6 flex-grow">
                    Épinglé dans le Top 3 des résultats avec le badge "Sélection". Maximise la visibilité sur la durée.
                </p>
                <div class="mt-auto border-t border-gray-100 pt-4 flex justify-between items-center">
                    <span class="font-mono font-bold text-black">100 pts</span>
                    <?php if($camp['boost_vedette_fin'] && new DateTime($camp['boost_vedette_fin']) > new DateTime()): ?>
                        <div class="text-xs font-bold text-green-700 bg-green-50 px-3 py-2 rounded">
                            Actif jsq <?= date('d/m', strtotime($camp['boost_vedette_fin'])) ?>
                        </div>
                    <?php else: ?>
                        <button onclick="applyBoost('vedette', 100)" class="text-sm bg-black text-white px-4 py-2 rounded hover:bg-gray-800 font-medium transition shadow-lg">
                            Activer
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg border border-gray-200 hover:border-red-300 transition-all group flex flex-col">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-2 bg-gray-50 rounded text-red-600 group-hover:bg-red-600 group-hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wide text-gray-400">3 Jours</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Dernière minute</h3>
                <p class="text-sm text-gray-500 leading-relaxed mb-6 flex-grow">
                    Badge rouge "Dernières places" pour créer un sentiment d'urgence et combler les places vides.
                </p>
                <div class="mt-auto border-t border-gray-100 pt-4 flex justify-between items-center">
                    <span class="font-mono font-bold">50 pts</span>
                    <?php if($camp['boost_urgence_fin'] && new DateTime($camp['boost_urgence_fin']) > new DateTime()): ?>
                        <div class="text-xs font-bold text-green-700 bg-green-50 px-3 py-2 rounded">
                            Actif jsq <?= date('d/m', strtotime($camp['boost_urgence_fin'])) ?>
                        </div>
                    <?php else: ?>
                        <button onclick="applyBoost('urgence', 50)" class="text-sm border border-gray-300 px-4 py-2 rounded hover:bg-gray-50 text-red-600 font-medium transition">
                            Activer
                        </button>
                    <?php endif; ?>
                </div>
            </div>

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
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Recharger votre portefeuille</h3>
                        <p class="text-xs text-gray-500 mt-1">Ces points sont valables pour <strong>tous</strong> vos séjours.</p>
                    </div>
                    <button onclick="closeShopModal()" class="text-gray-400 hover:text-black transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-3">
                    <div onclick="buyPoints('decouverte')" class="cursor-pointer border border-gray-200 rounded p-4 flex items-center justify-between hover:border-black transition group">
                        <div>
                            <span class="block font-bold text-gray-900 group-hover:text-black">Pack Découverte</span>
                            <span class="text-sm text-gray-500">100 points</span>
                        </div>
                        <span class="font-mono font-bold text-gray-900">10,00 €</span>
                    </div>

                    <div onclick="buyPoints('standard')" class="cursor-pointer border border-black bg-gray-50 rounded p-4 flex items-center justify-between hover:bg-gray-100 transition relative">
                        <div class="absolute -top-2 -right-2 bg-black text-white text-[10px] font-bold px-2 py-0.5 uppercase tracking-wide shadow-sm">Populaire</div>
                        <div>
                            <span class="block font-bold text-black">Pack Standard</span>
                            <span class="text-sm text-gray-600">600 points <span class="ml-2 text-[10px] bg-black text-white px-1.5 py-0.5 rounded">+20% Bonus</span></span>
                        </div>
                        <span class="font-mono font-bold text-black">50,00 €</span>
                    </div>

                    <div onclick="buyPoints('agence')" class="cursor-pointer border border-gray-200 rounded p-4 flex items-center justify-between hover:border-black transition group">
                        <div>
                            <span class="block font-bold text-gray-900 group-hover:text-black">Pack Agence</span>
                            <span class="text-sm text-gray-500">1500 points <span class="ml-2 text-[10px] bg-gray-200 text-gray-700 px-1.5 py-0.5 rounded">+50% Bonus</span></span>
                        </div>
                        <span class="font-mono font-bold text-gray-900">100,00 €</span>
                    </div>
                </div>
                
                <div class="mt-6 text-center">
                    <p class="text-[10px] text-gray-400 uppercase tracking-widest">Paiement sécurisé via Stripe</p>
                </div>
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
                token: '<?= $token ?>' 
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
            alert("Erreur réseau");
            location.reload();
        });
    }

    function applyBoost(type, cost) {
        if(!confirm(`Débiter ${cost} points de votre solde global ?`)) return;

        fetch('api/apply_boost.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ token: '<?= $token ?>', type: type })
        })
        .then(res => res.json())
        .then(data => {
            const feedback = document.getElementById('feedbackMessage');
            feedback.classList.remove('hidden', 'bg-red-50', 'text-red-800', 'bg-green-50', 'text-green-800', 'border-red-200', 'border-green-200');
            
            if (data.success) {
                feedback.classList.add('bg-green-50', 'text-green-800', 'border-green-200');
                feedback.innerHTML = "Boost activé ! Mise à jour en cours...";
                setTimeout(() => location.reload(), 1000);
            } else {
                feedback.classList.add('bg-red-50', 'text-red-800', 'border-red-200');
                feedback.innerHTML = data.message || "Une erreur est survenue.";
            }
            feedback.classList.remove('hidden');
        })
        .catch(err => alert("Erreur de connexion."));
    }
</script>

<?php require_once 'partials/footer.php'; ?>