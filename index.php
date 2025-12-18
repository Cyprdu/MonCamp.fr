<?php 
require_once 'partials/header.php'; 

$is_logged_in = isset($_SESSION['user']);
$is_animateur = $is_logged_in && ($_SESSION['user']['is_animateur'] ?? false);
$user_favorites = $_SESSION['user']['favorites'] ?? [];

// Liste des départements (inchangé)
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
<button id="fab-filter-btn" class="fixed bottom-6 left-6 z-50 bg-blue-600 text-white p-4 rounded-full shadow-xl hover:bg-blue-700 hover:scale-105 transition-all duration-300 flex items-center justify-center group">
    <i class="fa-solid fa-sliders text-xl"></i>
    <span class="max-w-0 overflow-hidden whitespace-nowrap group-hover:max-w-xs group-hover:ml-2 transition-all duration-300 ease-in-out font-bold">Filtres</span>
</button>

<div id="filter-drawer-overlay" class="fixed inset-0 bg-black/50 z-50 hidden transition-opacity opacity-0"></div>
<aside id="filter-drawer" class="fixed top-0 left-0 bottom-0 w-80 bg-white z-50 shadow-2xl transform -translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto">
    <div class="p-6">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-xl font-bold text-gray-800">Filtrer les camps</h2>
            <button id="close-drawer-btn" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-xmark text-2xl"></i>
            </button>
        </div>

        <form id="drawer-filter-form" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Département</label>
                <div class="relative">
                    <select id="drawer-department" name="department" class="w-full pl-3 pr-10 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none text-gray-700">
                        <option value="">Partout en France</option>
                        <?php foreach ($departments as $code => $name): ?>
                            <option value="<?php echo $code; ?>"><?php echo $code; ?> - <?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <i class="fa-solid fa-chevron-down text-gray-400"></i>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pour qui ?</label>
                <div class="relative">
                    <?php if ($is_logged_in): ?>
                        <select id="drawer-child" name="age" class="w-full pl-3 pr-10 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none text-gray-700">
                            <option value="">Sélectionner un enfant</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <i class="fa-solid fa-chevron-down text-gray-400"></i>
                        </div>
                    <?php else: ?>
                        <input type="number" id="drawer-age" name="age" placeholder="Âge de l'enfant" class="w-full pl-3 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <?php endif; ?>
                </div>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition-colors shadow-md mt-4">
                Appliquer les filtres
            </button>
            <button type="button" id="reset-filters-btn" class="w-full bg-white text-gray-600 font-medium py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors text-sm">
                Réinitialiser
            </button>
        </form>
    </div>
