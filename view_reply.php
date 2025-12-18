<?php
// Fichier: view_reply.php
require_once 'api/config.php';
require_once 'partials/header.php';

$token = $_GET['t'] ?? '';
$message = null;

if ($token) {
    $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE token = ? AND statut = 'Traité'");
    $stmt->execute([$token]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<style>
    /* Style pour le contenu riche (TinyMCE) affiché */
    .reply-content a { color: #2563eb; text-decoration: underline; font-weight: 500; }
    .reply-content a:hover { color: #1e40af; }
    .reply-content ul { list-style-type: disc; padding-left: 1.5rem; margin-bottom: 1rem; }
    .reply-content ol { list-style-type: decimal; padding-left: 1.5rem; margin-bottom: 1rem; }
    .reply-content p { margin-bottom: 0.75rem; }
    .reply-content strong { font-weight: 700; color: #111827; }
</style>

<div class="bg-gray-50 min-h-screen py-12">
    <div class="container mx-auto px-4 max-w-3xl">
        
        <?php if ($message): ?>
            <div class="text-center mb-10">
                <span class="bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">Dossier résolu</span>
                <h1 class="text-3xl font-extrabold text-gray-900 mt-3">Réponse du support</h1>
                <p class="text-gray-500 mt-2">Suite à votre demande du <?php echo date('d/m/Y', strtotime($message['created_at'])); ?></p>
            </div>

            <div class="space-y-6">
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden opacity-90">
                    <div class="bg-gray-50 px-6 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-xs uppercase font-bold text-gray-500 tracking-wider">Votre message</h3>
                        <i class="fa-regular fa-comments text-gray-400"></i>
                    </div>
                    <div class="p-6 text-gray-600 italic leading-relaxed">
                        "<?php echo nl2br(htmlspecialchars($message['message'])); ?>"
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-xl border border-blue-100 overflow-hidden ring-1 ring-blue-500/10">
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4 flex items-center justify-between text-white">
                        <div class="flex items-center gap-3">
                            <div class="bg-white/20 p-2 rounded-full"><i class="fa-solid fa-headset"></i></div>
                            <div>
                                <h3 class="font-bold text-lg leading-tight">L'équipe ColoMap</h3>
                                <p class="text-xs text-blue-100 opacity-90">Répondu le <?php echo date('d/m/Y à H:i', strtotime($message['replied_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-8 bg-white">
                        <div class="reply-content text-gray-800 text-lg leading-relaxed">
                            <?php echo $message['reponse']; ?>
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-100 flex items-center gap-3">
                            <img src="favico.png" alt="Signature" class="w-8 h-8 opacity-80">
                            <p class="text-sm text-gray-400 font-medium">Cordialement, le service support.</p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="mt-12 mb-8 bg-white p-4 rounded-xl border border-gray-200 shadow-sm text-center overflow-hidden">
                <p class="text-[10px] text-gray-400 uppercase tracking-widest mb-2">Publicité</p>
                <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3659884670016121" crossorigin="anonymous"></script>
                <ins class="adsbygoogle"
                     style="display:block"
                     data-ad-format="autorelaxed"
                     data-ad-client="ca-pub-3659884670016121"
                     data-ad-slot="1465151486"></ins>
                <script>
                     (adsbygoogle = window.adsbygoogle || []).push({});
                </script>
            </div>

            <div class="text-center pb-8">
                <a href="../" class="inline-flex items-center text-gray-500 font-bold hover:text-blue-600 transition">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Retour à l'accueil
                </a>
            </div>

        <?php else: ?>
            <div class="text-center py-20 bg-white rounded-3xl shadow-lg border border-gray-100">
                <div class="text-6xl mb-6">😕</div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Lien invalide ou expiré</h1>
                <p class="text-gray-500 mb-8 max-w-md mx-auto">Nous ne trouvons pas cette réponse.</p>
                <a href="contact.php" class="bg-blue-600 text-white px-8 py-4 rounded-xl font-bold hover:bg-blue-700">Nous contacter</a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once 'partials/footer.php'; ?>