<?php
// Fichier: contact.php
require_once 'api/config.php';
require_once 'partials/header.php';

$msg_success = '';
$msg_error = '';

// Variables par défaut
$nom = ''; $prenom = ''; $email = '';
$motif_select = ''; 
$motif_autre = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Récupération User
    if (isset($_SESSION['user'])) {
        $nom = $_SESSION['user']['nom'];
        $prenom = $_SESSION['user']['prenom'];
        $email = $_SESSION['user']['email'];
        $user_id = $_SESSION['user']['id'];
    } else {
        $nom = htmlspecialchars(trim($_POST['nom']));
        $prenom = htmlspecialchars(trim($_POST['prenom']));
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $user_id = NULL;
    }
    
    // 2. Traitement du Motif "Autre"
    $motif_select = htmlspecialchars(trim($_POST['motif']));
    $message = htmlspecialchars(trim($_POST['message']));
    
    // Si "Autre" est choisi, on prend le champ de précision
    if ($motif_select === 'Autre') {
        $precision = htmlspecialchars(trim($_POST['motif_autre']));
        if (!empty($precision)) {
            $final_motif = "Autre : " . $precision;
        } else {
            $final_motif = "Autre (Non précisé)";
        }
    } else {
        $final_motif = $motif_select;
    }

    // 3. Validation et Envoi
    if ($nom && $prenom && $email && $final_motif && $message) {
        try {
            $token = bin2hex(random_bytes(32)); 
            
            $stmt = $pdo->prepare("INSERT INTO contact_messages (user_id, nom, prenom, email, motif, message, token) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $nom, $prenom, $email, $final_motif, $message, $token]);
            
            $msg_success = "Votre demande a bien été envoyée ! Nous vous répondrons par email.";
            $message = ''; 
            $motif_select = '';
        } catch (Exception $e) {
            $msg_error = "Une erreur est survenue. Veuillez réessayer.";
        }
    } else {
        $msg_error = "Veuillez remplir tous les champs.";
    }
}
?>

