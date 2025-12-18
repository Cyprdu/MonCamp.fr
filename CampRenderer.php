// utils/CampRenderer.php (Extrait)

function get_icon($type) {
    $icons = [
        'localisation' => '<i class="fas fa-map-marker-alt" aria-hidden="true"></i>', // Ancien 📍
        'age' => '<i class="fas fa-birthday-cake" aria-hidden="true"></i>', // Ancien 🎂
        'duration' => '<i class="fas fa-clock" aria-hidden="true"></i>',
        'like' => '<i class="fas fa-heart" aria-hidden="true"></i>',
        // ... autres icônes
    ];
    return $icons[$type] ?? '';
}

function render_camp_card($camp, $rank = null) {
    // Logique de rendu de la carte de camp
    echo '<div class="camp-card">';
    if ($rank !== null) {
        // Badge pour les camps boostés
        echo '<span class="boost-badge">#' . $rank . '</span>';
    }
    // Remplacement des emojis dans l'affichage
    echo '<p>' . get_icon('localisation') . ' ' . htmlspecialchars($camp['ville']) . '</p>';
    echo '<p>' . get_icon('age') . ' ' . htmlspecialchars($camp['age_min']) . ' ans et +</p>';
    // ... autres détails
    echo '</div>';
}

function render_carousel_section($title, $camps) {
    echo '<div class="category-carousel">';
    echo '<h3>' . htmlspecialchars($title) . '</h3>';
    echo '<div class="carousel-wrapper no-scrollbar">'; // Classe importante pour cacher la scrollbar
    // Flèches de navigation (nécessitent JS/CSS)
    echo '<button class="prev-btn">‹</button>';
    
    foreach ($camps as $index => $camp) {
        render_camp_card($camp, strpos($title, 'Sélection') !== false ? $index + 1 : null);
    }
    
    echo '<button class="next-btn">›</button>';
    echo '</div>'; // .carousel-wrapper
    echo '</div>'; // .category-carousel
}