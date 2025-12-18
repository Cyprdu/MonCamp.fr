<?php
require_once 'partials/header.php';

// Sécurité basique PHP (la vraie sécurité est dans l'API)
if (!isset($_SESSION['user']) || !$_SESSION['user']['is_directeur']) {
    header('Location: index.php');
    exit;
}

// On récupère le token
$token = $_GET['t'] ?? '';
if (empty($token)) {
    header('Location: mes_camps.php');
    exit;
}
?>

<title>Dashboard Gestion - ColoMap</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<main class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
    
    <div id="page-loader" class="fixed inset-0 bg-white z-50 flex items-center justify-center">
        <div class="text-center">
            <div class="loader inline-block mb-4"></div>
            <p class="text-gray-500">Vérification des droits d'accès...</p>
        </div>
    </div>

    <div id="dashboard-content" class="hidden">
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 border-b pb-4">
            <div>
                <a href="mes_camps.php" class="text-gray-500 hover:text-gray-800 text-sm mb-2 inline-block">&larr; Retour à mes camps</a>
                <h1 class="text-3xl font-extrabold text-gray-900" id="camp-title"></h1>
                <p class="text-gray-500" id="camp-dates"></p>
            </div>
            <div class="mt-4 md:mt-0 flex gap-3">
                <button onclick="exportCSV()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Exporter CSV
                </button>
                <a id="btn-edit-camp" href="#" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow">Modifier</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow border-l-4 border-blue-500">
                <p class="text-gray-500 text-sm font-bold uppercase">Chiffre d'Affaires</p>
                <p class="text-3xl font-bold text-gray-800 mt-2" id="kpi-ca">0 €</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow border-l-4 border-green-500">
                <p class="text-gray-500 text-sm font-bold uppercase">Remplissage</p>
                <div class="flex items-end gap-2">
                    <p class="text-3xl font-bold text-gray-800 mt-2" id="kpi-inscrits">0</p>
                    <span class="text-gray-500 mb-1" id="kpi-quota">/ 0</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                    <div id="progress-bar" class="bg-green-600 h-2.5 rounded-full" style="width: 0%"></div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow border-l-4 border-purple-500">
                <p class="text-gray-500 text-sm font-bold uppercase">Âge Moyen</p>
                <p class="text-3xl font-bold text-gray-800 mt-2" id="kpi-age">0 ans</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow border-l-4 border-pink-500">
                <p class="text-gray-500 text-sm font-bold uppercase">Parité</p>
                <div class="flex justify-between mt-2 text-sm">
                    <span class="text-pink-600">F: <span id="kpi-filles">0</span></span>
                    <span class="text-blue-600">G: <span id="kpi-garcons">0</span></span>
                </div>
                <div class="flex w-full h-2.5 bg-gray-200 rounded-full mt-2 overflow-hidden">
                    <div id="bar-filles" class="bg-pink-500 h-2.5" style="width: 50%"></div>
                    <div id="bar-garcons" class="bg-blue-500 h-2.5" style="width: 50%"></div>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow border mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Évolution des inscriptions</h3>
            <div class="relative h-64">
                <canvas id="inscriptionChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border overflow-hidden">
            <div class="p-6 border-b bg-gray-50 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">Détails des inscrits</h3>
                <input type="text" id="searchTable" placeholder="Rechercher..." class="border rounded px-3 py-1 text-sm">
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Enfant</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Parent (Contact)</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Infos Santé / Régime</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Statut</th>
                        </tr>
                    </thead>
                    <tbody id="inscrits-body" class="bg-white divide-y divide-gray-200"></tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
let globalDataInscrits = [];

