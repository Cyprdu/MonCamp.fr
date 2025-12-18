<?php
// admin_commission_stats.php

// 1. CONFIG
require_once 'api/config.php';

// 2. SÉCURITÉ - STRICT ADMIN CHECK
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_admin'])) {
    header('Location: alerte.html'); // Redirection sécurisée
    exit;
}

// 3. LOGIQUE
$error = null;
$filtre = filter_input(INPUT_GET, 'filtre', FILTER_SANITIZE_STRING) ?? 'annee';

// Périodes de filtre
switch ($filtre) {
    case 'aujourdhui':
        $date_min = date('Y-m-d 00:00:00');
        $date_max = date('Y-m-d 23:59:59');
        $titre_filtre = "Aujourd'hui";
        break;
    case 'mois':
        $date_min = date('Y-m-01 00:00:00');
        $date_max = date('Y-m-d 23:59:59');
        $titre_filtre = "Ce mois-ci";
        break;
    case 'annee':
    default:
        $date_min = date('Y-01-01 00:00:00');
        $date_max = date('Y-m-d 23:59:59');
        $titre_filtre = "Cette année";
        break;
    case 'total':
        $date_min = '2000-01-01 00:00:00'; 
        $date_max = date('Y-m-d H:i:s');
        $titre_filtre = "Total depuis le début";
        break;
}

try {
    // A. Calcul des totaux pour la période
    
    // Commission Totale (Montant Brut - Montant Net)
    $sqlTotalCommission = "
        SELECT SUM(montant_total - montant_apres_commission) 
        FROM virements 
        WHERE date_demande >= ? AND date_demande <= ?
    ";
    $stmtTotal = $pdo->prepare($sqlTotalCommission);
    $stmtTotal->execute([$date_min, $date_max]);
    $totalCommission = $stmtTotal->fetchColumn() ?? 0.00;
    
    // Montant Total Brut Viré
    $sqlTotalVire = "
        SELECT SUM(montant_total) 
        FROM virements 
        WHERE date_demande >= ? AND date_demande <= ?
    ";
    $stmtTotalVire = $pdo->prepare($sqlTotalVire);
    $stmtTotalVire->execute([$date_min, $date_max]);
    $totalVire = $stmtTotalVire->fetchColumn() ?? 0.00;

    // Nombre de demandes traitées dans la période
    $sqlCountDemandes = "
        SELECT COUNT(*) 
        FROM virements 
        WHERE date_demande >= ? AND date_demande <= ?
    ";
    $stmtCount = $pdo->prepare($sqlCountDemandes);
    $stmtCount->execute([$date_min, $date_max]);
    $countDemandes = $stmtCount->fetchColumn();


    // D. Récupération des données pour le graphique mensuel (si le filtre n'est pas "Aujourd'hui" ou "Total")
    $chart_labels = [];
    $chart_data_commission = [];
    $show_chart = in_array($filtre, ['mois', 'annee']); // Afficher le graphique pour mois/année

    if ($show_chart) {
        $sqlMonthlyData = "
            SELECT 
                DATE_FORMAT(date_demande, '%Y-%m') AS month_year,
                SUM(montant_total - montant_apres_commission) AS total_commission
            FROM virements 
            WHERE date_demande >= ? AND date_demande <= ?
            GROUP BY month_year
            ORDER BY month_year ASC
        ";
        $stmtMonthly = $pdo->prepare($sqlMonthlyData);
        $stmtMonthly->execute([$date_min, $date_max]);
        $monthlyData = $stmtMonthly->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($monthlyData as $row) {
            // Formatage des étiquettes (Ex: Jan 2025)
            $chart_labels[] = date('M Y', strtotime($row['month_year'])); 
            $chart_data_commission[] = floatval($row['total_commission']);
        }
    }
    
    // Encodage en JSON pour le JavaScript
    $chart_labels_json = json_encode($chart_labels);
    $chart_data_commission_json = json_encode($chart_data_commission);

} catch (Exception $e) {
    $error = "Erreur SQL : " . $e->getMessage();
}

// 4. AFFICHAGE HTML
require_once 'partials/header.php';
?>

<title>Admin - Statistiques des Commissions</title>

