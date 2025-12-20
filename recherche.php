<?php
// Fichier: /recherche.php
require_once 'partials/header.php';

$search_term = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : '';
$is_logged_in = isset($_SESSION['user']);
?>

<div class="bg-gray-50 min-h-screen">
    
    <div class="lg:hidden bg-white border-b px-4 py-3 sticky top-16 z-30 shadow-sm">
        <button id="mobile-filter-btn" class="w-full flex items-center justify-center px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-bold shadow hover:bg-blue-700 transition-colors">
            <i class="fa-solid fa-sliders mr-2"></i> Filtrer la recherche
        </button>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col lg:flex-row gap-8">

            <aside class="w-full lg:w-1/4 hidden lg:block h-fit sticky top-24" id="filters-sidebar">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
                        <h2 class="text-xl font-bold text-gray-900">Filtres</h2>
                        <button id="reset-filters" type="button" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Réinitialiser</button>
                    </div>

                    <form id="search-form" class="space-y-6">
                        <input type="hidden" name="name" value="<?php echo $search_term; ?>">
                        <input type="hidden" name="lat" id="user-lat">
                        <input type="hidden" name="lng" id="user-lng">

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Localisation</label>
                            <div class="space-y-3">
                                <div class="relative">
                                    <i class="fa-solid fa-map-pin absolute left-3 top-3 text-gray-400"></i>
                                    <input type="text" id="filter-city" name="city" placeholder="Ville ou code postal" class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>
                                <button type="button" id="btn-geo" class="w-full py-2 px-3 bg-blue-50 text-blue-700 rounded-lg text-sm font-semibold hover:bg-blue-100 transition-colors flex items-center justify-center gap-2 border border-blue-100">
                                    <i class="fa-solid fa-location-crosshairs"></i> Autour de moi
                                </button>
                                <div id="radius-container" class="hidden pt-2">
                                    <div class="flex justify-between text-xs font-semibold text-gray-500 mb-1">
                                        <span>Rayon</span>
                                        <span id="radius-value" class="text-blue-600">50km</span>
                                    </div>
                                    <input type="range" id="radius-range" name="radius" min="10" max="200" step="10" value="50" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-100">

                        <div>
                            <label class="flex justify-between text-sm font-bold text-gray-700 mb-2">
                                <span>Budget Max</span>
                                <span id="price-display" class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs font-bold">3000 €</span>
                            </label>
                            <input type="range" id="filter-price" name="max_price" min="100" max="3000" step="50" value="3000" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-green-600">
                        </div>

                        <hr class="border-gray-100">

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Âge de l'enfant</label>
                            <?php if ($is_logged_in): ?>
                                <select id="filter-child" name="age" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">-- Sélectionner un enfant --</option>
                                </select>
                            <?php else: ?>
                                <input type="number" name="age" placeholder="Ex: 12" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                            <?php endif; ?>
                        </div>

                        <hr class="border-gray-100">

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-3">Thématiques</label>
                            <div id="themes-list" class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar pr-2 border border-gray-100 rounded-lg p-2 bg-gray-50">
                                <div class="text-center py-2 text-gray-400 text-xs">Chargement...</div>
                            </div>
                        </div>

                        <div class="pt-4 sticky bottom-0 bg-white pb-1">
                            <button type="button" id="trigger-search-btn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center text-base">
                                <i class="fa-solid fa-magnifying-glass mr-2"></i> Voir les résultats
                            </button>
                        </div>
                    </form>
                </div>
            </aside>

            <main class="w-full lg:w-3/4">
                <div class="flex flex-col sm:flex-row justify-between items-end sm:items-center mb-6 gap-4">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 leading-tight">
                            <span id="default-title">Résultats de recherche</span>
                            <span id="dynamic-title" class="hidden">
                                Résultat pour : <span id="search-term-span" class="bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 bg-clip-text text-transparent"></span>
                            </span>
                        </h1>
                        <p class="text-gray-500 text-sm mt-1" id="results-count">Lancer la recherche pour voir les camps</p>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500 hidden sm:inline">Trier :</span>
                        <select id="sort-order" class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg p-2.5 focus:ring-blue-500 shadow-sm outline-none">
                            <option value="relevance">Pertinence</option>
                            <option value="price_asc">Prix croissant</option>
                            <option value="price_desc">Prix décroissant</option>
                            <option value="date">Date de départ</option>
                        </select>
                    </div>
                </div>

                <div id="fallback-msg" class="hidden mb-6 p-4 bg-orange-50 border-l-4 border-orange-500 text-orange-800 rounded-r-lg shadow-sm">
                    <div class="flex items-start">
                        <i class="fa-solid fa-triangle-exclamation mt-1 mr-3 text-lg"></i>
                        <div>
                            <strong class="block font-bold">Aucun camp ne correspond exactement à vos critères.</strong>
                            <p class="text-sm mt-1">Cependant, voici notre sélection de séjours recommandés qui pourraient vous plaire :</p>
                        </div>
                    </div>
                </div>

                <div id="results-container" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6"></div>
                
                <div id="loader" class="hidden py-20 flex flex-col items-center justify-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-4 border-gray-200 border-t-blue-600 mb-4"></div>
                    <p class="text-gray-400 font-medium">Recherche en cours...</p>
                </div>
            </main>
        </div>
    </div>

    <div id="mobile-filter-overlay" class="fixed inset-0 bg-black/50 z-50 hidden transition-opacity opacity-0 backdrop-blur-sm"></div>
    <div id="mobile-filter-drawer" class="fixed inset-y-0 left-0 w-full sm:w-[400px] bg-white z-50 transform -translate-x-full transition-transform duration-300 shadow-2xl flex flex-col">
        <div class="p-4 border-b flex justify-between items-center bg-gray-50">
            <h2 class="font-bold text-lg text-gray-800">Filtrer</h2>
            <button id="close-mobile-filters" class="p-2 text-gray-500 hover:bg-gray-200 rounded-full transition-colors"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <div class="flex-grow overflow-y-auto p-5" id="mobile-filters-content"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // --- VARIABLES ---
    const form = document.getElementById('search-form');
    const container = document.getElementById('results-container');
    const loader = document.getElementById('loader');
    const fallbackMsg = document.getElementById('fallback-msg');
    const resultsCount = document.getElementById('results-count');
    const searchBtn = document.getElementById('trigger-search-btn');
    const sortSelect = document.getElementById('sort-order');
    const isLoggedIn = <?php echo json_encode($is_logged_in); ?>;

    // --- INTERFACE (Sliders, Geo...) ---
    const priceRange = document.getElementById('filter-price');
    if(priceRange) priceRange.addEventListener('input', function() { document.getElementById('price-display').textContent = this.value + ' €'; });
    
    const radiusRange = document.getElementById('radius-range');
    if(radiusRange) radiusRange.addEventListener('input', function() { document.getElementById('radius-value').textContent = this.value + 'km'; });

    const btnGeo = document.getElementById('btn-geo');
    if(btnGeo) {
        btnGeo.addEventListener('click', () => {
            if (!navigator.geolocation) return alert("Non supporté");
            btnGeo.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>...';
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    document.getElementById('user-lat').value = pos.coords.latitude;
                    document.getElementById('user-lng').value = pos.coords.longitude;
                    document.getElementById('radius-container').classList.remove('hidden');
                    btnGeo.innerHTML = '<i class="fa-solid fa-check"></i> Position OK';
                    btnGeo.classList.replace('bg-blue-50', 'bg-green-50');
                    document.getElementById('filter-city').value = "Ma position actuelle";
                },
                (err) => { btnGeo.innerHTML = 'Erreur'; }
            );
        });
    }

    // --- CHARGEMENT DONNÉES (Thèmes, Enfants) ---
    fetch('api/get_themes.php').then(r=>r.json()).then(d=>{
        const l=document.getElementById('themes-list'); l.innerHTML='';
        d.forEach(t=>{ l.innerHTML+=`<div class="flex items-center gap-2 p-1 hover:bg-white rounded"><input type="checkbox" name="themes[]" value="${t.id}" id="t-${t.id}" class="w-4 h-4 cursor-pointer"><label for="t-${t.id}" class="text-sm flex-1 cursor-pointer select-none">${t.name}</label></div>`; });
    }).catch(e=>console.error(e));

    if(isLoggedIn){
        fetch('api/get_children.php').then(r=>r.json()).then(d=>{
            const s=document.getElementById('filter-child');
            d.forEach(c=>{ s.innerHTML+=`<option value="${c.age}">${c.prenom} (${c.age} ans)</option>`; });
        });
    }

    // --- GESTION HISTORIQUE ---
    function updateHistoryWithCount(term, count) {
        if(!term || term.trim() === '') return;
        const key = 'colomap_search_history';
        let history = [];
        try { history = JSON.parse(localStorage.getItem(key)) || []; } catch(e){}

        if (count > 0) {
            // Mise à jour du nombre de résultats pour ce terme
            const idx = history.findIndex(i => i.term.toLowerCase() === term.toLowerCase());
            if(idx !== -1) {
                history[idx].count = count;
            } else {
                // Si pas trouvé (accès direct sans header submit), on ajoute
                history.unshift({ term: term, count: count, date: Date.now() });
                if(history.length > 6) history = history.slice(0, 6);
            }
            localStorage.setItem(key, JSON.stringify(history));
        } else {
            // Si 0 résultat, on retire l'entrée de l'historique (comme demandé)
            // Le header l'a peut-être ajouté au submit, donc on le nettoie ici
            history = history.filter(i => i.term.toLowerCase() !== term.toLowerCase());
            localStorage.setItem(key, JSON.stringify(history));
        }
    }

    // --- FONCTION DE RECHERCHE ---
    async function performSearch() {
        loader.classList.remove('hidden');
        container.innerHTML = '';
        fallbackMsg.classList.add('hidden');
        resultsCount.textContent = "Recherche en cours...";

        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        params.append('sort', sortSelect.value);
        
        // Titre dynamique
        const term = formData.get('name');
        if(term && term.trim()) {
            document.getElementById('default-title').classList.add('hidden');
            document.getElementById('dynamic-title').classList.remove('hidden');
            document.getElementById('search-term-span').textContent = term;
        } else {
            document.getElementById('default-title').classList.remove('hidden');
            document.getElementById('dynamic-title').classList.add('hidden');
        }

        try {
            const res = await fetch(`api/get_camps_recherche.php?${params.toString()}`);
            
            let responseData;
            try { responseData = await res.json(); } catch(e) { throw new Error("Réponse serveur invalide."); }

            if (!res.ok || responseData.status === 'error') {
                throw new Error(responseData.message || `Erreur HTTP ${res.status}`);
            }

            const camps = responseData.camps || [];
            const isFallback = responseData.is_fallback || false;

            loader.classList.add('hidden');
            
            // --- MISE A JOUR HISTORIQUE ---
            if(term && term.trim()) {
                // Si fallback activé = 0 résultat exact trouvé
                const exactCount = isFallback ? 0 : camps.length;
                updateHistoryWithCount(term.trim(), exactCount);
            }

            if (isFallback) {
                fallbackMsg.classList.remove('hidden');
                resultsCount.textContent = "0 résultat exact - Suggestions affichées";
            } else {
                resultsCount.textContent = `${camps.length} résultat(s) trouvé(s)`;
            }

            if (camps.length === 0 && !isFallback) {
                container.innerHTML = '<div class="col-span-full text-center text-gray-500 py-10 text-lg">Aucun séjour disponible pour le moment.<br><small>Essayez de modifier vos filtres.</small></div>';
            } else {
                camps.forEach(camp => {
                    container.insertAdjacentHTML('beforeend', buildCard(camp));
                });
            }

        } catch (error) {
            console.error(error);
            loader.classList.add('hidden');
            resultsCount.textContent = "Erreur.";
            container.innerHTML = `<div class="col-span-full p-6 bg-red-50 text-red-700 rounded-xl border border-red-200 text-center">
                <i class="fa-solid fa-bug text-2xl mb-2"></i><br>
                <strong>Oups ! Une erreur est survenue.</strong><br>
                ${error.message}
            </div>`;
        }
    }

    function buildCard(camp) {
        const img = camp.image_url || 'assets/img/default-camp.jpg';
        const prix = parseFloat(camp.prix).toFixed(0);
        
        let dateStr = "Dates à confirmer";
        if(camp.date_debut) {
            const d = new Date(camp.date_debut);
            dateStr = d.toLocaleDateString('fr-FR', {day: 'numeric', month: 'short'});
        }

        // Badges
        let badgesHtml = '';
        if (camp.is_vedette) badgesHtml += `<span class="bg-yellow-400 text-white text-xs px-2 py-1 rounded shadow-sm font-bold flex items-center mr-1"><i class="fa-solid fa-star mr-1"></i> TOP</span>`;
        if (camp.is_urgence) badgesHtml += `<span class="bg-red-500 text-white text-xs px-2 py-1 rounded shadow-sm font-bold animate-pulse mr-1">Dernières places</span>`;
        
        return `
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all group flex flex-col h-full fade-in-anim relative">
            <a href="camp_details.php?t=${camp.token}" class="block relative h-48 overflow-hidden bg-gray-100">
                <img src="${img}" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500" loading="lazy">
                <div class="absolute top-3 left-3 flex flex-wrap gap-1">
                    ${badgesHtml}
                    <span class="bg-white/90 backdrop-blur px-2 py-1 rounded text-xs font-bold text-gray-700 shadow">${camp.age_min}-${camp.age_max} ans</span>
                </div>
            </a>
            <div class="p-5 flex flex-col flex-grow">
                <h3 class="font-bold text-gray-900 text-lg mb-1 line-clamp-1">
                    <a href="camp_details.php?t=${camp.token}" class="hover:text-blue-600 transition">${camp.nom}</a>
                </h3>
                <p class="text-sm text-gray-500 mb-3 flex items-center"><i class="fa-solid fa-location-dot mr-2"></i> ${camp.ville}</p>
                
                <div class="mt-auto flex justify-between items-center pt-4 border-t border-gray-50">
                    <div>
                        <span class="text-xl font-bold text-blue-600">${prix}€</span>
                        <span class="text-xs text-gray-400 block">${dateStr}</span>
                    </div>
                    <a href="camp_details.php?t=${camp.token}" class="text-sm font-medium bg-gray-100 hover:bg-blue-600 hover:text-white px-4 py-2 rounded-lg transition-colors">Voir</a>
                </div>
            </div>
        </div>`;
    }

    searchBtn.addEventListener('click', performSearch);
    sortSelect.addEventListener('change', performSearch);
    
    document.getElementById('reset-filters').addEventListener('click', () => {
        form.reset();
        document.getElementById('price-display').textContent = '3000 €';
        if(document.getElementById('radius-container')) document.getElementById('radius-container').classList.add('hidden');
        if(document.getElementById('filter-city')) document.getElementById('filter-city').value = '';
        performSearch();
    });

    // Mobile Drawer Logic
    const mobileBtn = document.getElementById('mobile-filter-btn');
    const drawer = document.getElementById('mobile-filter-drawer');
    const overlay = document.getElementById('mobile-filter-overlay');
    const closeBtn = document.getElementById('close-mobile-filters');
    const sbContent = document.querySelector('#filters-sidebar .bg-white');

    function toggleDrawer(show) {
        if(show) {
            document.getElementById('mobile-filters-content').appendChild(form);
            drawer.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            setTimeout(()=>overlay.classList.remove('opacity-0'), 10);
            document.body.style.overflow = 'hidden';
        } else {
            drawer.classList.add('-translate-x-full');
            overlay.classList.add('opacity-0');
            setTimeout(() => {
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
                sbContent.appendChild(form);
            }, 300);
        }
    }

    if(mobileBtn) mobileBtn.addEventListener('click', () => toggleDrawer(true));
    if(closeBtn) closeBtn.addEventListener('click', () => toggleDrawer(false));
    if(overlay) overlay.addEventListener('click', () => toggleDrawer(false));

    // Lancer la recherche au chargement
    performSearch();
});
</script>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.fade-in-anim { animation: fadeIn 0.5s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<?php require_once 'partials/footer.php'; ?>