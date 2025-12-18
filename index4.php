<?php
/**
 * index.php - Version Intégrale Optimisée
 * Inclus : Centrage complet, 25 catégories, Pubs AdSense, Lazy Loading
 */
require_once 'api/config.php';
require_once 'partials/header.php';

// --- RÉCUPÉRATION UTILISATEUR ---
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
    :root {
        --primary: #FF385C;
        --dark: #222222;
        --gray: #717171;
        --radius: 16px; 
    }

    body { font-family: 'Inter', sans-serif; background-color: #fff; color: var(--dark); margin: 0; overflow-x: hidden; }

    /* --- ARCHITECTURE EN COLONNES --- */
    .global-layout {
        display: flex;
        justify-content: center;
        width: 100%;
        max-width: 1920px;
        margin: 0 auto;
    }

    .ad-side { width: 180px; flex-shrink: 0; padding-top: 150px; }

    .main-wrapper {
        flex: 1;
        max-width: 1200px;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: center; /* CENTRE TOUT LE CONTENU */
    }

    /* --- HERO SECTION CENTRÉE --- */
    .hero-center {
        width: 100%;
        padding: 80px 20px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .welcome-msg {
        font-family: 'Poppins', sans-serif;
        font-size: clamp(2.2rem, 6vw, 3.8rem);
        font-weight: 800;
        line-height: 1.1;
        margin-bottom: 35px;
    }
    .welcome-msg span { color: var(--primary); }

    .search-bar-ui {
        background: white;
        border: 1px solid #ddd;
        border-radius: 60px;
        padding: 10px 12px 10px 30px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        display: flex;
        align-items: center;
        width: 100%;
        max-width: 750px; /* Taille optimale */
        transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .search-bar-ui:focus-within { box-shadow: 0 12px 40px rgba(0,0,0,0.15); border-color: #bbb; }

    .search-input-field { border: none; outline: none; flex: 1; font-size: 1.25rem; font-weight: 500; color: #333; }
    .search-trigger-btn { 
        background: var(--primary); color: white; width: 54px; height: 54px; 
        border-radius: 50%; border: none; cursor: pointer; font-size: 1.3rem;
        display: flex; align-items: center; justify-content: center;
    }

    /* --- SECTIONS --- */
    .content-section { width: 100%; margin: 50px 0; padding: 0 30px; box-sizing: border-box; }
    .section-title-box { display: flex; align-items: center; gap: 12px; margin-bottom: 25px; }
    .section-name { font-family: 'Poppins', sans-serif; font-size: 1.7rem; font-weight: 700; margin: 0; }

    .carousel-view { display: flex; gap: 24px; overflow-x: auto; scrollbar-width: none; padding-bottom: 20px; scroll-behavior: smooth; }
    .carousel-view::-webkit-scrollbar { display: none; }

    /* --- CARTES --- */
    .camp-item { flex: 0 0 300px; text-decoration: none; color: inherit; }
    .img-wrapper { width: 100%; aspect-ratio: 16/10.5; border-radius: var(--radius); overflow: hidden; background: #f0f0f0; margin-bottom: 12px; }
    .img-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
    .camp-item:hover img { transform: scale(1.07); }
    .camp-title-txt { font-weight: 600; font-size: 1.1rem; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .camp-price-txt { font-weight: 800; font-size: 1.15rem; color: var(--dark); margin-top: 6px; }

    /* --- ADS STYLING --- */
    .ad-box { background: #fafafa; border: 2px dashed #ddd; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #ccc; text-transform: uppercase; font-size: 0.75rem; font-weight: bold; }
    .ad-box.vert { width: 160px; height: 600px; position: sticky; top: 120px; margin: 0 auto; }
    .ad-box.horiz { width: calc(100% - 60px); height: 130px; margin: 40px auto; }

    /* --- SKELETON --- */
    .skel-card { min-width: 300px; height: 210px; background: #eee; border-radius: 16px; animation: pulse 1.5s infinite; }
    @keyframes pulse { 0%, 100% { opacity: 0.5; } 50% { opacity: 1; } }

    @media (max-width: 1450px) { .ad-side { display: none; } }
    @media (max-width: 768px) {
        .hero-center { padding: 40px 20px; }
        .welcome-msg { font-size: 2rem; }
        .camp-item { flex: 0 0 85%; }
        .content-section { padding: 0 20px; }
    }
</style>

<div class="global-layout">
    
    <aside class="ad-side">
        <div class="ad-box vert">
            <ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-3659884670016121" data-ad-slot="SIDE_L" data-ad-format="auto"></ins>
        </div>
    </aside>

    <main class="main-wrapper">
        
        <section class="hero-center">
            <h1 class="welcome-msg">Bienvenue, <span><?= $user_prenom ?></span> !</h1>
            <div class="search-bar-ui">
                <input type="text" id="main-search-input" class="search-input-field" placeholder="Quelle destination pour vos enfants ?">
                <button class="search-trigger-btn" onclick="startSearch()">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </div>
        </section>

        <div id="homepage-feed" style="width: 100%;">
            <div class="content-section">
                <div class="section-title-box">
                    <i class="fa-solid fa-star" style="color: #FFD700; font-size: 1.4rem;"></i>
                    <h2 class="section-name">Coups de cœur</h2>
                </div>
                <div class="carousel-view">
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT * FROM camps WHERE date_debut > NOW() ORDER BY boost_points DESC LIMIT 10");
                        while($c = $stmt->fetch()):
                            $pic = getCampImageUrl($c['image_url']);
                    ?>
                        <a href="camp_details.php?id=<?= $c['id'] ?>" class="camp-item">
                            <div class="img-wrapper"><img src="<?= $pic ?>" loading="lazy"></div>
                            <div class="camp-title-txt"><?= htmlspecialchars($c['nom']) ?></div>
                            <div style="color: var(--gray); font-size: 0.95rem;"><?= htmlspecialchars($c['ville']) ?></div>
                            <div class="camp-price-txt">dès <?= number_format($c['prix'], 0, ',', ' ') ?>€</div>
                        </a>
                    <?php endwhile; } catch (Exception $e) {} ?>
                </div>
            </div>

            <div id="dynamic-area"></div>
        </div>

    </main>

    <aside class="ad-side">
        <div class="ad-box vert">
            <ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-3659884670016121" data-ad-slot="SIDE_R" data-ad-format="auto"></ins>
        </div>
    </aside>

</div>

<script>
const USER_ID = <?= $user_id ?>;
const area = document.getElementById('dynamic-area');

/**
 * LISTE COMPLÈTE DES 25 CATÉGORIES (Technique + Thématique + Âge)
 */
const allCats = [
    { title: "Autour de chez moi", cat: "nearby", icon: "fa-location-dot" },
    { title: "Sélectionné pour vous", cat: "recommended", icon: "fa-wand-magic-sparkles" },
    { title: "Vos favoris", cat: "favorites", icon: "fa-heart" },
    { title: "Dernière minute", cat: "last_minute", icon: "fa-clock" },
    { title: "Petits prix", cat: "cheap", icon: "fa-tag" },
    { title: "Nature & Forêt", cat: "nature", icon: "fa-tree" },
    { title: "Bord de mer", cat: "sea", icon: "fa-umbrella-beach" },
    { title: "Montagne & Air pur", cat: "mountain", icon: "fa-mountain" },
    { title: "Gaming & Numérique", cat: "gaming", icon: "fa-gamepad" },
    { title: "Sports & Action", cat: "sport", icon: "fa-volleyball" },
    { title: "Cuisine & Pâtisserie", cat: "cooking", icon: "fa-utensils" },
    { title: "Sciences & Technologie", cat: "science", icon: "fa-flask" },
    { title: "Passion Cheval", cat: "horse", icon: "fa-horse-head" },
    { title: "Artistique & Créatif", cat: "arts", icon: "fa-palette" },
    { title: "Musique & Chant", cat: "music", icon: "fa-music" },
    { title: "Théâtre & Cinéma", cat: "theater", icon: "fa-masks-theater" },
    { title: "Enfants (6-10 ans)", cat: "kids", icon: "fa-child" },
    { title: "Ados (14-17 ans)", cat: "teens", icon: "fa-users" },
    { title: "Séjours à l'étranger", cat: "abroad", icon: "fa-plane" },
    { title: "Une semaine (7+ jours)", cat: "long_stay", icon: "fa-calendar-week" },
    { title: "Séjours Itinérants", cat: "itinerant", icon: "fa-route" },
    { title: "Pèlerinages & Spi", cat: "religion", icon: "fa-church" },
    { title: "Sports d'Hiver", cat: "winter", icon: "fa-snowflake" },
    { title: "Aventure & Survie", cat: "survival", icon: "fa-compass" },
    { title: "Langues Étrangères", cat: "language", icon: "fa-language" }
];

// Fonction d'injection des pubs horizontales
function injectAd() {
    const ad = document.createElement('div');
    ad.className = 'ad-box horiz';
    ad.innerHTML = `Publicité AdSense <ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-3659884670016121" data-ad-slot="BANNER" data-ad-format="auto" data-full-width-responsive="true"></ins>`;
    area.appendChild(ad);
    (adsbygoogle = window.adsbygoogle || []).push({});
}

// Génération du flux dynamique
allCats.forEach((item, i) => {
    // Pub toutes les 3 catégories
    if(i > 0 && i % 3 === 0) injectAd();

    const sectionId = `track-id-${i}`;
    const div = document.createElement('div');
    div.className = 'content-section';
    div.innerHTML = `
        <div class="section-title-box">
            <i class="fa-solid ${item.icon}"></i>
            <h3 class="section-name">${item.title}</h3>
        </div>
        <div class="carousel-view" id="${sectionId}">
            <div class="skel-card"></div><div class="skel-card"></div><div class="skel-card"></div>
        </div>
    `;
    area.appendChild(div);

    // Observer pour le chargement réel (Lazy Loading)
    const observer = new IntersectionObserver((entries) => {
        if(entries[0].isIntersecting) {
            fetch(`api/get_home_category.php?cat=${item.cat}&uid=${USER_ID}`)
                .then(r => r.text())
                .then(html => {
                    if(html.trim().length > 20) {
                        document.getElementById(sectionId).innerHTML = html;
                    } else {
                        div.style.display = 'none'; // Cache si vide
                    }
                });
            observer.disconnect();
        }
    }, { rootMargin: "400px" });
    observer.observe(div);
});

function startSearch() {
    const val = document.getElementById('main-search-input').value.trim();
    if(val) window.location.href = `recherche.php?r=${encodeURIComponent(val)}`;
}

document.getElementById('main-search-input').addEventListener('keypress', (e) => { if(e.key === 'Enter') startSearch(); });

// Init AdSense
(adsbygoogle = window.adsbygoogle || []).push({});
(adsbygoogle = window.adsbygoogle || []).push({});
</script>

<?php require_once 'partials/footer.php'; ?>