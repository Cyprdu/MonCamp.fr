<?php
require_once 'api/config.php';
require_once 'partials/header.php';

// --- LOGIQUE PHP INCHANGÉE ---
$is_logged_in = isset($_SESSION['user']);
$user_favorites = $_SESSION['user']['favorites'] ?? [];
$token = $_GET['t'] ?? null;
$id_param = $_GET['id'] ?? null;
$camp_id = null;

try {
    if ($token) {
        $stmt = $pdo->prepare("SELECT id FROM camps WHERE token = ?");
        $stmt->execute([$token]);
        $res = $stmt->fetch();
        if ($res) $camp_id = $res['id'];
    } elseif ($id_param) {
        $stmt = $pdo->prepare("SELECT id, prive FROM camps WHERE id = ?");
        $stmt->execute([$id_param]);
        $res = $stmt->fetch();
        if ($res) {
            if ($res['prive'] == 1 && (!isset($_SESSION['user']['is_admin']) || !$_SESSION['user']['is_admin'])) {
                echo "<script>window.location.href='../';</script>";
                exit;
            }
            $camp_id = $res['id'];
        }
    }
} catch (Exception $e) {}
?>

<title>Détails du Séjour</title>

<div id="loader" class="fixed inset-0 bg-white z-[999] flex flex-col items-center justify-center transition-all duration-500">
    <div class="w-12 h-12 border-4 border-gray-200 border-t-[#162B4E] rounded-full animate-spin"></div>
</div>

