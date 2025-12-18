// assets/js/index.js

const state = {
    loadedCategoriesCount: 2, // Les 2 premières sont chargées en PHP
    isLoading: false,
    hasMore: true,
    categoryMap: [
        { title: "Aventure en montagne", type: "Montagne,Aventure", duration: '>5', sort: 'newest' },
        { title: "Camps créatifs", type: "Arts plastiques,Cinéma,Musique", sort: 'newest' },
        { title: "Séjours détente", type: "Bien-être / Relaxation,Nature", sort: 'cheapest' },
        // ... Ajoutez jusqu'à 150 objets pour des catégories ultra sophistiquées
    ]
};

// Fonction pour initialiser les carrousels (flèches et défilement)
function initCarousels() {
    document.querySelectorAll('.carousel-container, .category-carousel').forEach(container => {
        const wrapper = container.querySelector('.carousel-wrapper');
        const prevBtn = container.querySelector('.prev-btn');
        const nextBtn = container.querySelector('.next-btn');
        if (!wrapper || !prevBtn || !nextBtn) return;

        // Cacher/afficher les flèches si tout le contenu est visible (simplifié)
        const updateArrows = () => {
            prevBtn.style.display = (wrapper.scrollLeft > 0) ? 'block' : 'none';
            nextBtn.style.display = (wrapper.scrollWidth > wrapper.clientWidth + wrapper.scrollLeft) ? 'block' : 'none';
        };

        prevBtn.addEventListener('click', () => {
            wrapper.scrollBy({ left: -wrapper.clientWidth * 0.8, behavior: 'smooth' });
            setTimeout(updateArrows, 300);
        });

        nextBtn.addEventListener('click', () => {
            wrapper.scrollBy({ left: wrapper.clientWidth * 0.8, behavior: 'smooth' });
            setTimeout(updateArrows, 300);
        });
        
        wrapper.addEventListener('scroll', updateArrows);
        window.addEventListener('resize', updateArrows);
        updateArrows(); // Initial check
    });
}

// Fonction de chargement de la prochaine catégorie via AJAX
function loadNextCategories() {
    if (state.isLoading || !state.hasMore) return;
    
    state.isLoading = true;
    const startIndex = state.loadedCategoriesCount;
    const numToLoad = 3; // Charger 3 nouvelles catégories à la fois

    const categoriesToLoad = state.categoryMap.slice(startIndex, startIndex + numToLoad);

    if (categoriesToLoad.length === 0) {
        state.hasMore = false;
        state.isLoading = false;
        return;
    }

    // Mélanger pour l'effet "aléatoire"
    categoriesToLoad.sort(() => Math.random() - 0.5);

    fetch('api/get_camps_data.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ categories: categoriesToLoad })
    })
    .then(response => response.text())
    .then(html => {
        const container = document.getElementById('lazy-load-container');
        // Insérer une publicité toutes les X catégories (ex: après 6 catégories)
        if (state.loadedCategoriesCount % 6 === 0) {
            html += '<div class="adsense-slot ad-lazy-load"></div>';
        }
        container.insertAdjacentHTML('beforeend', html);
        state.loadedCategoriesCount += categoriesToLoad.length;
        state.isLoading = false;
        initCarousels(); // Ré-initialiser les carousels pour les nouveaux éléments
        state.hasMore = state.loadedCategoriesCount < state.categoryMap.length;
    })
    .catch(error => {
        console.error('Error loading categories:', error);
        state.isLoading = false;
    });
}

// Observer le scroll pour le lazy loading
function setupLazyLoading() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            // Déclenche le chargement si l'utilisateur atteint le bas de la page ou le dernier élément chargé
            if (entry.isIntersecting && state.hasMore) {
                loadNextCategories();
            }
        });
    }, { threshold: 0.1 }); // Seuil de 10% de visibilité

    // Observez le conteneur principal du contenu dynamique
    observer.observe(document.getElementById('lazy-load-container'));
}

document.addEventListener('DOMContentLoaded', () => {
    initCarousels();
    setupLazyLoading();
});