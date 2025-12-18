<?php
require_once 'api/config.php';
require_once 'partials/header.php';

// --- RECUPERATION USER ---
$user_prenom = 'Voyageur'; 
$user_id = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT prenom FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user && !empty($user['prenom'])) {
        $user_prenom = htmlspecialchars(ucfirst($user['prenom']));
    }
}

function getCampImageUrl($dbPath) {
    if (empty($dbPath)) return 'https://images.unsplash.com/photo-1506477331477-33d5d8b3dc85?q=80&w=2600&auto=format&fit=crop';
    if (strpos($dbPath, 'uploads/') === 0) return $dbPath;
    return 'uploads/camps/' . $dbPath;
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">

<style>
    /* --- CSS AMÉLIORÉ --- */
    :root {
        --primary: #FF385C;
        --dark: #222222;
        --gray: #717171;
        --light: #F7F7F7;
        --radius: 16px; 
    }

    body {
        font-family: 'Inter', sans-serif;
        background-color: #fff;
        color: var(--dark);
        margin: 0;
        overflow-x: hidden;
    }

    /* HERO SECTION RESPONSIVE */
    .hero-container {
        padding: 40px 40px 50px 40px;
        max-width: 1400px;
        margin: 0 auto;
    }

    .greeting-text {
        font-family: 'Poppins', sans-serif;
        font-size: 3rem; /* Gros sur PC */
        font-weight: 800;
        margin-bottom: 30px;
        color: var(--dark);
        line-height: 1.1;
        text-align: left; /* Gauche sur PC */
    }
    
    .greeting-text span { color: var(--primary); }

    /* SEARCH BAR */
    .search-bar-wrapper {
        background: white;
        border: 1px solid #ddd;
        border-radius: 50px;
        padding: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        display: flex;
        align-items: center;
        max-width: 750px;
        transition: box-shadow 0.2s;
    }
    .search-bar-wrapper:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.12); }

    .search-input {
        border: none;
        outline: none;
        flex-grow: 1;
        padding-left: 20px;
        font-size: 1.1rem;
        font-weight: 500;
        color: var(--dark);
    }

    .filter-btn {
        display: flex; align-items: center; gap: 8px;
        padding: 12px 20px;
        background: transparent; border: none;
        font-weight: 600; color: var(--dark);
        cursor: pointer; border-left: 1px solid #ddd; margin-left: 10px;
        text-decoration: none;
    }

    .search-submit {
        background-color: var(--primary);
        color: white;
        width: 50px; height: 50px;
        border-radius: 50%; border: none;
        cursor: pointer; font-size: 1.2rem;
        display: flex; align-items: center; justify-content: center;
        transition: transform 0.1s;
    }
    .search-submit:active { transform: scale(0.95); }

    /* CARROUSELS */
    .section-wrapper {
        max-width: 1400px;
        margin: 50px auto;
        padding-left: 40px; 
    }

    .section-header {
        display: flex; align-items: center; gap: 12px;
        margin-bottom: 25px; padding-right: 40px;
    }

    .section-title {
        font-size: 1.6rem; font-weight: 700; margin: 0;
        font-family: 'Poppins', sans-serif;
    }

    .carousel-container { position: relative; }

    .carousel-track {
        display: flex; gap: 24px;
        overflow-x: auto;
        padding: 5px 40px 30px 0;
        scroll-behavior: smooth;
        scrollbar-width: none; -ms-overflow-style: none;
    }
    .carousel-track::-webkit-scrollbar { display: none; }

    /* NAVIGATION BUTTONS */
    .nav-arrow {
        position: absolute; top: 38%;
        width: 40px; height: 40px;
        background: white; border: 1px solid rgba(0,0,0,0.1);
        border-radius: 50%;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        cursor: pointer; z-index: 10;
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transition: all 0.2s;
    }
    .carousel-container:hover .nav-arrow { opacity: 1; }
    .nav-arrow:hover { transform: scale(1.1); }
    .nav-prev { left: -20px; }
    .nav-next { right: 0; }

    /* CARDS */
    .camp-card {
        flex: 0 0 300px;
        text-decoration: none; color: inherit;
        display: flex; flex-direction: column;
        group: hover;
    }

    .camp-img-wrapper {
        width: 100%;
        aspect-ratio: 16 / 10.5; /* Paysage forcé */
        border-radius: var(--radius);
        overflow: hidden; margin-bottom: 12px;
        background-color: #f0f0f0; position: relative;
    }

    .camp-img {
        width: 100%; height: 100%; object-fit: cover;
        transition: transform 0.4s;
    }
    .camp-card:hover .camp-img { transform: scale(1.05); }

    .camp-info { padding: 0 4px; }
    
    .camp-title {
        font-weight: 600; font-size: 1.05rem; color: var(--dark);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        margin-bottom: 4px;
    }

    .camp-meta {
        color: var(--gray); font-size: 0.95rem; margin-bottom: 6px;
    }

    .camp-price {
        font-weight: 700; color: var(--dark); font-size: 1.05rem;
    }
    .camp-price span { font-weight: 400; font-size: 0.9rem; color: var(--gray); }

    /* PUB */
    .adsense-spacer {
        max-width: 1200px; margin: 40px auto; height: 100px;
        background: #fafafa; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        color: #ccc;
    }

    /* MOBILE ONLY */
    @media (max-width: 768px) {
        .hero-container { padding: 25px 20px; text-align: center; }
        .greeting-text { font-size: 1.8rem; text-align: center; } /* Centré mobile */
        
        .search-bar-wrapper { padding: 6px; max-width: 100%; }
        .search-input { font-size: 1rem; padding-left: 15px; }
        .filter-btn span { display: none; } /* Cache texte filtre */
        
        .section-wrapper { padding-left: 20px; margin: 30px 0; }
        .camp-card { flex: 0 0 85%; } /* Style App Mobile */
        .nav-arrow { display: none !important; }
        
        .section-title { font-size: 1.3rem; }
    }

    /* SKELETON */
    .skeleton {
        background: linear-gradient(90deg, #eee 25%, #f5f5f5 50%, #eee 75%);
        background-size: 200% 100%; animation: shimmer 1.5s infinite;
        border-radius: var(--radius);
    }
    @keyframes shimmer { 0%{background-position: 200% 0;} 100%{background-position: -200% 0;} }

</style>

<div class="hero-container">
    <div class="greeting-text">
        Bienvenue, <span><?= $user_prenom ?></span> !
    </div>

    <div class="search-bar-wrapper">
        <input type="text" id="main-search" class="search-input" placeholder="Quelle destination pour vos enfants ?">
        
        <a href="recherche.php" class="filter-btn">
            <i class="fa-solid fa-sliders"></i> <span>Filtres</span>
        </a>

        <button class="search-submit" onclick="triggerSearch()">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div>
</div>

<div id="main-content">

    <div class="section-wrapper">
        <div class="section-header">
            <i class="fa-solid fa-star" style="color:#FF385C;"></i>
            <h2 class="section-title">Coups de cœur</h2>
        </div>
        
        <div class="carousel-container">
            <div class="nav-arrow nav-prev" onclick="scrollCarousel('boosted-track', -1)"><i class="fa-solid fa-chevron-left"></i></div>
            
            <div class="carousel-track" id="boosted-track">
                <?php
                try {
                    $sql = "SELECT * FROM camps WHERE date_debut > NOW() ORDER BY boost_points DESC, id DESC LIMIT 10";
                    $stmt = $pdo->query($sql);
                    while($camp = $stmt->fetch(PDO::FETCH_ASSOC)):
                        $img = getCampImageUrl($camp['image_url'] ?? '');
                        $nom = htmlspecialchars($camp['nom']);
                        $ville = htmlspecialchars($camp['ville'] ?? 'France');
                        $prix = number_format($camp['prix'], 0, ',', ' ');
                        $id = $camp['id'];
                ?>
                    <a href="camp_details.php?id=<?= $id ?>" class="camp-card">
                        <div class="camp-img-wrapper">
                            <img src="<?= $img ?>" alt="<?= $nom ?>" class="camp-img" loading="lazy">
                        </div>
                        <div class="camp-info">
                            <div class="camp-title"><?= $nom ?></div>
                            <div class="camp-meta"><?= $ville ?></div>
                            <div class="camp-price">à partir de <?= $prix ?>€</div>
                        </div>
                    </a>
                <?php endwhile; } catch (Exception $e) {} ?>
            </div>
            
            <div class="nav-arrow nav-next" onclick="scrollCarousel('boosted-track', 1)"><i class="fa-solid fa-chevron-right"></i></div>
        </div>
    </div>

    <div id="dynamic-container"></div>

</div>

<div style="height: 100px;"></div>

<script>const USER_ID = <?= $user_id ?>;</script>

<script>
// --- LOGIQUE GEOLOCALISATION ---
// On demande la localisation. Si OK, on injecte la catégorie "Autour de chez moi"
function initGeo() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(showPosition, showError);
    }
}