<div id="camp-content" class="hidden min-h-screen bg-white font-sans text-[#162B4E]">

    <header class="relative w-full h-[500px] bg-gray-900">
        <div class="absolute inset-0">
            <img id="hero-image" src="" class="w-full h-full object-cover opacity-90" alt="Couverture">
            <div class="absolute inset-0 bg-gradient-to-r from-black/50 via-transparent to-transparent"></div>
        </div>

        <div class="relative container mx-auto px-4 h-full flex items-center">
            <div class="grid grid-cols-1 lg:grid-cols-12 w-full gap-8 items-center">
                
                <div class="lg:col-span-7 text-white space-y-4 pt-10">
                    <nav class="text-xs text-gray-300 mb-4 flex gap-1">
                        <a href="../" class="hover:text-white">Home</a> <span>&gt;</span>
                        <span>Détail séjour</span> <span>&gt;</span>
                        <span id="breadcrumb-title" class="text-white font-semibold">...</span>
                    </nav>

                    <h1 id="hero-title" class="text-5xl md:text-6xl font-bold leading-tight shadow-black drop-shadow-lg"></h1>
                    
                    <div class="flex items-center gap-4 text-sm font-semibold uppercase tracking-wider">
                        <span id="hero-age-tag"></span>
                    </div>

                    <div class="flex items-center gap-2 text-sm opacity-90 mt-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <span id="stats-views-hero"></span> vues
                    </div>
                </div>

                <div class="lg:col-span-5 flex justify-end">
                    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm text-[#162B4E]">
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-[#162B4E] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span id="info-location" class="font-medium text-lg"></span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-[#162B4E] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span id="info-duration" class="font-medium text-lg"></span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-[#162B4E] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span id="info-date" class="font-medium text-lg"></span>
                            </li>
                            <li class="flex items-start gap-3 pt-2 border-t border-gray-100 mt-2">
                                <svg class="w-6 h-6 text-[#162B4E] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <div>
                                    <p class="text-sm text-gray-500">organisé par</p>
                                    <p id="info-organizer" class="font-bold leading-tight"></p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="bg-gray-50 border-b border-gray-200">
        <div class="container mx-auto px-4 py-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center">
                <?php if ($is_logged_in): ?>
                <button id="btn-favorite" class="flex items-center gap-2 bg-[#162B4E] text-white px-4 py-2 rounded-l-md font-medium hover:bg-blue-900 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/></svg>
                    J'aime
                </button>
                <div class="bg-gray-200 text-gray-700 px-3 py-2 rounded-r-md font-bold border-l border-gray-300" id="stats-likes">0</div>
                <?php endif; ?>
            </div>

            <div class="flex gap-3">
                <button id="btn-share" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md font-medium hover:bg-gray-50 transition">
                    Partager
                </button>
                <button id="btn-calendar" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md font-medium hover:bg-gray-50 flex items-center gap-2 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Ajouter au calendrier
                </button>
            </div>
        </div>
    </div>

    <main class="container mx-auto px-4 py-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <div class="lg:col-span-7 space-y-8">
                <div>
                    <h2 id="desc-title" class="text-2xl font-bold text-[#162B4E] mb-4">Pourquoi venir à ce séjour ?</h2>
                    <div id="camp-desc-container" class="prose max-w-none text-gray-700 leading-relaxed text-justify relative overflow-hidden transition-all duration-300" style="max-height: 200px;">
                        <p id="camp-desc"></p>
                        <div id="desc-fade" class="absolute bottom-0 left-0 w-full h-16 bg-gradient-to-t from-white to-transparent"></div>
                    </div>
                    <button id="btn-see-more" class="mt-2 text-[#162B4E] font-bold text-sm hover:underline">Voir plus</button>
                </div>

                <div id="video-wrapper" class="hidden mt-6">
                    <iframe id="video-frame" class="w-full h-[350px] rounded-lg shadow-sm" src="" frameborder="0" allowfullscreen></iframe>
                </div>

                <div class="pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-bold text-[#162B4E] mb-4">Moyens de paiement acceptés</h3>
                    <div class="flex flex-wrap gap-6 text-sm font-medium text-gray-700">
                        <div class="flex items-center gap-2"><span class="font-bold text-xl">CB</span> Carte Bancaire</div>
                        <div class="flex items-center gap-2"><svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h18v18H3V3zm16 16V5H5v14h14zM7 7h10v2H7V7zm0 4h10v2H7v-2zm0 4h7v2H7v-2z"/></svg> Chèques Vacances</div>
                        <div class="flex items-center gap-2"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg> Chèques</div>
                        <div class="flex items-center gap-2"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg> Espèces</div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-3">
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden sticky top-4">
                    <div id="cta-header">
                         </div>

                    <div class="p-4 space-y-4">
                        <div id="progress-container" class="hidden">
                             <div class="h-2 w-full bg-gray-200 rounded-full overflow-hidden">
                                <div id="progress-bar" class="h-full bg-[#162B4E] rounded-full" style="width: 0%"></div>
                             </div>
                             <p class="text-right text-xs text-gray-500 mt-1"><span id="progress-text">0%</span> rempli</p>
                        </div>

                        <div class="flex justify-between items-baseline border-b border-gray-100 pb-4">
                            <span class="text-lg font-medium text-gray-700">Prix</span>
                            <span id="card-price" class="text-2xl font-bold text-[#162B4E]"></span>
                        </div>

                        <div class="rounded-md overflow-hidden border border-gray-200 h-32 relative">
                            <iframe id="map-frame" class="w-full h-full" frameborder="0" loading="lazy"></iframe>
                        </div>

                        <div id="contact-container"></div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-gray-100 rounded-lg h-full min-h-[400px] flex items-center justify-center border border-gray-200 relative overflow-hidden">
                    <span class="text-gray-400 font-medium">Espace de pub</span>
                    <div id="ad-container" class="absolute inset-0 w-full h-full flex items-center justify-center"></div>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const config = {
        isLoggedIn: <?= json_encode($is_logged_in); ?>,
        campId: <?= json_encode($camp_id); ?>,
        userFavorites: <?= json_encode($user_favorites); ?>
    };

    const loader = document.getElementById('loader');
    const content = document.getElementById('camp-content');

    if (!config.campId) { window.location.href = '../'; return; }

    try {
        const response = await fetch(`api/get_camp_details?id=${config.campId}`);
        if (!response.ok) throw new Error('API Error');
        const camp = await response.json();

        // --- 1. HERO ---
        document.getElementById('breadcrumb-title').textContent = camp.nom;
        document.getElementById('hero-title').textContent = camp.nom;
        document.getElementById('hero-age-tag').textContent = `${camp.age_min} - ${camp.age_max} ANS`;
        document.getElementById('stats-views-hero').textContent = camp.vues || 0;
        document.getElementById('stats-likes').textContent = camp.total_likes || 0;
        
        if(camp.image_url) document.getElementById('hero-image').src = camp.image_url;

        // Info Card
        document.getElementById('info-location').textContent = camp.ville;
        
        const d1 = new Date(camp.date_debut);
        const d2 = new Date(camp.date_fin);
        const diffTime = Math.abs(d2 - d1);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        document.getElementById('info-duration').textContent = `${diffDays} Jours`;
        document.getElementById('info-date').textContent = `départ ${d1.toLocaleDateString('fr-FR', {day:'numeric', month:'short'})}`;
        document.getElementById('info-organizer').textContent = camp.orga_nom || 'Non spécifié';

        // --- 2. DESCRIPTION ---
        document.getElementById('desc-title').textContent = `Pourquoi venir à ${camp.nom} ?`;
        const descEl = document.getElementById('camp-desc');
        const descCont = document.getElementById('camp-desc-container');
        const descFade = document.getElementById('desc-fade');
        const btnMore = document.getElementById('btn-see-more');

        descEl.innerHTML = camp.description ? camp.description.replace(/\n/g, '<br>') : '...';

        btnMore.addEventListener('click', () => {
            if (descCont.style.maxHeight) {
                descCont.style.maxHeight = null;
                descFade.classList.add('hidden');
                btnMore.textContent = 'Voir moins';
            } else {
                descCont.style.maxHeight = '200px';
                descFade.classList.remove('hidden');
                btnMore.textContent = 'Voir plus';
            }
        });

        if(camp.video_url) {
            let v = camp.video_url.replace('watch?v=', 'embed/').replace('youtu.be/', 'youtube.com/embed/');
            document.getElementById('video-frame').src = v;
            document.getElementById('video-wrapper').classList.remove('hidden');
        }

        // --- 3. RESERVATION CARD ---
        document.getElementById('card-price').textContent = `${camp.prix}€`;
        
        // Map
        const q = encodeURIComponent(`${camp.adresse} ${camp.ville} ${camp.code_postal}`);
        document.getElementById('map-frame').src = `https://maps.google.com/maps?q=${q}&t=&z=13&ie=UTF8&iwloc=&output=embed`;

        // Progress
        if(camp.inscription_en_ligne == 1) {
            const pct = camp.percent_filled || 0;
            document.getElementById('progress-container').classList.remove('hidden');
            document.getElementById('progress-text').textContent = `${pct}%`;
            document.getElementById('progress-bar').style.width = `${pct}%`;
        }

        // Bouton Réserver (Header de la carte)
        const ctaHeader = document.getElementById('cta-header');
        const places = camp.places_restantes || 0;
        let ctaBtn = '';

        if (camp.lien_externe) {
            ctaBtn = `<a href="${camp.lien_externe}" target="_blank" class="block w-full bg-[#162B4E] text-white font-bold text-center py-3 hover:bg-blue-900 transition uppercase">Réserver</a>`;
        } else if (camp.inscription_en_ligne == 1) {
            if (places > 0) {
                 if (config.isLoggedIn) {
                    ctaBtn = `<a href="inscription?camp_t=${camp.token}" class="block w-full bg-[#162B4E] text-white font-bold text-center py-3 hover:bg-blue-900 transition uppercase">Réserver</a>`;
                 } else {
                    ctaBtn = `<a href="login.php?redirect=${encodeURIComponent(window.location.href)}" class="block w-full bg-[#162B4E] text-white font-bold text-center py-3 hover:bg-blue-900 transition uppercase">Se connecter</a>`;
                 }
            } else {
                ctaBtn = `<button disabled class="block w-full bg-gray-300 text-gray-500 font-bold text-center py-3 cursor-not-allowed uppercase">Complet</button>`;
            }
        }
        ctaHeader.innerHTML = ctaBtn;

        // Bouton Contact
        const contactBox = document.getElementById('contact-container');
        if(camp.organisateur_user_id && config.isLoggedIn) {
            const btn = document.createElement('button');
            btn.className = "w-full py-2 rounded bg-[#162B4E] text-white text-sm font-medium hover:bg-blue-900 flex items-center justify-center gap-2";
            btn.innerHTML = `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> Contacter l'organisateur`;
            btn.onclick = () => contactOrganisateur(camp.organisateur_user_id, camp.id);
            contactBox.appendChild(btn);
        }

        // --- 4. FAVORIS & ACTIONS ---
        document.getElementById('btn-share').onclick = () => {
             if(navigator.share) navigator.share({title: camp.nom, url: window.location.href});
             else alert("URL copiée !");
        };

        if(config.isLoggedIn) {
             // Logic favoris simple
             const btnFav = document.getElementById('btn-favorite');
             let isFav = config.userFavorites.includes(camp.id);
             // Init state visual logic...
             btnFav.addEventListener('click', () => {
                 fetch('api/toggle_favorite.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({campId: camp.id}) });
                 // update UI optimistically
                 const countEl = document.getElementById('stats-likes');
                 let count = parseInt(countEl.textContent);
                 countEl.textContent = isFav ? count - 1 : count + 1;
                 isFav = !isFav;
             });
        }

        // --- 5. ADS & DISPLAY ---
        initAds();
        loader.classList.add('opacity-0', 'pointer-events-none');
        content.classList.remove('hidden');

    } catch (e) { console.error(e); }
});

function initAds() {
    const c = document.getElementById('ad-container');
    if(!c) return;
    const s = document.createElement('script');
    s.src = "https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3659884670016121"; s.async=true; s.crossOrigin="anonymous";
    const i = document.createElement('ins');
    i.className="adsbygoogle"; i.style.display="block"; i.style.width="100%"; i.style.height="100%";
    i.setAttribute('data-ad-client','ca-pub-3659884670016121'); i.setAttribute('data-ad-slot','7384171500'); i.setAttribute('data-ad-format','auto'); i.setAttribute('data-full-width-responsive','true');
    c.appendChild(s); c.appendChild(i);
    try{ (window.adsbygoogle = window.adsbygoogle || []).push({}); }catch(e){}
}

async function contactOrganisateur(u, c) {
    if(!u || !c) return;
    try {
        const r = await fetch('api/start_conversation.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ organisateurId: u, campId: c }) });
        const d = await r.json();
        if(d.conversationId) window.location.href = `messagerie?conv_id=${d.conversationId}`;
    } catch(e) {}
}
</script>

<?php require_once 'partials/footer.php'; ?>