<div class="bg-gray-50 min-h-screen py-12 font-sans">
    <div class="container mx-auto px-4">
        
        <div class="text-center mb-10">
            <h1 class="text-4xl font-extrabold text-gray-900 mb-2">Nous contacter</h1>
            <p class="text-gray-500">Une question, une suggestion ? Écrivez-nous.</p>
        </div>

        <div class="max-w-3xl mx-auto">
            <?php if ($msg_success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-8 rounded-r shadow-sm flex items-center animate-pulse">
                    <i class="fa-solid fa-check-circle text-xl mr-3"></i>
                    <div><p class="font-bold">Message envoyé</p><p class="text-sm"><?php echo $msg_success; ?></p></div>
                </div>
            <?php endif; ?>

            <?php if ($msg_error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-r shadow-sm flex items-center">
                    <i class="fa-solid fa-triangle-exclamation text-xl mr-3"></i>
                    <div><p class="font-bold">Erreur</p><p class="text-sm"><?php echo $msg_error; ?></p></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="w-full max-w-3xl mx-auto bg-white rounded-2xl shadow-xl p-8 border border-gray-100 relative overflow-hidden">
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-blue-50 rounded-full opacity-50 blur-2xl"></div>

            <form action="" method="POST" class="space-y-6 relative z-10">
                
                <?php if (!isset($_SESSION['user'])): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Prénom</label>
                            <input type="text" name="prenom" value="<?php echo htmlspecialchars($prenom); ?>" required class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nom</label>
                            <input type="text" name="nom" value="<?php echo htmlspecialchars($nom); ?>" required class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                <?php else: ?>
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex items-center mb-6">
                        <div class="flex-shrink-0 bg-blue-100 rounded-full p-3 mr-4"><i class="fa-solid fa-user-check text-blue-600 text-xl"></i></div>
                        <div>
                            <p class="text-sm text-blue-800 font-bold">Connecté en tant que <?php echo htmlspecialchars($_SESSION['user']['prenom'] . ' ' . $_SESSION['user']['nom']); ?></p>
                            <p class="text-xs text-blue-600">Email : <?php echo htmlspecialchars($_SESSION['user']['email']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Motif de la demande</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="fa-solid fa-list"></i></span>
                            <select name="motif" id="motif-select" required onchange="toggleAutreInput()" class="w-full pl-10 bg-gray-50 border border-gray-300 rounded-lg py-3 focus:ring-2 focus:ring-blue-500 outline-none appearance-none cursor-pointer">
                                <option value="" disabled selected>Choisissez un motif...</option>
                                <option value="Renseignement séjour">Renseignement sur un séjour</option>
                                <option value="Inscription / Réservation">Problème d'inscription / Réservation</option>
                                <option value="Compte / Connexion">Problème de compte / Connexion</option>
                                <option value="Partenariat / Organisateur">Espace Organisateur / Partenariat</option>
                                <option value="Recrutement / Animation">Recrutement / Espace Animation</option>
                                <option value="Autre">Autre demande (Préciser)</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-gray-500"><svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg></div>
                        </div>
                    </div>

                    <div id="div-autre" class="hidden transition-all duration-300 ease-in-out">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Précisez votre demande</label>
                        <input type="text" name="motif_autre" id="input-autre" placeholder="Sujet de votre message..." class="w-full bg-white border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Votre message</label>
                    <textarea name="message" rows="6" required placeholder="Détaillez votre demande ici..." class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-4 rounded-xl shadow-lg transform transition hover:-translate-y-0.5 active:scale-95 flex justify-center items-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> Envoyer le message
                </button>
            </form>
        </div>

        <div class="w-full max-w-3xl mx-auto mt-8">
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 text-center overflow-hidden">
                <span class="block text-[10px] text-gray-400 uppercase mb-2">Publicité</span>
                <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3659884670016121" crossorigin="anonymous"></script>
                <ins class="adsbygoogle"
                     style="display:block"
                     data-ad-client="ca-pub-3659884670016121"
                     data-ad-slot="1183215923"
                     data-ad-format="horizontal"
                     data-full-width-responsive="true"></ins>
                <script>
                     (adsbygoogle = window.adsbygoogle || []).push({});
                </script>
            </div>
        </div>

        <div class="mt-16 max-w-4xl mx-auto">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Questions Fréquentes</h2>
                <p class="text-gray-500 mt-2">Trouvez rapidement une réponse à vos interrogations</p>
            </div>

            <div class="space-y-4">
                <details class="group bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <summary class="flex justify-between items-center p-5 cursor-pointer font-bold text-gray-800 hover:bg-gray-50 transition select-none">
                        <span><i class="fa-solid fa-lock text-blue-500 mr-2"></i> J'ai oublié mon mot de passe, que faire ?</span>
                        <span class="transition group-open:rotate-180"><i class="fa-solid fa-chevron-down text-gray-400"></i></span>
                    </summary>
                    <div class="p-5 pt-0 text-gray-600 border-t border-gray-100 bg-gray-50/50 leading-relaxed">
                        Pas de panique ! Vous pouvez réinitialiser votre mot de passe à tout moment en cliquant sur le lien 
                        <a href="forgot_password.php" class="text-blue-600 font-bold hover:underline">Mot de passe oublié</a> sur la page de connexion. 
                        Un email vous sera envoyé avec les instructions pour en créer un nouveau.
                    </div>
                </details>

                <details class="group bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <summary class="flex justify-between items-center p-5 cursor-pointer font-bold text-gray-800 hover:bg-gray-50 transition select-none">
                        <span><i class="fa-solid fa-circle-exclamation text-red-500 mr-2"></i> Je n'arrive pas à me connecter</span>
                        <span class="transition group-open:rotate-180"><i class="fa-solid fa-chevron-down text-gray-400"></i></span>
                    </summary>
                    <div class="p-5 pt-0 text-gray-600 border-t border-gray-100 bg-gray-50/50 leading-relaxed">
                        Vérifiez bien que vous utilisez l'adresse email fournie lors de votre inscription. Assurez-vous également que la touche "Majuscule" n'est pas activée par erreur.
                        Si le problème persiste, essayez de réinitialiser votre mot de passe ou contactez-nous via le formulaire ci-dessus.
                    </div>
                </details>

                <details class="group bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <summary class="flex justify-between items-center p-5 cursor-pointer font-bold text-gray-800 hover:bg-gray-50 transition select-none">
                        <span><i class="fa-solid fa-child text-green-500 mr-2"></i> Comment inscrire mon enfant à un séjour ?</span>
                        <span class="transition group-open:rotate-180"><i class="fa-solid fa-chevron-down text-gray-400"></i></span>
                    </summary>
                    <div class="p-5 pt-0 text-gray-600 border-t border-gray-100 bg-gray-50/50 leading-relaxed">
                        C'est très simple :
                        <ol class="list-decimal list-inside mt-2 space-y-1 ml-2">
                            <li>Connectez-vous à votre compte.</li>
                            <li>Allez dans "Gérer mes enfants" pour créer une fiche enfant.</li>
                            <li>Parcourez les séjours, choisissez celui qui vous plaît et cliquez sur "Réserver".</li>
                            <li>Suivez les étapes pour finaliser l'inscription.</li>
                        </ol>
                    </div>
                </details>

                <details class="group bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <summary class="flex justify-between items-center p-5 cursor-pointer font-bold text-gray-800 hover:bg-gray-50 transition select-none">
                        <span><i class="fa-solid fa-user-tie text-purple-500 mr-2"></i> Je suis un organisateur, comment proposer mes séjours ?</span>
                        <span class="transition group-open:rotate-180"><i class="fa-solid fa-chevron-down text-gray-400"></i></span>
                    </summary>
                    <div class="p-5 pt-0 text-gray-600 border-t border-gray-100 bg-gray-50/50 leading-relaxed">
                        ColoMap est ouvert aux organisateurs professionnels. Vous devez créer un compte "Directeur" ou faire une demande pour le devenir. 
                        Rendez-vous dans la section <a href="organisateurs.php" class="text-purple-600 font-bold hover:underline">Espace Organisateur</a> pour en savoir plus et commencer vos démarches.
                    </div>
                </details>

                <details class="group bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <summary class="flex justify-between items-center p-5 cursor-pointer font-bold text-gray-800 hover:bg-gray-50 transition select-none">
                        <span><i class="fa-solid fa-credit-card text-gray-500 mr-2"></i> Le paiement est-il sécurisé ?</span>
                        <span class="transition group-open:rotate-180"><i class="fa-solid fa-chevron-down text-gray-400"></i></span>
                    </summary>
                    <div class="p-5 pt-0 text-gray-600 border-t border-gray-100 bg-gray-50/50 leading-relaxed">
                        Oui, absolument. Nous utilisons <strong>Stripe</strong>, une plateforme de paiement mondialement reconnue, pour sécuriser toutes les transactions effectuées sur notre site. Vos coordonnées bancaires ne sont jamais stockées sur nos serveurs.
                    </div>
                </details>
            </div>
        </div>

        <div class="w-full max-w-3xl mx-auto mt-8">
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 text-center overflow-hidden">
                <span class="block text-[10px] text-gray-400 uppercase mb-2">Publicité</span>
                <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3659884670016121" crossorigin="anonymous"></script>
                <ins class="adsbygoogle"
                     style="display:block"
                     data-ad-client="ca-pub-3659884670016121"
                     data-ad-slot="1183215923"
                     data-ad-format="horizontal"
                     data-full-width-responsive="true"></ins>
                <script>
                     (adsbygoogle = window.adsbygoogle || []).push({});
                </script>
            </div>
        </div>

    </div>
</div>

<script>
function toggleAutreInput() {
    const select = document.getElementById('motif-select');
    const divAutre = document.getElementById('div-autre');
    const inputAutre = document.getElementById('input-autre');
    
    if (select.value === 'Autre') {
        divAutre.classList.remove('hidden');
        inputAutre.setAttribute('required', 'required');
        setTimeout(() => inputAutre.focus(), 100);
    } else {
        divAutre.classList.add('hidden');
        inputAutre.removeAttribute('required');
        inputAutre.value = ''; // Reset
    }
}
</script>

<?php require_once 'partials/footer.php'; ?>