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

// 2. Récupération du camp et de l'organisateur
try {
    // On récupère le camp + le solde de l'organisateur
    $stmt = $pdo->prepare("
        SELECT c.*, o.id as orga_id, o.solde_points 
        FROM camps c 
        JOIN organisateurs o ON c.organisateur_id = o.id 
        WHERE c.token = ?
    ");
    $stmt->execute([$token]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) die("Séjour introuvable.");

    // Séparation des données pour plus de clarté
    $camp = $data;
    $solde = intval($data['solde_points']);

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

require_once 'partials/header.php';
?>

<title>Booster - <?= htmlspecialchars($camp['nom']) ?></title>

<style>
    .card-option:hover { transform: translateY(-5px); }
    .btn-gradient { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .bg-vedette { background: linear-gradient(135deg, #FFD700 0%, #FDB931 100%); }
    .bg-urgence { background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%); }
    .bg-bump { background: linear-gradient(135deg, #4299E1 0%, #3182CE 100%); }
</style>

<div class="min-h-screen bg-gray-50 py-12 font-sans">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-10 border border-gray-100 flex flex-col md:flex-row items-center justify-between gap-6">
            <div>
                <h1 class="text-2xl font-extrabold text-[#0A112F]">
                    Centre de Visibilité
                </h1>
                <p class="text-gray-500">Gérez la puissance marketing de <span class="font-bold text-blue-600"><?= htmlspecialchars($camp['nom']) ?></span></p>
            </div>

            <div class="flex items-center gap-6 bg-blue-50 px-6 py-3 rounded-xl border border-blue-100">
                <div>
                    <p class="text-xs text-blue-600 font-bold uppercase tracking-wider">Votre Solde</p>
                    <div class="text-3xl font-black text-[#0A112F]">
                        <span id="displaySolde"><?= number_format($solde, 0, ',', ' ') ?></span> <span class="text-sm font-medium text-gray-400">pts</span>
                    </div>
                </div>
                <button onclick="openShopModal()" class="btn-gradient text-white font-bold py-3 px-6 rounded-lg shadow-lg hover:shadow-xl transition transform hover:scale-105 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Recharger
                </button>
            </div>
        </div>

        <div id="feedbackMessage" class="hidden mb-8 p-4 rounded-lg shadow-sm border text-center font-bold"></div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            <div class="card-option bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden transition-all duration-300">
                <div class="h-2 bg-bump w-full"></div>
                <div class="p-8 flex flex-col h-full">
                    <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mb-6 mx-auto text-blue-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    </div>
                    <h3 class="text-xl font-extrabold text-center text-gray-800 mb-2">Le Coup de Pouce</h3>
                    <p class="text-center text-gray-500 text-sm mb-6 flex-grow">
                        Votre séjour remonte instantanément <strong>tout en haut</strong> des résultats de recherche, comme s'il venait d'être publié.
                    </p>
                    
                    <div class="flex justify-center items-baseline mb-6">
                        <span class="text-3xl font-black text-gray-800">10</span>
                        <span class="text-gray-500 ml-1">pts</span>
                        <span class="text-xs text-gray-400 ml-2">/ remontée</span>
                    </div>

                    <button onclick="applyBoost('bump', 10)" class="w-full border-2 border-blue-500 text-blue-600 hover:bg-blue-50 font-bold py-3 rounded-xl transition">
                        Remonter maintenant
                    </button>
                </div>
            </div>

            <div class="card-option bg-white rounded-2xl shadow-lg border-2 border-yellow-400 overflow-hidden transition-all duration-300 relative transform scale-105 z-10">
                <div class="absolute top-0 right-0 bg-yellow-400 text-white text-xs font-bold px-3 py-1 rounded-bl-lg">POPULAIRE</div>
                <div class="h-2 bg-vedette w-full"></div>
                <div class="p-8 flex flex-col h-full">
                    <div class="bg-yellow-100 w-16 h-16 rounded-full flex items-center justify-center mb-6 mx-auto text-yellow-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    </div>
                    <h3 class="text-xl font-extrabold text-center text-gray-800 mb-2">La Semaine Star</h3>
                    <p class="text-center text-gray-500 text-sm mb-6 flex-grow">
                        Fixé dans les <strong>3 premiers résultats</strong> pendant 7 jours avec un badge "⭐ Sélection".
                    </p>

                    <div class="flex justify-center items-baseline mb-6">
                        <span class="text-3xl font-black text-yellow-500">100</span>
                        <span class="text-gray-500 ml-1">pts</span>
                        <span class="text-xs text-gray-400 ml-2">/ 7 jours</span>
                    </div>

                    <?php if($camp['boost_vedette_fin'] && new DateTime($camp['boost_vedette_fin']) > new DateTime()): ?>
                        <div class="text-center bg-yellow-50 border border-yellow-200 text-yellow-800 p-3 rounded-xl text-sm font-bold">
                            Actif jusqu'au <?= date('d/m H:i', strtotime($camp['boost_vedette_fin'])) ?>
                        </div>
                    <?php else: ?>
                        <button onclick="applyBoost('vedette', 100)" class="w-full bg-vedette hover:opacity-90 text-white font-bold py-3 rounded-xl shadow-md transition">
                            Activer 7 jours
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-option bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden transition-all duration-300">
                <div class="h-2 bg-urgence w-full"></div>
                <div class="p-8 flex flex-col h-full">
                    <div class="bg-red-100 w-16 h-16 rounded-full flex items-center justify-center mb-6 mx-auto text-red-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-xl font-extrabold text-center text-gray-800 mb-2">L'Urgence</h3>
                    <p class="text-center text-gray-500 text-sm mb-6 flex-grow">
                        Badge rouge <strong>"Dernières places"</strong> + mise en avant dans les emails aux parents inscrits. Durée 3 jours.
                    </p>

                    <div class="flex justify-center items-baseline mb-6">
                        <span class="text-3xl font-black text-gray-800">50</span>
                        <span class="text-gray-500 ml-1">pts</span>
                        <span class="text-xs text-gray-400 ml-2">/ 3 jours</span>
                    </div>

                    <?php if($camp['boost_urgence_fin'] && new DateTime($camp['boost_urgence_fin']) > new DateTime()): ?>
                        <div class="text-center bg-red-50 border border-red-200 text-red-800 p-3 rounded-xl text-sm font-bold">
                            Actif jusqu'au <?= date('d/m H:i', strtotime($camp['boost_urgence_fin'])) ?>
                        </div>
                    <?php else: ?>
                        <button onclick="applyBoost('urgence', 50)" class="w-full border-2 border-red-500 text-red-600 hover:bg-red-50 font-bold py-3 rounded-xl transition">
                            Activer 3 jours
                        </button>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<div id="shopModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full p-8 relative">
        <button onclick="document.getElementById('shopModal').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <h2 class="text-3xl font-extrabold text-[#0A112F] text-center mb-2">Recharger mon compte</h2>
        <p class="text-center text-gray-500 mb-8">Les points n'expirent jamais et sont valables pour tous vos séjours.</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="border rounded-xl p-6 text-center hover:border-blue-500 transition cursor-pointer" onclick="buyPoints(100)">
                <div class="text-lg font-bold text-gray-600">Pack Découverte</div>
                <div class="text-3xl font-black text-[#0A112F] my-2">10€</div>
                <div class="text-blue-600 font-bold bg-blue-50 rounded-full py-1 px-3 inline-block">100 Points</div>
            </div>
            <div class="border-2 border-blue-500 rounded-xl p-6 text-center transform scale-105 bg-white shadow-xl cursor-pointer relative" onclick="buyPoints(600)">
                <div class="absolute top-0 right-0 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-bl-lg">+20% OFFERT</div>
                <div class="text-lg font-bold text-blue-600">Pack Pro</div>
                <div class="text-4xl font-black text-[#0A112F] my-2">50€</div>
                <div class="text-white font-bold bg-blue-600 rounded-full py-1 px-3 inline-block">600 Points</div>
            </div>
            <div class="border rounded-xl p-6 text-center hover:border-purple-500 transition cursor-pointer" onclick="buyPoints(1500)">
                <div class="text-lg font-bold text-gray-600">Pack Agence</div>
                <div class="text-3xl font-black text-[#0A112F] my-2">100€</div>
                <div class="text-purple-600 font-bold bg-purple-50 rounded-full py-1 px-3 inline-block">1500 Points</div>
            </div>
        </div>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe('votre_cle_publique_stripe'); // TODO: Remplacer

    function openShopModal() {
        document.getElementById('shopModal').classList.remove('hidden');
    }

    // ACHAT DE POINTS (Stripe)
    function buyPoints(amount) {
        const btn = event.currentTarget;
        btn.innerHTML = "Chargement...";
        
        fetch('api/create_points_session.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ points: amount })
        })
        .then(res => res.json())
        .then(session => {
            if(session.error) alert(session.error);
            else stripe.redirectToCheckout({ sessionId: session.id });
        });
    }

    // APPLICATION DU BOOST (Consommation de points)
    function applyBoost(type, cost) {
        if(!confirm(`Confirmez-vous l'utilisation de ${cost} points pour ce boost ?`)) return;

        const currentSolde = parseInt(document.getElementById('displaySolde').innerText.replace(/\s/g, ''));
        if(currentSolde < cost) {
            alert("Solde insuffisant. Veuillez recharger votre compte.");
            openShopModal();
            return;
        }

        fetch('api/apply_boost.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ 
                token: '<?= $token ?>', 
                type: type 
            })
        })
        .then(res => res.json())
        .then(data => {
            const feedback = document.getElementById('feedbackMessage');
            feedback.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-green-100', 'text-green-700');
            
            if (data.success) {
                feedback.classList.add('bg-green-100', 'text-green-700');
                feedback.textContent = "🚀 Boost activé avec succès !";
                // Mettre à jour le solde visuellement
                document.getElementById('displaySolde').innerText = (currentSolde - cost);
                setTimeout(() => location.reload(), 1500);
            } else {
                feedback.classList.add('bg-red-100', 'text-red-700');
                feedback.textContent = data.message || "Erreur technique.";
            }
            feedback.classList.remove('hidden');
        })
        .catch(err => alert("Erreur réseau"));
    }
</script>

<?php require_once 'partials/footer.php'; ?>