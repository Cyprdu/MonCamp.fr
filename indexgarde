<?php 
require_once 'partials/header.php'; 

$is_logged_in = isset($_SESSION['user']);
$is_animateur = $is_logged_in && ($_SESSION['user']['is_animateur'] ?? false);
$user_favorites = $_SESSION['user']['favorites'] ?? [];

// Liste des départements français pour le menu déroulant
$departments = [
    '01' => 'Ain', '02' => 'Aisne', '03' => 'Allier', '04' => 'Alpes-de-Haute-Provence', '05' => 'Hautes-Alpes',
    '06' => 'Alpes-Maritimes', '07' => 'Ardèche', '08' => 'Ardennes', '09' => 'Ariège', '10' => 'Aube',
    '11' => 'Aude', '12' => 'Aveyron', '13' => 'Bouches-du-Rhône', '14' => 'Calvados', '15' => 'Cantal',
    '16' => 'Charente', '17' => 'Charente-Maritime', '18' => 'Cher', '19' => 'Corrèze', '2A' => 'Corse-du-Sud',
    '2B' => 'Haute-Corse', '21' => 'Côte-d\'Or', '22' => 'Côtes-d\'Armor', '23' => 'Creuse', '24' => 'Dordogne',
    '25' => 'Doubs', '26' => 'Drôme', '27' => 'Eure', '28' => 'Eure-et-Loir', '29' => 'Finistère', '30' => 'Gard',
    '31' => 'Haute-Garonne', '32' => 'Gers', '33' => 'Gironde', '34' => 'Hérault', '35' => 'Ille-et-Vilaine',
    '36' => 'Indre', '37' => 'Indre-et-Loire', '38' => 'Isère', '39' => 'Jura', '40' => 'Landes',
    '41' => 'Loir-et-Cher', '42' => 'Loire', '43' => 'Haute-Loire', '44' => 'Loire-Atlantique', '45' => 'Loiret',
    '46' => 'Lot', '47' => 'Lot-et-Garonne', '48' => 'Lozère', '49' => 'Maine-et-Loire', '50' => 'Manche',
    '51' => 'Marne', '52' => 'Haute-Marne', '53' => 'Mayenne', '54' => 'Meurthe-et-Moselle', '55' => 'Meuse',
    '56' => 'Morbihan', '57' => 'Moselle', '58' => 'Nièvre', '59' => 'Nord', '60' => 'Oise', '61' => 'Orne',
    '62' => 'Pas-de-Calais', '63' => 'Puy-de-Dôme', '64' => 'Pyrénées-Atlantiques', '65' => 'Hautes-Pyrénées',
    '66' => 'Pyrénées-Orientales', '67' => 'Bas-Rhin', '68' => 'Haut-Rhin', '69' => 'Rhône', '70' => 'Haute-Saône',
    '71' => 'Saône-et-Loire', '72' => 'Sarthe', '73' => 'Savoie', '74' => 'Haute-Savoie', '75' => 'Paris',
    '76' => 'Seine-Maritime', '77' => 'Seine-et-Marne', '78' => 'Yvelines', '79' => 'Deux-Sèvres', '80' => 'Somme',
    '81' => 'Tarn', '82' => 'Tarn-et-Garonne', '83' => 'Var', '84' => 'Vaucluse', '85' => 'Vendée', '86' => 'Vienne',
    '87' => 'Haute-Vienne', '88' => 'Vosges', '89' => 'Yonne', '90' => 'Territoire de Belfort', '91' => 'Essonne',
    '92' => 'Hauts-de-Seine', '93' => 'Seine-Saint-Denis', '94' => 'Val-de-Marne', '95' => 'Val-d\'Oise',
    '971' => 'Guadeloupe', '972' => 'Martinique', '973' => 'Guyane', '974' => 'La Réunion', '976' => 'Mayotte'
];
?>

