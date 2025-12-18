<?php
// Fichier: login.php
session_start();
require_once 'api/config.php';

// 1. Initialisation Google
$login_url = '#'; 
if (file_exists('api/google_config.php')) {
    require_once 'api/google_config.php';
    if (isset($client)) {
        $login_url = $client->createAuthUrl();
    }
}

// 2. Logique "Se souvenir de moi" (Cookie)
if (!isset($_SESSION['user']) && isset($_COOKIE['remember_me'])) {
    list($user_id, $token) = explode(':', $_COOKIE['remember_me']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // On vérifie le token
        if ($user && !empty($user['remember_token']) && hash_equals($user['remember_token'], hash('sha256', $token))) {
            if (isset($user['is_active']) && $user['is_active'] == 0) {
                // Compte désactivé
            } else {
                $_SESSION['user'] = $user;
                // Rotation du token
                $new_token = bin2hex(random_bytes(16));
                $new_hash = hash('sha256', $new_token);
                $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?")->execute([$new_hash, $user['id']]);
                setcookie('remember_me', $user['id'] . ':' . $new_token, time() + (86400 * 30), "/", "", false, true);
                header("Location: index.php");
                exit();
            }
        }
    } catch (Exception $e) {}
}

// 3. Redirection si déjà connecté
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$error = '';

// 4. Traitement du formulaire de connexion
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember_me']);

    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // MODIFICATION ICI :
                // On a supprimé le bloc qui interdisait la connexion si google_id existait.
                // On tente directement de vérifier le mot de passe.
                
                if (password_verify($password, $user['password'])) {
                    
                    // Vérification compte actif
                    if (isset($user['is_active']) && $user['is_active'] == 0) {
                        $error = "Votre compte a été désactivé. Contactez le support.";
                    } else {
                        // Connexion réussie
                        $_SESSION['user'] = $user;

                        // Si l'utilisateur n'avait pas son email vérifié mais qu'il se connecte (cas rare ici mais possible), 
                        // on peut considérer que le login valide l'accès, ou on laisse tel quel.

                        // Gestion Cookie "Se souvenir de moi"
                        if ($remember) {
                            $token = bin2hex(random_bytes(16)); 
                            $hash = hash('sha256', $token);
                            try {
                                $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?")->execute([$hash, $user['id']]);
                                setcookie('remember_me', $user['id'] . ':' . $token, time() + (86400 * 30), "/", "", false, true);
                            } catch (Exception $e) {}
                        } else {
                            if(isset($_COOKIE['remember_me'])) {
                                setcookie('remember_me', '', time() - 3600, "/");
                                try {
                                    $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?")->execute([$user['id']]);
                                } catch(Exception $e) {}
                            }
                        }

                        header("Location: index.php");
                        exit();
                    }
                } else {
                    // Mot de passe incorrect (ou pas de mot de passe car compte 100% Google)
                    $error = "Email ou mot de passe incorrect.";
                }
            } else {
                $error = "Email ou mot de passe incorrect.";
            }
        } catch (Exception $e) {
            $error = "Erreur système : " . $e->getMessage();
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}

// Gestion des erreurs URL
if (isset($_GET['error']) && $_GET['error'] == 'google_auth_failed') {
    $error = "Échec de l'authentification Google.";
} elseif (isset($_GET['error']) && $_GET['error'] == 'google_api_error') {
    $error = "Erreur de communication avec Google.";
}

// 5. Inclusion du Header
require_once 'partials/header.php';
?>

<div class="min-h-[calc(100vh-64px)] flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-gradient-to-br from-slate-100 to-slate-300">

    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        
        <div class="bg-white py-10 px-4 shadow-2xl rounded-2xl sm:px-10 border border-gray-100 relative overflow-hidden">
            
            <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500"></div>

            <div class="mb-8 text-center">
                <h2 class="text-3xl font-extrabold text-gray-900 mb-2">
                    Connexion à <span class="bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">ColoMap</span>
                </h2>
                <p class="text-sm text-gray-600">
                    Ou
                    <a href="register.php" class="font-bold text-blue-600 hover:text-blue-500 transition-colors hover:underline">
                        créer un nouveau compte
                    </a>
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-md animate-pulse">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fa-solid fa-circle-exclamation text-red-500 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700 font-bold"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form class="space-y-6" action="" method="POST">
                <div>
                    <label for="email" class="block text-sm font-bold text-gray-700 mb-1">Adresse email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-envelope text-gray-400"></i>
                        </div>
                        <input id="email" name="email" type="email" autocomplete="email" required placeholder="exemple@email.com"
                               class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all bg-gray-50 focus:bg-white">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-gray-700 mb-1">Mot de passe</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-lock text-gray-400"></i>
                        </div>
                        <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="••••••••"
                               class="appearance-none block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all bg-gray-50 focus:bg-white">
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none" title="Afficher/Masquer">
                            <i class="fa-solid fa-eye" id="eye-icon"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember_me" name="remember_me" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded cursor-pointer">
                        <label for="remember_me" class="ml-2 block text-sm text-gray-700 cursor-pointer select-none">Se souvenir de moi</label>
                    </div>

                    <div class="text-sm">
                        <a href="forgot_password.php" class="font-medium text-blue-600 hover:text-blue-500 transition-colors hover:underline">Mot de passe oublié ?</a>
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-md text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all transform hover:-translate-y-0.5 active:scale-95">
                        Se connecter
                    </button>
                </div>
            </form>

            <div class="mt-8">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-3 bg-white text-gray-500 font-medium tracking-wide">OU CONTINUER AVEC</span>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="<?php echo htmlspecialchars($login_url); ?>" 
                       class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg shadow-sm text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-200 transform hover:-translate-y-0.5 active:scale-95 group">
                        <img class="h-5 w-5 mr-3 group-hover:scale-110 transition-transform" src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google logo">
                        Compte Google
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function togglePassword() {
        const x = document.getElementById("password");
        const i = document.getElementById("eye-icon");
        if (x.type === "password") {
            x.type = "text";
            i.classList.remove("fa-eye");
            i.classList.add("fa-eye-slash");
        } else {
            x.type = "password";
            i.classList.remove("fa-eye-slash");
            i.classList.add("fa-eye");
        }
    }
</script>