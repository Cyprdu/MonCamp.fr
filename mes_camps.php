<?php
// 1. D'ABORD LA CONFIG (Session start est dedans)
require_once 'api/config.php';

// 2. SÉCURITÉ AVANT TOUT HTML
// On utilise !empty() pour éviter l'erreur "Undefined array key" si la clé n'existe pas
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_directeur'])) {
    header('Location: index.php');
    exit;
}

// 3. LOGIQUE MÉTIER (Récupération des données)
$userId = $_SESSION['user']['id'];
$camps = []; // Initialisation par défaut
$error = null;

try {
    // A. Récupérer TOUS les IDs de l'organisateur liés au User
    $stmtOrga = $pdo->prepare("SELECT id FROM organisateurs WHERE user_id = ?");
    $stmtOrga->execute([$userId]);
    $organisateurIds = $stmtOrga->fetchAll(PDO::FETCH_COLUMN); // Récupère un tableau de tous les IDs

    if (!empty($organisateurIds)) {
        // B. Créer des placeholders pour la clause IN
        $placeholders = implode(',', array_fill(0, count($organisateurIds), '?'));
        
        // C. Récupérer les camps liés à TOUS ces IDs d'organisateurs
        $sql = "
            SELECT 
                c.*,
                (SELECT COUNT(*) FROM inscriptions WHERE camp_id = c.id) as nb_inscrits
            FROM camps c
            WHERE c.organisateur_id IN ($placeholders) -- Utilisation de la clause IN
            ORDER BY c.date_debut DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        // Exécuter la requête en passant le tableau des IDs d'organisateur
        $stmt->execute($organisateurIds); 
        $camps = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        $error = "Aucun profil organisateur lié à votre compte.";
    }

} catch (Exception $e) {
    $error = "Erreur SQL : " . $e->getMessage();
}

// 4. ENFIN, ON AFFICHE LE HTML
require_once 'partials/header.php';
?>

<title>Mes Séjours - Gestion</title>

<div class="min-h-screen bg-gray-50 py-10 font-sans">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10">
            <div>
                <h1 class="text-3xl font-extrabold text-[#0A112F]">Gestion de vos séjours</h1>
                <p class="text-gray-500 mt-1">Gérez, modifiez et suivez les inscriptions de vos camps.</p>
            </div>
            
            <a href="create_camp.php" class="inline-flex items-center justify-center gap-2 bg-[#0A112F] hover:bg-blue-900 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Créer un nouveau séjour
            </a>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Attention</p>
                <p><?= $error ?></p>
            </div>
        <?php endif; ?>

        <?php if (empty($camps)): ?>
            <div class="bg-white rounded-3xl shadow-sm p-12 text-center border border-gray-100">
                <div class="mx-auto h-24 w-24 bg-blue-50 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-[#0A112F]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Aucun séjour pour le moment</h3>
                <p class="text-gray-500 mb-6">Commencez par publier votre premier séjour pour recevoir des inscriptions.</p>
                <a href="create_camp.php" class="text-[#0A112F] font-bold hover:underline">Créer mon premier séjour &rarr;</a>
            </div>
        <?php else: ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($camps as $camp): 
                    // Calculs sécurisés
                    $quota = max(1, intval($camp['quota_global']));
                    $inscrits = intval($camp['nb_inscrits']);
                    $percent = min(100, round(($inscrits / $quota) * 100));
                    
                    // Dates
                    $d1 = date('d/m', strtotime($camp['date_debut']));
                    $d2 = date('d/m/Y', strtotime($camp['date_fin']));
                    
                    // Image par défaut si vide
                    $img = !empty($camp['image_url']) ? $camp['image_url'] : 'assets/default_camp.jpg';
                    
                    // Token pour le lien de partage
                    $campToken = !empty($camp['token']) ? $camp['token'] : '';
                ?>
                
                <div class="bg-white rounded-2xl shadow-sm hover:shadow-md transition border border-gray-200 overflow-hidden flex flex-col h-full group">
                    
                    <div class="relative h-48 w-full overflow-hidden">
                        <img src="<?= htmlspecialchars($img) ?>" alt="Cover" class="w-full h-full object-cover transition duration-700 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        
                        <div class="absolute top-4 right-4 flex flex-col gap-2 items-end">
                            <?php if($camp['prive']): ?>
                                <span class="bg-gray-900 text-white text-xs font-bold px-2 py-1 rounded-md flex items-center gap-1 shadow-sm">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    Privé
                                </span>
                            <?php else: ?>
                                <span class="bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-md shadow-sm">Public</span>
                            <?php endif; ?>
                        </div>

                        <div class="absolute bottom-4 left-4 right-4">
                            <h3 class="text-white font-bold text-xl leading-tight truncate shadow-sm"><?= htmlspecialchars($camp['nom']) ?></h3>
                            <p class="text-gray-200 text-sm flex items-center gap-1 mt-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <?= htmlspecialchars($camp['ville']) ?>
                            </p>
                        </div>
                    </div>

                    <div class="p-5 flex-1 flex flex-col">
                        
                        <div class="flex justify-between items-center mb-4 text-sm text-gray-600">
                            <div class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span><?= $d1 ?> - <?= $d2 ?></span>
                            </div>
                            <div class="font-bold text-[#0A112F] text-lg">
                                <?= number_format($camp['prix'], 2, ',', ' ') ?>€
                            </div>
                        </div>

                        <div class="mb-6">
                            <div class="flex justify-between text-xs font-semibold mb-1.5">
                                <span class="text-gray-500">Inscrits</span>
                                <span class="text-[#0A112F]"><?= $inscrits ?> / <?= $quota ?></span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-[#0A112F] h-2 rounded-full" style="width: <?= $percent ?>%"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 gap-2 mt-auto border-t border-gray-100 pt-4">
                            
                            <a href="camp_details.php?id=<?= $camp['id'] ?>" target="_blank" class="flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-[#0A112F] hover:bg-blue-50 transition" title="Voir la page publique">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>

                            <a href="edit_camp.php?id=<?= $camp['id'] ?>" class="flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition" title="Modifier">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </a>

                            <button onclick="shareLink('<?= $campToken ?>')" class="flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 transition" title="Partager le lien">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                </svg>
                            </button>

                            <a href="delete_camp.php?id=<?= $camp['id'] ?>" class="flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50 transition" title="Supprimer" onclick="return confirm('Attention : Cette action est irréversible. Supprimer ce séjour ?')">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </a>

                        </div>
                        
                        <a href="gestion_camp.php?t=<?= $camp['token'] ?>" class="mt-3 block w-full text-center py-2 rounded-lg bg-gray-100 text-gray-700 text-sm font-bold hover:bg-gray-200 transition">
                            Gérer les inscrits
                        </a>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function shareLink(token) {
    if (!token) {
        alert("Ce camp n'a pas de lien partageable.");
        return;
    }
    
    // Construction de l'URL spécifique
    const baseUrl = "https://moncamp.fr/camp_details.php?t=";
    const fullUrl = baseUrl + token;
    
    // Copie dans le presse-papier
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(fullUrl).then(() => {
            alert("Lien copié dans le presse-papier !\n\n" + fullUrl);
        }).catch(err => {
            console.error('Erreur lors de la copie :', err);
            prompt("Impossible de copier automatiquement. Voici le lien :", fullUrl);
        });
    } else {
        // Fallback pour les anciens navigateurs ou contextes non sécurisés
        prompt("Voici le lien à partager :", fullUrl);
    }
}
</script>

<?php require_once 'partials/footer.php'; ?>