function showPosition(position) {
    const lat = position.coords.latitude;
    const lon = position.coords.longitude;
    
    // On injecte la section "Autour de chez moi" en PREMIER dans le conteneur dynamique
    const sectionHtml = buildSectionHTML('cat-nearby', 'Autour de chez moi', 'fa-location-dot');
    const div = document.createElement('div');
    div.innerHTML = sectionHtml;
    
    const container = document.getElementById('dynamic-container');
    container.insertBefore(div, container.firstChild); // Insérer tout en haut
    
    loadCategory('cat-nearby', `nearby&lat=${lat}&lon=${lon}`);
}

function showError(error) { console.log("Loc refusée ou erreur"); }

// Lancer la demande au chargement (ou au clic si on préfère être moins intrusif)
initGeo();


// --- SEARCH ---
function triggerSearch() {
    const val = document.getElementById('main-search').value.trim();
    if(val) window.location.href = `recherche.php?r=${encodeURIComponent(val)}`;
}
document.getElementById('main-search').addEventListener('keypress', (e) => { if(e.key==='Enter') triggerSearch(); });


// --- SCROLL INTELLIGENT ---
function scrollCarousel(id, dir) {
    const track = document.getElementById(id);
    const amount = track.clientWidth * 0.8;
    track.scrollBy({ left: dir * amount, behavior: 'smooth' });
}