document.addEventListener('DOMContentLoaded', async function() {
    const token = '<?= htmlspecialchars($token) ?>';
    
    try {
        // APPEL API SECURISE PAR TOKEN
        const response = await fetch(`api/get_camp_stats.php?t=${token}`);
        
        // GESTION DES REDIRECTIONS DE SECURITE
        if (response.status === 403 || response.status === 404) {
            alert("Accès refusé : Vous n'êtes pas le propriétaire de ce camp ou le lien est invalide.");
            window.location.href = 'index.php'; // Redirection forcée
            return;
        }

        const data = await response.json();
        if(data.error) throw new Error(data.error);

        globalDataInscrits = data.liste_inscrits;

        // Affichage des données
        document.getElementById('camp-title').textContent = "Gestion : " + data.camp.nom;
        document.getElementById('camp-dates').textContent = data.camp.dates;
        document.getElementById('btn-edit-camp').href = `edit_camp.php?id=${data.camp.id}`;

        // KPIs
        document.getElementById('kpi-ca').textContent = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(data.kpi.chiffre_affaires);
        document.getElementById('kpi-inscrits').textContent = data.kpi.nombre_inscrits;
        document.getElementById('kpi-quota').textContent = "/ " + data.camp.quota;
        document.getElementById('progress-bar').style.width = data.kpi.taux_remplissage + "%";
        document.getElementById('kpi-age').textContent = data.kpi.age_moyen + " ans";
        
        const f = data.kpi.parite.filles; const g = data.kpi.parite.garcons; const t = f + g;
        document.getElementById('kpi-filles').textContent = f;
        document.getElementById('kpi-garcons').textContent = g;
        if(t > 0) {
            document.getElementById('bar-filles').style.width = (f/t*100) + "%";
            document.getElementById('bar-garcons').style.width = (g/t*100) + "%";
        }

        // Graphique
        new Chart(document.getElementById('inscriptionChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: data.chart.labels,
                datasets: [{
                    label: 'Inscriptions',
                    data: data.chart.data,
                    borderColor: 'rgb(37, 99, 235)',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        renderTable(data.liste_inscrits);

        // Recherche
        document.getElementById('searchTable').addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            renderTable(globalDataInscrits.filter(i => 
                i.nom.toLowerCase().includes(term) || 
                i.prenom.toLowerCase().includes(term) ||
                i.parent_nom_famille.toLowerCase().includes(term)
            ));
        });

        // Tout est chargé, on cache le loader et on montre le contenu
        document.getElementById('page-loader').classList.add('hidden');
        document.getElementById('dashboard-content').classList.remove('hidden');

    } catch (e) {
        document.getElementById('page-loader').innerHTML = `<div class='text-red-600 font-bold'>Erreur : ${e.message}</div>`;
    }
});

function renderTable(list) {
    const tbody = document.getElementById('inscrits-body');
    tbody.innerHTML = '';
    
    if(list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Aucun inscrit.</td></tr>';
        return;
    }

    list.forEach(i => {
        const age = new Date().getFullYear() - new Date(i.date_naissance).getFullYear();
        // On récupère le token du camp depuis l'URL actuelle pour le passer au lien
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('t');

        tbody.innerHTML += `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                    <div class="text-sm font-bold text-gray-900">${i.prenom} ${i.nom}</div>
                    <div class="text-xs text-gray-500">${age} ans - ${i.sexe}</div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-900">${i.parent_prenom} ${i.parent_nom_famille}</div>
                    <div class="text-xs text-blue-600"><a href="mailto:${i.parent_email}">${i.parent_email}</a></div>
                    <div class="text-xs text-gray-500">${i.parent_tel}</div>
                </td>
                <td class="px-6 py-4">
                    ${i.infos_sante ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">⚠️ Médical</span>' : '<span class="text-gray-400 text-xs">RAS</span>'}
                </td>
                <td class="px-6 py-4">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">${i.statut_paiement}</span>
                </td>
                <td class="px-6 py-4 text-right">
                    <a href="child_details.php?id=${i.inscription_id}&t=${token}" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-3 rounded shadow transition-colors flex items-center justify-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        Voir dossier
                    </a>
                </td>
            </tr>`;
    });
}

function exportCSV() {
    if(globalDataInscrits.length === 0) return alert("Rien à exporter.");
    let csv = "ID;Date;Prenom Enfant;Nom Enfant;Sexe;Date Naissance;Infos Sante;Regime;Parent Nom;Parent Email;Parent Tel;Tarif;Montant;Statut\n";
    
    globalDataInscrits.forEach(r => {
        csv += [
            r.inscription_id, r.date_inscription, r.prenom, r.nom, r.sexe, r.date_naissance,
            `"${(r.infos_sante||'').replace(/"/g,'""')}"`, `"${(r.regime_alimentaire||'').replace(/"/g,'""')}"`,
            r.parent_nom_famille, r.parent_email, r.parent_tel, r.tarif_nom, r.montant_paye, r.statut_paiement
        ].join(";") + "\r\n";
    });

    const link = document.createElement("a");
    link.href = "data:text/csv;charset=utf-8," + encodeURI(csv);
    link.download = "inscrits.csv";
    link.click();
}
</script>

<?php require_once 'partials/footer.php'; ?>