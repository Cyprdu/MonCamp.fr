<?php
require_once 'partials/header.php';

// Sécurité
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = htmlspecialchars(trim($_POST['nom']));
    $prenom = htmlspecialchars(trim($_POST['prenom']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $user_id = $_SESSION['user']['id'];

    if (empty($nom) || empty($prenom) || empty($email)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format d'email invalide.";
    } else {
        // Connexion DB (Adaptez selon votre config.php)
        require_once 'api/config.php'; 
        
        try {
            // Vérifier si l'email existe déjà pour un autre utilisateur
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $error = "Cet email est déjà utilisé par un autre compte.";
            } else {
                // Mise à jour
                $update = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, email = ? WHERE id = ?");
                if ($update->execute([$nom, $prenom, $email, $user_id])) {
                    // Mise à jour de la session
                    $_SESSION['user']['nom'] = $nom;
                    $_SESSION['user']['prenom'] = $prenom;
                    $_SESSION['user']['email'] = $email;
                    
                    $message = "Informations mises à jour avec succès !";
                } else {
                    $error = "Erreur lors de la mise à jour.";
                }
            }
        } catch (PDOException $e) {
            $error = "Erreur base de données : " . $e->getMessage();
        }
    }
}

// Données actuelles
$nom_actuel = $_SESSION['user']['nom'];
$prenom_actuel = $_SESSION['user']['prenom'];
$email_actuel = $_SESSION['user']['email'];
?>

<div class="min-h-screen bg-gray-50 py-12">
    <div class="container mx-auto px-4 max-w-lg">
        
        <div class="mb-6">
            <a href="profile.php" class="text-gray-500 hover:text-blue-600 flex items-center gap-2 transition-colors">
                <i class="fa-solid fa-arrow-left"></i> Retour au profil
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-xl overflow-hidden p-8 fade-in-anim">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Modifier mes informations</h1>
            <p class="text-gray-500 mb-6">Mettez à jour vos informations personnelles.</p>

            <?php if ($message): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r" role="alert">
                    <p class="font-bold">Succès</p>
                    <p><?php echo $message; ?></p>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r" role="alert">
                    <p class="font-bold">Erreur</p>
                    <p><?php echo $error; ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="prenom" class="block text-sm font-medium text-gray-700 mb-1">Prénom</label>
                        <input type="text" name="prenom" id="prenom" value="<?php echo htmlspecialchars($prenom_actuel); ?>" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 focus:bg-white transition-colors">
                    </div>
                    <div>
                        <label for="nom" class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                        <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars($nom_actuel); ?>" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 focus:bg-white transition-colors">
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse Email</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email_actuel); ?>" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 focus:bg-white transition-colors">
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 hover:shadow-lg transition-all transform active:scale-95">
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.fade-in-anim { animation: fadeIn 0.5s ease-out forwards; opacity: 0; transform: translateY(10px); }
@keyframes fadeIn { to { opacity: 1; transform: translateY(0); } }
</style>

<?php require_once 'partials/footer.php'; ?>