// --- LISTE MASSIVE DE CATÉGORIES ---
// L'API PHP fera le tri complexe
let allCategories = [
    // Catégories Algorithmiques (Apparaissent si connecté)
    { title: "Sélectionné pour vous", cat: "recommended", icon: "fa-wand-magic-sparkles", auth_only: true },
    { title: "Vos favoris", cat: "favorites", icon: "fa-heart", auth_only: true },

    // Catégories Standards
    { title: "Dernière minute", cat: "last_minute", icon: "fa-hourglass-half" },
    { title: "Petits prix", cat: "cheap", icon: "fa-piggy-bank" },
    { title: "Une semaine (7+ jours)", cat: "long_stay", icon: "fa-calendar-week" },
    { title: "Séjours Itinérants", cat: "itinerant", icon: "fa-route" }, // Test colonne Itinerance
    { title: "Nature & Forêt", cat: "nature", icon: "fa-tree" },
    { title: "Bord de mer", cat: "sea", icon: "fa-umbrella-beach" },
    { title: "Sports Nautiques", cat: "water", icon: "fa-sailboat" },
    { title: "Montagne & Altitude", cat: "mountain", icon: "fa-person-hiking" },
    { title: "Cuisine & Pâtisserie", cat: "cooking", icon: "fa-utensils" },
    { title: "Sciences & Tech", cat: "science", icon: "fa-flask" },
    { title: "Gaming & Numérique", cat: "gaming", icon: "fa-gamepad" },
    { title: "Passion Cheval", cat: "horse", icon: "fa-horse-head" },
    { title: "Artistique & Créatif", cat: "arts", icon: "fa-palette" },
    { title: "Musique & Chant", cat: "music", icon: "fa-music" },
    { title: "Théâtre & Cinéma", cat: "theater", icon: "fa-masks-theater" },
    { title: "Premiers Départs (6-10 ans)", cat: "kids", icon: "fa-child" },
    { title: "Ados (14-17 ans)", cat: "teens", icon: "fa-users" },
    { title: "Pèlerinages & Spi", cat: "religion", icon: "fa-church" },
    { title: "Séjours à l'étranger", cat: "abroad", icon: "fa-plane" },
    { title: "Sports d'Hiver", cat: "winter", icon: "fa-snowflake" },
    { title: "Campagne", cat: "countryside", icon: "fa-tractor" },
    { title: "Aventure & Survie", cat: "survival", icon: "fa-compass" },
    { title: "Humanitaire", cat: "solidarity", icon: "fa-hand-holding-heart" },
    { title: "Langues Étrangères", cat: "language", icon: "fa-language" }
];

const container = document.getElementById('dynamic-container');

// Génération HTML
allCategories.forEach((conf, index) => {
    // Si la catégorie nécessite d'être connecté et que l'user ne l'est pas, on saute
    if(conf.auth_only && USER_ID === 0) return;

    const sectionId = `track-${index}`;
    const div = document.createElement('div');
    
    // Pub Adsense tous les 4 blocs
    if(index > 0 && index % 4 === 0) {
        div.innerHTML += `<div class="adsense-spacer"><small>Espace Publicitaire</small></div>`;
    }

    div.innerHTML += buildSectionHTML(sectionId, conf.title, conf.icon);
    container.appendChild(div);

    // Observer Lazy Loading
    const obs = new IntersectionObserver((entries) => {
        if(entries[0].isIntersecting) {
            loadCategory(sectionId, conf.cat);
            obs.disconnect();
        }
    }, { rootMargin: "400px" });
    obs.observe(div.querySelector('.carousel-track'));
});

// Fonctions Helper
function buildSectionHTML(id, title, icon) {
    return `
    <div class="section-wrapper">
        <div class="section-header">
            <i class="fa-solid ${icon}" style="font-size:1.2rem; color:var(--dark);"></i>
            <h3 class="section-title">${title}</h3>
        </div>
        <div class="carousel-container">
            <div class="nav-arrow nav-prev" onclick="scrollCarousel('${id}', -1)"><i class="fa-solid fa-chevron-left"></i></div>
            <div class="carousel-track" id="${id}">
                <div class="skeleton" style="min-width:300px; aspect-ratio:16/10.5;"></div>
                <div class="skeleton" style="min-width:300px; aspect-ratio:16/10.5;"></div>
                <div class="skeleton" style="min-width:300px; aspect-ratio:16/10.5;"></div>
            </div>
            <div class="nav-arrow nav-next" onclick="scrollCarousel('${id}', 1)"><i class="fa-solid fa-chevron-right"></i></div>
        </div>
    </div>`;
}

function loadCategory(elId, catType) {
    // On passe user_id dans l'URL pour l'algo PHP
    fetch(`api/get_home_category.php?cat=${catType}&uid=${USER_ID}`)
        .then(r => r.text())
        .then(html => document.getElementById(elId).innerHTML = html);
}
</script>

<?php require_once 'partials/footer.php'; ?>