<div class="min-h-screen bg-gray-50 py-10 font-sans">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex items-center gap-4 mb-8">
            <a href="admin_demande_virement" class="text-gray-500 hover:text-[#0A112F] transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <h1 class="text-3xl font-extrabold text-[#0A112F]">Statistiques des Commissions</h1>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Erreur</p>
                <p><?= $error ?></p>
            </div>
        <?php endif; ?>

        <div class="mb-8 p-4 bg-white rounded-xl shadow-md border border-gray-200">
            <h2 class="text-lg font-semibold text-gray-700 mb-3">Filtrer les données :</h2>
            <div class="flex flex-wrap gap-3">
                <?php 
                    $filtres = ['aujourdhui' => "Aujourd'hui", 'mois' => 'Ce mois-ci', 'annee' => 'Cette année', 'total' => 'Total'];
                    foreach ($filtres as $key => $label):
                ?>
                <a href="?filtre=<?= $key ?>" 
                   class="py-2 px-4 rounded-lg text-sm font-medium transition 
                          <?= ($filtre === $key) ? 'bg-[#0A112F] text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                    <?= $label ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <h2 class="text-2xl font-bold text-[#0A112F] mb-6 border-b pb-2">Résultats pour : <?= $titre_filtre ?></h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-600">
                <p class="text-sm font-medium text-gray-500">Commissions Collectées</p>
                <p class="text-3xl font-extrabold text-green-700 mt-1">
                    <?= number_format($totalCommission, 2, ',', ' ') ?>€
                </p>
                <p class="text-xs text-gray-400 mt-2">Revenu brut de la plateforme</p>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-600">
                <p class="text-sm font-medium text-gray-500">Montant BRUT Géré</p>
                <p class="text-3xl font-extrabold text-blue-700 mt-1">
                    <?= number_format($totalVire, 2, ',', ' ') ?>€
                </p>
                <p class="text-xs text-gray-400 mt-2">Total des fonds transitant</p>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-indigo-600">
                <p class="text-sm font-medium text-gray-500">Nombre de Demandes</p>
                <p class="text-3xl font-extrabold text-indigo-700 mt-1">
                    <?= $countDemandes ?>
                </p>
                <p class="text-xs text-gray-400 mt-2">Demandes de virement initiées</p>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-[#0A112F] mb-6 border-b pb-2">Analyse Détaillée</h2>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <div class="bg-white rounded-xl shadow-md p-6 lg:col-span-1">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Évolution des Commissions (<?= $titre_filtre ?>)</h3>
                <?php if ($show_chart && !empty($chart_labels)): ?>
                    <div class="h-80">
                        <canvas id="commissionChart"></canvas>
                    </div>
                <?php else: ?>
                    <div class="h-64 bg-gray-100 rounded-lg flex items-center justify-center text-gray-500">
                        Données non disponibles pour le graphique avec ce filtre.
                    </div>
                <?php endif; ?>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Analyses Complémentaires</h3>
                <div class="h-64 bg-gray-100 rounded-lg flex items-center justify-center text-gray-500">
                    [Placeholder pour autres analyses : Top 5 organismes, répartition statut, etc.]
                </div>
            </div>

        </div>

    </div>
</div>

<?php require_once 'partials/footer.php'; ?>

<?php if ($show_chart && !empty($chart_labels)): ?>
<script>
    // Assurez-vous que la librairie Chart.js est chargée dans votre header/footer.
    const labels = <?= $chart_labels_json ?>;
    const dataCommissions = <?= $chart_data_commission_json ?>;

    const data = {
        labels: labels,
        datasets: [{
            label: 'Commissions Collectées (€)',
            data: dataCommissions,
            fill: true,
            backgroundColor: 'rgba(10, 17, 47, 0.1)', // #0A112F avec transparence
            borderColor: '#0A112F', // Couleur principale
            tension: 0.3,
            pointBackgroundColor: '#0A112F',
            pointRadius: 4
        }]
    };

    const config = {
        type: 'line',
        data: data,
        options: {
            maintainAspectRatio: false,
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Montant (€)'
                    }
                }
            }
        }
    };

    new Chart(
        document.getElementById('commissionChart'),
        config
    );
</script>
<?php endif; ?>