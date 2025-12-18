<?php
/**
 * FICHIER : index.php
 * VERSION CORRIGÉE (Centrage + Debug Catégories)
 */
require_once 'api/config.php';
require_once 'partials/header.php';

// --- RÉCUPÉRATION DES DONNÉES UTILISATEUR ---
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

    body { font-family: 'Inter', sans-serif; margin: 0; background-color: #fff; }

    /* --- CORRECTION CENTRAGE --- */
    .app-layout {
        display: flex;
        justify-content: center;
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
    }

    .side-ads-column { width: 180px; flex-shrink: 0; padding-top: 150px; }

    .center-content-column {
        flex: 1;
        max-width: 1200px;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: center; /* Centre les éléments enfants horizontalement */
    }

    /* Hero Section centrée */
    .hero {
        width: 100%;
        padding: 60px 20px;
        display: flex;
        flex-direction: column;
        align-items: center; /* Centre le texte et la barre */
        text-align: center;
    }

    .greeting {
        font-family: 'Poppins', sans-serif;
        font-size: clamp(2rem, 5vw, 3.2rem);
        font-weight: 800;
        margin-bottom: 30px;
    }
    .greeting span { color: var(--primary); }

    .search-wrapper {
        background: white;
        border: 1px solid #ddd;
        border-radius: 50px;
        padding: 8px 10px 8px 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        width: 100%;
        max-width: 700px; /* Largeur max de la barre */
    }

    .search-input { border: none; outline: none; flex: 1; font-size: 1.1rem; padding: 10px 0; }
    .search-btn { background: var(--primary); color: white; width: 50px; height: 50px; border-radius: 50%; border: none; cursor: pointer; }

    /* --- SECTIONS --- */
    .section-wrapper { width: 100%; margin: 40px 0; padding: 0 20px; box-sizing: border-box; }
    .section-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; justify-content: flex-start; width: 100%; }
    .section-title { font-family: 'Poppins', sans-serif; font-size: 1.6rem; font-weight: 700; }

    .carousel-track { display: flex; gap: 20px; overflow-x: auto; scrollbar-width: none; padding-bottom: 10px; }
    .carousel-track::-webkit-scrollbar { display: none; }

    /* --- CARDS --- */
    .camp-card { flex: 0 0 280px; text-decoration: none; color: inherit; }
    .img-container { width: 100%; aspect-ratio: 16/10; border-radius: var(--radius); overflow: hidden; background: #f0f0f0; }
    .img-container img { width: 100%; height: 100%; object-fit: cover; }

    /* --- ADS --- */
    .ad-placeholder { background: #fafafa; border: 2px dashed #ddd; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #ccc; text-transform: uppercase; font-size: 0.7rem; }
    .ad-placeholder.vertical { width: 160px; height: 600px; position: sticky; top: 100px; }
    .ad-placeholder.horizontal { width: 100%; height: 120px; margin: 30px 0; }

    @media (max-width: 1400px) { .side-ads-column { display: none; } }
</style>

<div class="app-layout">
    
    <aside class="side-ads-column">
        <div class="ad-placeholder vertical">Pub Latérale</div>
    </aside>

    <main class="center-content-column">
        
        <section class="hero">
            <h1 class="greeting">Bienvenue, <span><?= $user_prenom ?></span> !</h1>
            <div class="search-wrapper">
                <input type="text" id="main-search" class="search-input" placeholder="Trouvez la colo idéale...">
                <button class="search-btn" onclick="executeSearch()"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </section>

        <div id="main-feed" style="width: 100%;">
            
            <div class="section-wrapper">
                <div class="section-header"><i class="fa-solid fa-star" style="color: gold;"></i><h2 class="section-title">Coups de cœur</h2></div>
                <div class="carousel-track">
                    <?php
                    $stmt = $pdo->query("SELECT * FROM camps WHERE date_debut > NOW() ORDER BY boost_points DESC LIMIT 6");
                    while($c = $stmt->fetch()):
                    ?>
                        <a href="camp_details.php?id=<?= $c['id'] ?>" class="camp-card">
                            <div class="img-container"><img src="<?= getCampImageUrl($c['image_url']) ?>"></div>
                            <div style="font-weight:600; margin-top:10px;"><?= htmlspecialchars($c['nom']) ?></div>
                            <div style="color:var(--gray)"><?= htmlspecialchars($c['ville']) ?></div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>

            <div id="dynamic-sections"></div>
        </div>

    </main>

    <aside class="side-ads-column">
        <div class="ad-placeholder vertical">Pub Latérale</div>
    </aside>

</div>

<script>
const USER_ID = <?= $user_id ?>;
const categories = [
    { title: "Dernière minute", cat: "last_minute", icon: "fa-clock" },
    { title: "Petits prix", cat: "cheap", icon: "fa-tag" },
    { title: "Bord de mer", cat: "sea", icon: "fa-umbrella-beach" },
    { title: "Montagne", cat: "mountain", icon: "fa-mountain" },
    { title: "Nature & Forêt", cat: "nature", icon: "fa-tree" },
    { title: "Gaming", cat: "gaming", icon: "fa-gamepad" },
    { title: "Ados (14-17 ans)", cat: "teens", icon: "fa-users" },
    { title: "Sports d'hiver", cat: "winter", icon: "fa-snowflake" }
];

const dynamicContainer = document.getElementById('dynamic-sections');

categories.forEach((item, index) => {
    // Création de la section
    const section = document.createElement('div');
    section.className = 'section-wrapper';
    const sectionId = `track-${index}`;
    
    section.innerHTML = `
        <div class="section-header"><i class="fa-solid ${item.icon}"></i><h3 class="section-title">${item.title}</h3></div>
        <div class="carousel-track" id="${sectionId}">
            <div style="min-width:280px; height:180px; background:#f9f9f9; border-radius:16px;"></div>
            <div style="min-width:280px; height:180px; background:#f9f9f9; border-radius:16px;"></div>
        </div>
    `;
    dynamicContainer.appendChild(section);

    // Insertion Pub
    if(index % 3 === 0) {
        const ad = document.createElement('div');
        ad.className = 'ad-placeholder horizontal';
        ad.innerHTML = 'Publicité AdSense';
        dynamicContainer.appendChild(ad);
    }

    // Chargement des données au scroll
    const obs = new IntersectionObserver((entries) => {
        if(entries[0].isIntersecting) {
            console.log("Chargement de : " + item.cat); // Debug console
            fetch(`api/get_home_category.php?cat=${item.cat}&uid=${USER_ID}`)
                .then(r => r.text())
                .then(html => {
                    if(html.trim() === "") {
                        section.style.display = 'none'; // Cache si vide
                    } else {
                        document.getElementById(sectionId).innerHTML = html;
                    }
                })
                .catch(err => console.error("Erreur chargement catégorie", err));
            obs.disconnect();
        }
    }, { rootMargin: "200px" });
    obs.observe(section);
});

function executeSearch() {
    const val = document.getElementById('main-search').value.trim();
    if(val) window.location.href = `recherche.php?r=${encodeURIComponent(val)}`;
}
</script>

<?php require_once 'partials/footer.php'; ?>