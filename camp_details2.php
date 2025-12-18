<?php
require_once 'api/config.php';
require_once 'partials/header.php';

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

<title>Détails du Séjour - ColoMap</title>

<div id="auth-modal" class="fixed inset-0 bg-gray-900 bg-opacity-80 backdrop-blur-sm flex items-center justify-center z-[60] hidden transition-opacity">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full text-center border border-gray-100">
        <h2 class="text-2xl font-bold text-[#0A112F] mb-2">Contenu réservé</h2>
        <p class="text-gray-500 mb-6">Connectez-vous pour accéder aux détails complets.</p>
        <a href="login" class="block w-full bg-[#0A112F] text-white font-bold py-3 px-4 rounded-xl hover:bg-opacity-90 transition">Se connecter</a>
        <a href="../" class="block mt-4 text-sm text-gray-400 hover:text-gray-600">Retour à l'accueil</a>
    </div>
</div>

<div id="loader" class="fixed inset-0 bg-white z-50 flex flex-col items-center justify-center">
    <div class="loader-spinner mb-4 border-4 border-blue-100 border-t-[#0A112F] rounded-full w-12 h-12 animate-spin"></div>
    <p class="text-gray-500 font-medium animate-pulse">Chargement...</p>
</div>

<div id="camp-content" class="hidden min-h-screen bg-gray-50 pb-20 font-sans">

    <div id="hero-section" class="relative min-h-[550px] w-full overflow-hidden group flex items-end">
        <div class="absolute inset-0 bg-[#0A112F]">
            <img id="hero-image" src="" class="w-full h-full object-cover opacity-60 group-hover:scale-105 transition duration-[2s] ease-out" alt="Couverture">
            <div class="absolute inset-0 bg-gradient-to-t from-[#0A112F] via-[#0A112F]/40 to-transparent"></div>
        </div>

        <div class="absolute top-10 left-10 z-20">
            <a href="../" class="flex items-center gap-2 px-5 py-2.5 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white hover:bg-white/20 transition shadow-lg text-sm font-medium">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Retour</span>
            </a>
        </div>

        <div class="relative z-10 w-full container mx-auto px-4 pb-12 pt-24">
            <div class="flex flex-col md:flex-row items-end justify-between gap-6">
                <div class="max-w-3xl">
                    <span id="hero-age-tag" class="inline-block px-4 py-1.5 rounded-xl bg-white/15 backdrop-blur-md border border-white/10 text-white text-sm font-bold tracking-wide mb-4 shadow-sm">...</span>
                    
                    <h1 id="hero-title" class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-white mb-6 leading-tight shadow-sm drop-shadow-lg"></h1>
                    
                    <div class="flex flex-wrap items-center gap-3 text-white text-sm font-medium">
                        <span class="flex items-center gap-2 bg-white/10 px-4 py-2 rounded-xl backdrop-blur-md border border-white/10">
                            <svg class="w-5 h-5 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span id="hero-location"></span>
                        </span>
                        <span class="flex items-center gap-2 bg-white/10 px-4 py-2 rounded-xl backdrop-blur-md border border-white/10">
                            <svg class="w-5 h-5 text-green-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span id="hero-dates"></span>
                        </span>
                        <span class="flex items-center gap-2 bg-white/10 px-4 py-2 rounded-xl backdrop-blur-md border border-white/10">
                            <svg class="w-5 h-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <span id="stats-views">...</span>
                        </span>
                        <span class="flex items-center gap-2 bg-white/10 px-4 py-2 rounded-xl backdrop-blur-md border border-white/10">
                            <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="currentColor"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg>
                            <span id="stats-likes">...</span>
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button id="btn-favorite" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 text-white hover:bg-white/20 transition group shadow-lg">
                        <svg class="w-6 h-6 group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.5l1.318-1.182a4.5 4.5 0 116.364 6.364L12 21l-7.682-7.682a4.5 4.5 0 010-6.364z"></path></svg>
                    </button>
                    <button id="btn-share" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 text-white hover:bg-white/20 transition shadow-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                    </button>
                    <button id="btn-calendar" class="flex items-center gap-2 px-5 py-3 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 text-white hover:bg-white/20 transition shadow-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span class="font-bold">Ajouter au calendrier</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <main class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 relative">
            
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white rounded-3xl p-8 shadow-sm border border-gray-100">
                    <h2 class="text-2xl font-extrabold text-[#0A112F] mb-6 flex items-center gap-2"><span>À propos du séjour</span></h2>
                    <div class="relative">
                        <div id="camp-desc-container" class="prose max-w-none text-gray-700 leading-relaxed text-lg max-h-40 overflow-hidden transition-all duration-500 ease-in-out">
                            <div id="camp-desc"></div>
                        </div>
                        <div id="desc-gradient" class="absolute bottom-0 left-0 w-full h-16 bg-gradient-to-t from-white to-transparent pointer-events-none"></div>
                    </div>
                    <button id="btn-see-more" class="mt-4 text-[#0A112F] font-bold hover:opacity-80 flex items-center gap-1 transition-colors">
                        Voir plus <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>

                <div id="video-wrapper" class="hidden">
                    <h3 class="text-xl font-bold text-[#0A112F] mb-4 px-2">Vidéo de présentation</h3>
                    <div class="bg-black rounded-3xl overflow-hidden shadow-lg aspect-video relative group">
                        <iframe id="video-frame" class="w-full h-full" src="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                        <h3 class="font-bold text-[#0A112F] mb-4">Détails pratiques</h3>
                        <div class="space-y-4">
                            <div class="flex items-start gap-4">
                                <div class="bg-blue-50 p-3 rounded-2xl text-[#0A112F]"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></div>
                                <div><h4 class="font-bold text-gray-900 text-sm">Organisme</h4><p id="info-organizer" class="text-gray-600 text-sm"></p></div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="bg-orange-50 p-3 rounded-2xl text-orange-600"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                                <div><h4 class="font-bold text-gray-900 text-sm">Durée</h4><p id="info-duration" class="text-gray-600 text-sm"></p></div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-3xl p-2 shadow-sm border border-gray-100 overflow-hidden">
                        <iframe id="map-frame" class="w-full h-full min-h-[200px] rounded-2xl" frameborder="0" style="border:0" loading="lazy" allowfullscreen></iframe>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="sticky top-[100px] space-y-6">
                    
                    <div id="booking-card" class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100 ring-1 ring-black/5">
                        <div class="bg-[#0A112F] p-8 text-white text-center relative overflow-hidden">
                            <div class="relative z-10">
                                <p class="text-xs opacity-80 uppercase font-bold tracking-widest mb-2">À partir de</p>
                                <p id="card-price" class="text-5xl font-extrabold tracking-tight"></p>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <div class="mb-6 bg-gray-50 p-4 rounded-xl border border-gray-100">
                                <div class="flex justify-between text-sm font-bold mb-2">
                                    <span class="text-gray-600">Remplissage</span>
                                    <span id="progress-text" class="text-[#0A112F]">...</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                    <div id="progress-bar" class="bg-[#0A112F] h-2.5 rounded-full transition-all duration-1000 ease-out" style="width: 0%"></div>
                                </div>
                            </div>

                            <div class="space-y-4 mb-8 text-sm px-2">
                                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                                    <span class="text-gray-500">Âge</span>
                                    <span id="card-age" class="font-bold text-gray-900"></span>
                                </div>
                                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                                    <span class="text-gray-500">Dates</span>
                                    <span id="card-dates" class="font-bold text-gray-900"></span>
                                </div>
                            </div>

                            <div id="cta-container"></div>
                        </div>
                    </div>

                    <div id="orga-card" class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100 ring-1 ring-black/5">
                        <div class="bg-[#0A112F] p-4 text-white text-center">
                            <p class="text-xs opacity-90 uppercase font-bold tracking-widest">Organisé par</p>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center gap-4 mb-6">
                                <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center text-2xl font-bold text-[#0A112F]">
                                    <span id="orga-initial">?</span>
                                </div>
                                <div class="overflow-hidden w-full">
                                    <p id="orga-name" class="font-bold text-gray-900 text-lg truncate"></p>
                                </div>
                            </div>
                            
                            <div id="contact-container" class="space-y-3"></div>
                            <div id="website-container"></div>
                        </div>
                    </div>

                    <div class="bg-gray-100 rounded-2xl p-4 text-center border border-gray-200 min-h-[250px] flex items-center justify-center relative overflow-hidden">
                        <span class="absolute top-2 right-2 text-[10px] text-gray-400 uppercase bg-gray-200 px-1 rounded">Publicité</span>
                        <div class="text-gray-400 text-sm">Espace Publicitaire</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const isLoggedIn = <?= json_encode($is_logged_in); ?>;
    const campId = <?= json_encode($camp_id); ?>;
    const userFavorites = <?= json_encode($user_favorites); ?>;

    const loader = document.getElementById('loader');
    const authModal = document.getElementById('auth-modal');
    const content = document.getElementById('camp-content');

    if (!isLoggedIn) {
        loader.classList.add('hidden');
        authModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        return;
    }

    if (!campId) {
        loader.innerHTML = '<div class="text-center p-10"><h1 class="text-2xl font-bold text-red-500">Séjour introuvable</h1><a href="../" class="text-[#0A112F] underline mt-4 block">Retour accueil</a></div>';
        return;
    }

    try {
        const response = await fetch(`api/get_camp_details?id=${campId}`);
        if (!response.ok) throw new Error('Erreur API');
        const camp = await response.json();

        // HERO
        if(camp.image_url) document.getElementById('hero-image').src = camp.image_url;
        document.getElementById('hero-title').textContent = camp.nom;
        document.getElementById('hero-location').textContent = camp.ville;
        document.getElementById('hero-age-tag').textContent = `${camp.age_min} - ${camp.age_max} ANS`;

        // STATS
        document.getElementById('stats-views').textContent = camp.vues || 0;
        document.getElementById('stats-likes').textContent = camp.total_likes || 0;

        const d1 = new Date(camp.date_debut);
        const d2 = new Date(camp.date_fin);
        const dateStr = d1.toLocaleDateString('fr-FR', {day:'numeric', month:'short'}) + ' - ' + d2.toLocaleDateString('fr-FR', {day:'numeric', month:'short', year:'numeric'});
        document.getElementById('hero-dates').textContent = dateStr;
        document.title = `${camp.nom} - ColoMap`;

        // DESCRIPTION
        const descEl = document.getElementById('camp-desc');
        const descContainer = document.getElementById('camp-desc-container');
        const btnMore = document.getElementById('btn-see-more');
        const gradient = document.getElementById('desc-gradient');

        descEl.innerHTML = camp.description ? camp.description.replace(/\n/g, '<br>') : 'Aucune description.';
        
        btnMore.addEventListener('click', () => {
            if(descContainer.classList.contains('max-h-40')) {
                descContainer.classList.remove('max-h-40');
                gradient.classList.add('hidden');
                btnMore.innerHTML = `Voir moins <svg class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>`;
            } else {
                descContainer.classList.add('max-h-40');
                gradient.classList.remove('hidden');
                btnMore.innerHTML = `Voir plus <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>`;
            }
        });

        // INFO
        document.getElementById('info-organizer').textContent = camp.orga_nom || 'Non spécifié';
        const diffTime = Math.abs(d2 - d1);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; 
        document.getElementById('info-duration').textContent = `${diffDays} jours / ${diffDays-1} nuits`;

        // VIDEO
        if(camp.video_url) {
            let embedUrl = camp.video_url;
            if(embedUrl.includes('youtube.com/watch')) embedUrl = embedUrl.replace('watch?v=', 'embed/');
            else if(embedUrl.includes('youtu.be/')) embedUrl = embedUrl.replace('youtu.be/', 'youtube.com/embed/');
            document.getElementById('video-frame').src = embedUrl;
            document.getElementById('video-wrapper').classList.remove('hidden');
        }

        // MAP
        const address = `${camp.adresse}, ${camp.code_postal} ${camp.ville}`;
        const mapUrl = `https://maps.google.com/maps?q=${encodeURIComponent(address)}&t=&z=13&ie=UTF8&iwloc=&output=embed`;
        document.getElementById('map-frame').src = mapUrl;

        // SIDEBAR
        document.getElementById('card-price').textContent = `${camp.prix}€`;
        document.getElementById('card-age').textContent = `${camp.age_min} - ${camp.age_max} ans`;
        document.getElementById('card-dates').textContent = dateStr;

        // BARRE PROGRESSION
        const percentFilled = camp.percent_filled || 0;
        const pb = document.getElementById('progress-bar');
        const pt = document.getElementById('progress-text');
        setTimeout(() => { pb.style.width = `${percentFilled}%`; }, 300);
        pt.textContent = `${percentFilled}% Rempli`;

        // CTA
        const ctaBox = document.getElementById('cta-container');
        let ctaHtml = '';
        const places = camp.places_restantes || 0;

        if(camp.inscription_en_ligne == 1 && places > 0) {
            ctaHtml = `
                <a href="inscription?camp_t=${camp.token}" class="flex items-center justify-center gap-2 w-full bg-[#0A112F] hover:bg-opacity-90 text-white font-bold py-4 px-6 rounded-xl shadow-lg transform transition hover:scale-[1.02] text-center text-lg">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                    Réserver ma place
                </a>`;
        } else if (camp.inscription_hors_ligne == 1 && camp.lien_externe) {
            ctaHtml = `<a href="${camp.lien_externe}" target="_blank" class="block w-full bg-[#0A112F] hover:bg-opacity-90 text-white font-bold py-3 px-6 rounded-xl text-center shadow transition">Réserver ma place</a>`;
        } else if (places === 0) {
            ctaHtml = `<button disabled class="block w-full bg-gray-100 text-gray-400 font-bold py-3 px-6 rounded-xl cursor-not-allowed text-center border border-gray-200">Inscriptions closes</button>`;
        }
        ctaBox.innerHTML = ctaHtml;

        // CONTACT & ORGANISATEUR
        document.getElementById('orga-name').textContent = camp.orga_nom;
        document.getElementById('orga-initial').textContent = camp.orga_nom ? camp.orga_nom.charAt(0).toUpperCase() : '?';
        
        // BOUTON CONTACT (SOLID #0A112F)
        if(camp.organisateur_user_id) {
            const btnContact = document.createElement('button');
            btnContact.className = "w-full bg-[#0A112F] text-white font-bold py-3 px-4 rounded-xl hover:bg-opacity-90 transition shadow-lg flex items-center justify-center gap-2 group";
            btnContact.innerHTML = `<svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg> Contacter l'organisateur`;
            btnContact.onclick = () => contactOrganisateur(camp.organisateur_user_id, camp.id);
            document.getElementById('contact-container').appendChild(btnContact);
        }

        // BOUTON SITE WEB (LIGHT BUTTON)
        if(camp.orga_website) {
            document.getElementById('website-container').innerHTML = `
                <a href="${camp.orga_website}" target="_blank" class="block w-full mt-3 bg-white border border-gray-200 text-gray-700 font-bold py-3 px-4 rounded-xl hover:bg-gray-50 text-center transition shadow-sm flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    Voir le site de l'organisme
                </a>`;
        }

        // FAVORIS UI
        const btnFav = document.getElementById('btn-favorite');
        let isFav = userFavorites.includes(camp.id);
        const updateFavUI = () => {
            if(isFav) {
                btnFav.classList.replace('bg-white/10', 'bg-red-500/80');
            } else {
                btnFav.classList.replace('bg-red-500/80', 'bg-white/10');
            }
        };
        updateFavUI();

        btnFav.addEventListener('click', async () => {
            try {
                await fetch('api/toggle_favorite.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({campId: camp.id}) });
                isFav = !isFav;
                updateFavUI();
            } catch(e) {}
        });

        // PARTAGE
        document.getElementById('btn-share').addEventListener('click', () => {
            if(navigator.share) {
                navigator.share({ title: `Camp ${camp.nom}`, text: `Regarde ce séjour à ${camp.ville} !`, url: window.location.href }).catch(console.error);
            } else {
                navigator.clipboard.writeText(window.location.href);
                alert('Lien copié dans le presse-papier !');
            }
        });

        // CALENDRIER
        document.getElementById('btn-calendar').addEventListener('click', () => {
            const startStr = d1.toISOString().replace(/-|:|\.\d\d\d/g, "");
            const endStr = d2.toISOString().replace(/-|:|\.\d\d\d/g, "");
            const title = encodeURIComponent(`Séjour ColoMap: ${camp.nom}`);
            const details = encodeURIComponent(`Séjour à ${camp.ville}. Plus d'infos: ${window.location.href}`);
            const location = encodeURIComponent(`${camp.adresse}, ${camp.ville}`);
            const gCalUrl = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${title}&dates=${startStr}/${endStr}&details=${details}&location=${location}&sf=true&output=xml`;
            window.open(gCalUrl, '_blank');
        });

        loader.classList.add('hidden');
        content.classList.remove('hidden');

    } catch (e) {
        console.error(e);
        loader.innerHTML = `<div class="p-8 text-center"><p class="text-red-600 font-bold text-lg mb-2">Erreur technique</p><p class="text-gray-600 text-sm bg-gray-100 p-4 rounded">${e.message}</p><a href="../" class="text-[#0A112F] underline mt-4 block">Retour accueil</a></div>`;
    }
});

async function contactOrganisateur(targetUserId, campId) {
    if(!targetUserId || !campId) return alert("Erreur données.");
    document.body.style.cursor = 'wait';
    try {
        const res = await fetch('api/start_conversation.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ organisateurId: targetUserId, campId: campId }) });
        const data = await res.json();
        document.body.style.cursor = 'default';
        if(data.conversationId) window.location.href = `messagerie?conv_id=${data.conversationId}`;
        else alert(data.error || "Erreur");
    } catch(e) { 
        document.body.style.cursor = 'default'; 
        alert("Erreur réseau"); 
    }
}
</script>

<?php require_once 'partials/footer.php'; ?>