</aside>
<?php endif; ?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl pt-6">
    <section class="text-center my-8">
        <?php if ($is_logged_in): ?>
            <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight text-gray-900 mb-2">
                Bonjour <span class="bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 bg-clip-text text-transparent"><?php echo htmlspecialchars($_SESSION['user']['prenom']); ?></span>
            </h1>
            <p class="text-lg text-gray-500">
                <?php echo $is_animateur ? "Prêt à trouver votre prochaine mission d'animation ?" : "Prêt à trouver une nouvelle aventure pour vos enfants ?"; ?>
            </p>
        <?php else: ?>
            <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight text-gray-900 mb-2">
                Bienvenue sur <span class="bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 bg-clip-text text-transparent">ColoMap</span>
            </h1>
            <p class="text-lg text-gray-500 max-w-2xl mx-auto">
                Explorez des centaines de camps partout en France. L'aventure inoubliable de votre enfant commence ici.
            </p>
        <?php endif; ?>
    </section>

    <section class="mt-8 mb-24">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <?php if($is_animateur): ?>
                    <i class="fa-solid fa-briefcase text-blue-600"></i>
                    Offres d'emploi
                <?php else: ?>
                    <i class="fa-solid fa-campground text-green-600"></i>
                    Camps disponibles
                <?php endif; ?>
            </h2>
        </div>
        
        <div id="camps-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 auto-rows-fr"></div>
        <div id="loader" class="col-span-full flex justify-center items-center py-12 hidden"><div class="loader"></div></div>
        <div id="end-message" class="text-center py-8 text-gray-500 hidden"><p>Vous avez tout vu ! 🎉</p></div>
    </section>

    <?php if (!$is_logged_in): ?>
    <article class="max-w-4xl mx-auto mt-32 mb-24 px-4 text-gray-800 leading-relaxed">
        
        <h2 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-10 text-center tracking-tight">
            ColoMap : <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Le Guide Ultime des Colonies de Vacances</span>
        </h2>

        <div class="text-xl md:text-2xl font-bold text-gray-700 mb-8 space-y-6">
            <p>
                Vous recherchez bien plus qu’une simple colonie de vacances pour votre enfant ? Vous êtes au bon endroit. ColoMap n’est pas un simple annuaire : c’est une véritable porte d’entrée vers des aventures uniques et mémorables. Chaque séjour est pensé pour éveiller la curiosité, développer la confiance en soi et forger le caractère des enfants, tout en favorisant des rencontres sincères. Jour après jour, ils vivent des expériences intenses, riches en découvertes et en émotions, et tissent des amitiés fortes qui, bien souvent, perdurent bien au-delà des vacances.
            </p>
            <p>
                Dans un monde toujours plus connecté et rythmé par les écrans, offrir à votre enfant une véritable parenthèse faite de nature, de sport et de découvertes est sans doute l’un des plus beaux cadeaux que vous puissiez lui faire. Les colonies de vacances offrent aux jeunes l’opportunité de s’éloigner du quotidien, de sortir de leur zone de confort et de vivre des expériences nouvelles et enrichissantes. Loin du cocon familial, ils apprennent progressivement l’autonomie, la prise d’initiative et la confiance en eux, tout en développant des compétences sociales essentielles. Encadrés par des équipes attentives, dans un environnement sécurisé et bienveillant, les enfants grandissent, s’épanouissent et repartent avec des souvenirs et des apprentissages qui les accompagneront durablement.
            </p>
        </div>

        <div class="my-12">
            <div class="text-[10px] text-gray-400 uppercase tracking-widest mb-2 text-center">Publicité</div>
            <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3659884670016121" crossorigin="anonymous"></script>
            <ins class="adsbygoogle"
                 style="display:block; text-align:center;"
                 data-ad-layout="in-article"
                 data-ad-format="fluid"
                 data-ad-client="ca-pub-3659884670016121"
                 data-ad-slot="6405652824"></ins>
            <script>
                 (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
        </div>

        <div class="text-xl md:text-2xl font-bold text-gray-700 mb-8 space-y-6">
            <p>
                Que votre enfant rêve de galoper au grand air, de monter sur scène sous les projecteurs, de percer les mystères de la science ou de repousser ses limites à travers des sports riches en sensations, notre plateforme met à votre disposition des milliers d’offres soigneusement sélectionnées à travers toute la France. Des paysages verdoyants de la Bretagne aux sommets majestueux des Alpes, sans oublier le soleil et les plages de la Méditerranée, chaque région regorge d’expériences uniques. Autant d’occasions pour votre enfant de découvrir de nouveaux horizons, de s’épanouir, d’apprendre et de créer des souvenirs inoubliables, dans des environnements aussi variés qu’enrichissants.

            </p>
            <p>
                La sécurité de votre enfant est au cœur de nos engagements et constitue notre priorité absolue. C’est pourquoi tous les organismes référencés sur ColoMap font l’objet de vérifications rigoureuses afin de garantir le respect des normes d’encadrement, de sécurité et de qualité les plus exigeantes. Pour vous accompagner dans votre choix, vous avez également accès aux avis détaillés d’autres parents, à une présentation claire des programmes pédagogiques et aux informations essentielles sur chaque séjour. Vous pouvez ainsi comparer, choisir et réserver en toute confiance, avec la certitude d’offrir à votre enfant une expérience encadrée, fiable et pleinement sécurisée.
            </p>
            <p>
               Ne laissez pas l’été s’écouler sans offrir à vos enfants une expérience véritablement marquante. Partir en colonie, c’est bien plus que de simples vacances loin de la maison : c’est une véritable école de la vie. Au fil des activités et des rencontres, les enfants apprennent à vivre en collectivité, à partager, à faire preuve de solidarité et à gagner en autonomie. Ils développent aussi la confiance en eux, le sens des responsabilités et le goût du dépassement de soi, tout en créant des souvenirs forts qui les accompagneront longtemps après la fin de l’été.
            </p>
        </div>

        <div class="my-12">
            <div class="text-[10px] text-gray-400 uppercase tracking-widest mb-2 text-center">Publicité</div>
            <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3659884670016121" crossorigin="anonymous"></script>
            <ins class="adsbygoogle"
                 style="display:block; text-align:center;"
                 data-ad-layout="in-article"
                 data-ad-format="fluid"
                 data-ad-client="ca-pub-3659884670016121"
                 data-ad-slot="6405652824"></ins>
            <script>
                 (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
        </div>

        <div class="mt-16 text-center">
            <a href="login" class="inline-block bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-extrabold text-2xl py-5 px-10 rounded-full shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300">
                Partir à l'aventure
            </a>
            <p class="mt-4 text-gray-500 font-medium">Rejoignez gratuitement la communauté ColoMap dès aujourd'hui.</p>
        </div>

    </article>
    <?php endif; ?>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isAnimator = <?php echo json_encode($is_animateur); ?>;
    const isLoggedIn = <?php echo json_encode($is_logged_in); ?>;
    const campsListContainer = document.getElementById('camps-list');
    const loader = document.getElementById('loader');
    const endMessage = document.getElementById('end-message');
    let userFavorites = <?php echo json_encode($user_favorites); ?>;

    // Elements du Drawer et FAB
    const fabBtn = document.getElementById('fab-filter-btn');
    const drawer = document.getElementById('filter-drawer');
    const overlay = document.getElementById('filter-drawer-overlay');
    const closeDrawerBtn = document.getElementById('close-drawer-btn');
    const drawerForm = document.getElementById('drawer-filter-form');
    const childSelect = document.getElementById('drawer-child');
    const resetBtn = document.getElementById('reset-filters-btn');

    let currentPage = 1;
    const limit = 40;
    let isLoading = false;
    let hasMoreCamps = true;
    let currentFilters = {};

    // --- LOGIQUE DRAWER ---
    if (!isAnimator && fabBtn) {
        fabBtn.addEventListener('click', () => {
            drawer.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            setTimeout(() => overlay.classList.remove('opacity-0'), 10);
            document.body.style.overflow = 'hidden';
        });

        function closeDrawer() {
            drawer.classList.add('-translate-x-full');
            overlay.classList.add('opacity-0');
            setTimeout(() => overlay.classList.add('hidden'), 300);
            document.body.style.overflow = '';
        }

        closeDrawerBtn.addEventListener('click', closeDrawer);
        overlay.addEventListener('click', closeDrawer);

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

        if(drawerForm) {
            drawerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const params = Object.fromEntries(formData.entries());
                resetAndFetch(params);
                closeDrawer();
            });
        }

        if(resetBtn) {
            resetBtn.addEventListener('click', function() {
                drawerForm.reset();
                resetAndFetch({});
                closeDrawer();
            });
        }
    }

    // --- VARIABLES PUBS ---
    let itemsSinceLastAd = 0; 
    let firstAdShown = false;
    let firstAdThreshold = getRandomInt(1, 3);
    function getRandomInt(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }
    let nextAdThreshold = getRandomInt(9, 13); 

    async function fetchAndDisplayCamps(params = {}, isAppend = false) {
        if (isLoading || (!hasMoreCamps && isAppend)) return;
        isLoading = true;
        loader.classList.remove('hidden');
        if (!isAppend) endMessage.classList.add('hidden');

        const apiUrl = isAnimator ? 'api/get_eligible_camps_for_animator.php' : 'api/get_camps.php';
        params.page = currentPage;
        params.limit = limit;
        const query = new URLSearchParams(params).toString();
        
        try {
            const response = await fetch(`${apiUrl}?${query}`);
            if (!response.ok) throw new Error('Erreur de chargement');
            const camps = await response.json();
            loader.classList.add('hidden');
            
            if (!isAppend) {
                campsListContainer.innerHTML = '';
                itemsSinceLastAd = 0;
                firstAdShown = false; 
                firstAdThreshold = getRandomInt(1, 3);
                nextAdThreshold = getRandomInt(9, 13);
                window.scrollTo(0, 0);
            }

            if (camps.length < limit) {
                hasMoreCamps = false;
                if (camps.length > 0 || isAppend) endMessage.classList.remove('hidden');
            }

            if (camps.length === 0 && !isAppend) {
                campsListContainer.innerHTML = `<div class="col-span-full text-center py-12 text-gray-500">Aucun camp trouvé pour ces critères.</div>`;
            } else {
                renderCamps(camps);
            }
        } catch (error) {
            loader.classList.add('hidden');
            console.error(error);
        } finally {
            isLoading = false;
        }
    }

    function resetAndFetch(params) {
        currentPage = 1;
        hasMoreCamps = true;
        currentFilters = params;
        fetchAndDisplayCamps(currentFilters, false);
    }
    
    window.addEventListener('scroll', () => {
        const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
        if (scrollTop + clientHeight >= scrollHeight - 800 && hasMoreCamps && !isLoading) {
            currentPage++;
            fetchAndDisplayCamps(currentFilters, true);
        }
    });

    function getAdHtml() {
        return `
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-full justify-center items-center p-4 fade-in-anim">
                <span class="text-[10px] text-gray-400 uppercase tracking-widest mb-2">Publicité</span>
                <div class="w-full h-full flex items-center justify-center">
                    <ins class="adsbygoogle" style="display:block; width:100%;" data-ad-format="rectangle" data-ad-layout="in-article" data-ad-client="ca-pub-3659884670016121" data-ad-slot="7108236695"></ins>
                </div>
            </div>`;
    }

    function renderCamps(camps) {
        let newContent = '';
        
        camps.forEach((camp, index) => {
            const isFavorited = !isAnimator && userFavorites.includes(camp.id);
            const detailPage = isAnimator ? 'info-camp-animateur' : 'camp_details';
            const linkParam = isAnimator ? `id=${camp.id}` : `t=${camp.token}`;
            const priceDisplay = !isAnimator ? `<div class="bg-blue-50 px-3 py-1 rounded-full"><span class="text-blue-700 font-bold">${camp.prix}€</span></div>` : '';
            const dateStr = new Date(camp.date_debut).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
            let imageUrl = (camp.image_url && camp.image_url.trim() !== '') ? camp.image_url.replace(/\\/g, '/') : 'https://placehold.co/600x400/f1f5f9/94a3b8?text=Image+non+disponible';

            newContent += `
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 group flex flex-col h-full fade-in-anim">
                    <div class="relative cursor-pointer h-48 overflow-hidden" onclick="window.location.href='${detailPage}?${linkParam}'">
                        <img src="${imageUrl}" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500" onerror="this.src='https://placehold.co/600x400/f1f5f9/94a3b8?text=Erreur+image';">
                        ${!isAnimator && isLoggedIn ? `
                        <button class="favorite-button absolute top-3 right-3 bg-white/90 p-2 rounded-full shadow-sm hover:bg-white z-10 transition-transform active:scale-90" data-camp-id="${camp.id}">
                            <svg class="w-5 h-5 ${isFavorited ? 'text-red-500 fill-current' : 'text-gray-400'}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.5l1.318-1.182a4.5 4.5 0 116.364 6.364L12 21l-7.682-7.682a4.5 4.5 0 010-6.364z" />
                            </svg>
                        </button>` : ''}
                    </div>
                    <div class="p-5 flex flex-col flex-grow cursor-pointer" onclick="window.location.href='${detailPage}?${linkParam}'">
                        <h3 class="font-bold text-gray-900 text-lg mb-2 line-clamp-1">${camp.nom}</h3>
                        <div class="space-y-2 mb-4 flex-grow text-sm text-gray-600">
                            <div class="flex items-center"><i class="fa-solid fa-location-dot w-4 mr-2 text-gray-400"></i>${camp.ville}</div>
                            <div class="flex items-center"><i class="fa-solid fa-child w-4 mr-2 text-gray-400"></i>${camp.age_min}-${camp.age_max} ans</div>
                            <div class="flex items-center"><i class="fa-regular fa-calendar w-4 mr-2 text-gray-400"></i>Dès le ${dateStr}</div>
                        </div>
                        <div class="pt-4 border-t border-gray-100 flex justify-between items-center">${priceDisplay}<span class="text-blue-600 text-sm font-medium">Détails →</span></div>
                    </div>
                </div>`;

            // LOGIQUE PUB LISTE
            itemsSinceLastAd++;
            if (!firstAdShown && itemsSinceLastAd === firstAdThreshold) {
                newContent += getAdHtml();
                itemsSinceLastAd = 0;
                firstAdShown = true;
            } else if (firstAdShown && itemsSinceLastAd >= nextAdThreshold) {
                newContent += getAdHtml();
                itemsSinceLastAd = 0;
                nextAdThreshold = getRandomInt(9, 13);
            }
        });

        campsListContainer.insertAdjacentHTML('beforeend', newContent);
        try { (window.adsbygoogle = window.adsbygoogle || []).push({}); } catch (e) {}
    }

    campsListContainer.addEventListener('click', function(e) {
        const button = e.target.closest('.favorite-button');
        if (button) {
            e.preventDefault(); e.stopPropagation();
            toggleFavorite(button);
        }
    });

    async function toggleFavorite(button) {
        const campId = parseInt(button.dataset.campId);
        const svg = button.querySelector('svg');
        const isCurrentlyFavorited = svg.classList.contains('text-red-500');

        if (isCurrentlyFavorited) {
            svg.classList.remove('text-red-500', 'fill-current');
            svg.classList.add('text-gray-400');
            userFavorites = userFavorites.filter(id => id !== campId);
        } else {
            svg.classList.add('text-red-500', 'fill-current');
            svg.classList.remove('text-gray-400');
            userFavorites.push(campId);
        }

        button.classList.add('scale-90');
        setTimeout(() => button.classList.remove('scale-90'), 150);

        try {
            const response = await fetch('api/toggle_favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ campId })
            });
            const result = await response.json();
            if (!result.success) {
                // Revert si échec
                if (isCurrentlyFavorited) {
                    svg.classList.add('text-red-500', 'fill-current');
                    svg.classList.remove('text-gray-400');
                } else {
                    svg.classList.remove('text-red-500', 'fill-current');
                    svg.classList.add('text-gray-400');
                }
            }
        } catch (error) { 
            // Revert si échec réseau
            if (isCurrentlyFavorited) {
                svg.classList.add('text-red-500', 'fill-current');
                svg.classList.remove('text-gray-400');
            } else {
                svg.classList.remove('text-red-500', 'fill-current');
                svg.classList.add('text-gray-400');
            }
        }
    }
    
    resetAndFetch({});
});
</script>

<style>
.fade-in-anim { animation: fadeIn 0.5s ease-in-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
#filter-drawer { box-shadow: 4px 0 24px rgba(0,0,0,0.15); }
</style>

<?php require_once 'partials/footer.php'; ?>