<?php if (!$is_animateur): ?>
<div id="filter-bar" class="hidden sticky top-[64px] z-30 bg-white/95 backdrop-blur-sm shadow-md transition-all duration-300">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
        <form id="advanced-search-form" class="py-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <select id="filter-department" name="department" class="p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-gray-700">
                    <option value="">Tous les départements</option>
                    <?php foreach ($departments as $code => $name): ?>
                        <option value="<?php echo $code; ?>"><?php echo $code; ?> - <?php echo $name; ?></option>
                    <?php endforeach; ?>
                </select>
                
                <?php if ($is_logged_in): ?>
                    <select id="filter-child" name="age" class="p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-gray-500">
                        <option value="">Pour quel enfant ?</option>
                    </select>
                <?php else: ?>
                    <input type="number" id="filter-age" name="age" placeholder="Âge de l'enfant" class="p-3 border border-gray-300 rounded-lg">
                <?php endif; ?>

                <button type="submit" class="w-full bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition-colors">Rechercher</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
    
    <section class="text-center my-12">
        <?php if ($is_logged_in): ?>
            <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight text-gray-900">
                Bonjour <span class="bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 bg-clip-text text-transparent"><?php echo htmlspecialchars($_SESSION['user']['prenom']); ?></span>
            </h1>
            <p class="mt-4 max-w-2xl mx-auto text-lg text-gray-500">
                <?php echo $is_animateur ? "Prêt à trouver votre prochaine mission d'animation ?" : "Prêt à trouver une nouvelle aventure pour vos enfants ?"; ?>
            </p>
        <?php else: ?>
            <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight text-gray-900">
                Bienvenu sur <span class="bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 bg-clip-text text-transparent">ColoMap</span>
            </h1>
            <p class="mt-4 max-w-2xl mx-auto text-lg text-gray-500">
                Explorez des centaines de camps partout en France. L'aventure inoubliable de votre enfant commence ici.
            </p>
        <?php endif; ?>
    </section>

    <section class="mb-8">
        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
            </div>
            <input type="text" id="name-search-input" placeholder="Rechercher un camp par nom..." class="block w-full rounded-lg border-gray-300 p-3 pl-10 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
    </section>

    <section class="mt-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold"><?php echo $is_animateur ? "Camps qui recrutent" : "Tous les camps disponibles"; ?></h2>
            <?php if (!$is_animateur): ?>
            <button id="toggle-filters-button" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-100 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 0 1 .628.74v2.288a2.25 2.25 0 0 1-.659 1.59l-4.682 4.683a2.25 2.25 0 0 0-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 0 1 8 18.25v-5.757a2.25 2.25 0 0 0-.659-1.59L2.659 6.22A2.25 2.25 0 0 1 2 4.629V2.34a.75.75 0 0 1 .628-.74Z" clip-rule="evenodd" /></svg>
                <span>Filtres avancés</span>
            </button>
            <?php endif; ?>
        </div>
        
        <div id="loader" class="flex justify-center items-center h-64"><div class="loader"></div></div>
        <div id="camps-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"></div>
    </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isAnimator = <?php echo json_encode($is_animateur); ?>;
    const isLoggedIn = <?php echo json_encode($is_logged_in); ?>;
    const campsListContainer = document.getElementById('camps-list');
    const loader = document.getElementById('loader');
    const nameSearchInput = document.getElementById('name-search-input');
    let userFavorites = <?php echo json_encode($user_favorites); ?>;

    const toggleFiltersButton = document.getElementById('toggle-filters-button');
    const filterBar = document.getElementById('filter-bar');
    const searchForm = document.getElementById('advanced-search-form');
    const childSelect = document.getElementById('filter-child');

    if (!isAnimator) {
        async function loadChildren() {
            if (!childSelect) return;
            try {
                const response = await fetch('api/get_children.php');
                if (!response.ok) return;
                const children = await response.json();
                if (children.length > 0) {
                    children.forEach(child => {
                        const option = document.createElement('option');
                        option.value = child.age;
                        option.textContent = `${child.prenom} (${child.age} ans)`;
                        childSelect.appendChild(option);
                    });
                } else {
                    childSelect.innerHTML = '<option value="">Aucun enfant enregistré</option>';
                    childSelect.disabled = true;
                }
            } catch (error) { console.error("Erreur chargement enfants:", error); }
        }
        loadChildren();
        if(toggleFiltersButton) toggleFiltersButton.addEventListener('click', () => filterBar.classList.toggle('hidden'));
        if(searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const params = Object.fromEntries(formData.entries());
                params.name = nameSearchInput.value.trim();
                fetchAndDisplayCamps(params);
            });
        }
    }

    const debounce = (func, timeout = 300) => {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => { func.apply(this, args); }, timeout);
        };
    };

    async function fetchAndDisplayCamps(params = {}) {
        loader.style.display = 'flex';
        campsListContainer.innerHTML = ''; 
        const apiUrl = isAnimator ? 'api/get_eligible_camps_for_animator.php' : 'api/get_camps.php';
        const query = new URLSearchParams(params).toString();
        
        try {
            const response = await fetch(`${apiUrl}?${query}`);
            if (!response.ok) {
                const errorText = await response.text();
                console.error("Server response:", errorText);
                throw new Error('Erreur réseau ou erreur PHP. Consultez la console.');
            }
            const camps = await response.json();
            loader.style.display = 'none';
            if (camps.error || camps.length === 0) {
                const message = isAnimator ? "Aucun camp ne correspond à votre profil." : "Aucun camp ne correspond à votre recherche.";
                campsListContainer.innerHTML = `<p class="text-gray-500 col-span-full text-center py-10">${message}</p>`;
                return;
            }
            renderCamps(camps);
        } catch (error) {
            loader.style.display = 'none';
            campsListContainer.innerHTML = `<p class="text-red-500 col-span-full text-center py-10">${error.message}</p>`;
        }
    }
    
    function renderCamps(camps) {
        let newContent = '';
        camps.forEach(camp => {
            const isFavorited = !isAnimator && userFavorites.includes(camp.id);
            const detailPage = isAnimator ? 'info-camp-animateur.php' : 'camp_details.php';
            
            // MODIFICATION: Construction du lien sécurisé
            // Si c'est un animateur, on garde l'ID (ou on adapte si la page animateur change)
            // Si c'est public, on utilise le token (t=...)
            const linkParam = isAnimator ? `id=${camp.id}` : `t=${camp.token}`;
            
            const priceDisplay = !isAnimator ? `<p class="text-blue-600 font-bold text-lg">${camp.prix}€</p>` : '';
            
            newContent += `
                <div class="bg-white rounded-lg shadow-md overflow-hidden transform hover:-translate-y-1 transition-transform duration-300 group">
                    <div class="relative cursor-pointer" onclick="window.location.href='${detailPage}?${linkParam}'">
                        <img src="${camp.image_url}" alt="Image pour ${camp.nom}" class="w-full h-48 object-cover" onerror="this.onerror=null;this.src='https://placehold.co/600x400/e2e8f0/cbd5e0?text=Image+invalide';">
                        ${!isAnimator && isLoggedIn ? `
                        <button class="favorite-button absolute top-3 right-3 bg-white/70 backdrop-blur-sm p-2 rounded-full transition-all hover:scale-110" data-camp-id="${camp.id}" title="Ajouter aux favoris">
                            <svg class="w-5 h-5 ${isFavorited ? 'text-red-500 fill-current' : 'text-gray-600'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.5l1.318-1.182a4.5 4.5 0 116.364 6.364L12 21l-7.682-7.682a4.5 4.5 0 010-6.364z"></path>
                            </svg>
                        </button>` : ''}
                    </div>
                    <div class="p-4 cursor-pointer" onclick="window.location.href='${detailPage}?${linkParam}'">
                         <h3 class="font-bold text-lg mb-2 truncate">${camp.nom}</h3>
                         <p class="text-gray-600 text-sm mb-1">📍 ${camp.ville}</p>
                         <p class="text-gray-600 text-sm mb-3">🎂 ${camp.age_min} - ${camp.age_max} ans</p>
                         <div class="flex justify-between items-center">
                             ${priceDisplay}
                             <span class="text-xs font-semibold text-gray-500 ml-auto">${new Date(camp.date_debut).toLocaleDateString('fr-FR')}</span>
                         </div>
                    </div>
                </div>`;
        });
        campsListContainer.innerHTML = newContent;
        if (!isAnimator) addFavoriteListeners();
    }

    function addFavoriteListeners() {
        document.querySelectorAll('.favorite-button').forEach(button => {
            button.addEventListener('click', toggleFavorite);
        });
    }

    async function toggleFavorite(event) {
        event.stopPropagation();
        const button = event.currentTarget;
        const campId = button.dataset.campId;
        const svg = button.querySelector('svg');
        try {
            const response = await fetch('api/toggle_favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ campId })
            });
            const result = await response.json();
            if (result.success) {
                if (result.isFavorited) {
                    userFavorites.push(campId);
                    svg.classList.add('text-red-500', 'fill-current');
                } else {
                    userFavorites = userFavorites.filter(id => id !== campId);
                    svg.classList.remove('text-red-500', 'fill-current');
                }
            }
        } catch (error) {
            console.error('Erreur favoris:', error);
        }
    }
    
    const debouncedFetch = debounce((e) => {
        fetchAndDisplayCamps({ name: e.target.value });
    }, 300);

    nameSearchInput.addEventListener('input', debouncedFetch);
    fetchAndDisplayCamps({ name: nameSearchInput.value });
});
</script>
</body>
</html>