<?php
// Fichier: /aide.php
require_once 'partials/header.php';
?>

<div class="bg-gray-50 min-h-screen py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-5xl">
        
        <div class="text-center mb-16">
            <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-4">Centre d'Aide & FAQ</h1>
            <div class="w-20 h-1.5 bg-gradient-to-r from-blue-500 to-purple-500 mx-auto rounded-full mb-6"></div>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Tout ce que vous devez savoir sur le fonctionnement de ColoMap. Que vous soyez parent, organisateur ou animateur, nous avons les réponses.
            </p>
        </div>

        <div class="flex flex-wrap justify-center gap-4 mb-12">
            <a href="#parents" class="px-6 py-2 bg-white rounded-full shadow-sm text-gray-700 font-bold hover:bg-blue-50 hover:text-blue-600 transition-colors border border-gray-200">
                👨‍👩‍👧‍👦 Parents
            </a>
            <a href="#organisateurs" class="px-6 py-2 bg-white rounded-full shadow-sm text-gray-700 font-bold hover:bg-purple-50 hover:text-purple-600 transition-colors border border-gray-200">
                🏢 Organisateurs
            </a>
            <a href="#animateurs" class="px-6 py-2 bg-white rounded-full shadow-sm text-gray-700 font-bold hover:bg-green-50 hover:text-green-600 transition-colors border border-gray-200">
                🎒 Animateurs
            </a>
            <a href="#paiements" class="px-6 py-2 bg-white rounded-full shadow-sm text-gray-700 font-bold hover:bg-yellow-50 hover:text-yellow-600 transition-colors border border-gray-200">
                💳 Paiements & Sécurité
            </a>
        </div>

        <section id="parents" class="mb-16 scroll-mt-24">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-blue-50 p-6 border-b border-blue-100 flex items-center">
                    <div class="bg-blue-100 p-3 rounded-full mr-4 text-blue-600"><i class="fa-solid fa-people-roof text-2xl"></i></div>
                    <h2 class="text-2xl font-bold text-gray-800">Questions Fréquentes des Parents</h2>
                </div>
                
                <div class="p-8 space-y-8">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center"><i class="fa-solid fa-circle-question text-blue-400 mr-2 text-sm"></i> Comment inscrire mon enfant à un camp ?</h3>
                        <p class="text-gray-600 leading-relaxed">
                            L'inscription sur ColoMap est conçue pour être simple et rapide. Commencez par créer un compte parent gratuitement. Une fois connecté, vous pouvez ajouter le profil de vos enfants (nom, prénom, âge, allergies éventuelles). Ensuite, naviguez à travers notre catalogue de séjours en utilisant les filtres (âge, thème, lieu). Une fois le camp idéal trouvé, cliquez sur "Réserver". Vous devrez alors sélectionner l'enfant concerné et procéder au paiement de l'acompte ou de la totalité du séjour selon les conditions de l'organisateur. Un email de confirmation vous sera envoyé immédiatement avec le dossier d'inscription complet.
                        </p>
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center"><i class="fa-solid fa-circle-question text-blue-400 mr-2 text-sm"></i> Les colonies sont-elles vérifiées ?</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Absolument. La sécurité est notre priorité numéro un. Chaque organisateur souhaitant publier sur ColoMap doit passer par un processus de validation strict. Nous vérifions leur numéro d'agrément Jeunesse et Sports, leur assurance responsabilité civile professionnelle ainsi que leur projet éducatif. De plus, nous encourageons vivement les parents à laisser des avis après chaque séjour pour maintenir un niveau de transparence total au sein de la communauté.
                        </p>
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center"><i class="fa-solid fa-circle-question text-blue-400 mr-2 text-sm"></i> Que faire si mon enfant a un problème de santé ?</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Lors de l'inscription, une fiche sanitaire de liaison est obligatoire. Vous devez y renseigner toutes les informations médicales importantes : allergies, traitements en cours, régimes alimentaires spécifiques, etc. Ces informations sont transmises de manière sécurisée et confidentielle au directeur du séjour et à l'assistant sanitaire présent sur place. Si votre enfant tombe malade durant le séjour, l'équipe encadrante vous contactera immédiatement pour vous tenir informé et prendre les décisions nécessaires en accord avec un médecin.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div class="my-12 text-center bg-gray-100 rounded-xl p-6 border border-gray-200">
            <span class="text-[10px] text-gray-400 uppercase tracking-widest block mb-4">Publicité</span>
            <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3659884670016121" crossorigin="anonymous"></script>
            <ins class="adsbygoogle"
                 style="display:block; text-align:center;"
                 data-ad-layout="in-article"
                 data-ad-format="fluid"
                 data-ad-client="ca-pub-3659884670016121"
                 data-ad-slot="6405652824"></ins>
            <script>
                 (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
        </div>

        <section id="organisateurs" class="mb-16 scroll-mt-24">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-purple-50 p-6 border-b border-purple-100 flex items-center">
                    <div class="bg-purple-100 p-3 rounded-full mr-4 text-purple-600"><i class="fa-solid fa-building-user text-2xl"></i></div>
                    <h2 class="text-2xl font-bold text-gray-800">Espace Organisateurs</h2>
                </div>
                
                <div class="p-8 space-y-8">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center"><i class="fa-solid fa-circle-question text-purple-400 mr-2 text-sm"></i> Comment référencer mes séjours sur ColoMap ?</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Pour devenir partenaire, créez un compte "Organisateur". Vous devrez fournir les justificatifs légaux de votre structure (association, entreprise, collectivité). Une fois votre compte validé par notre équipe (sous 24 à 48h), vous aurez accès à un tableau de bord complet. De là, vous pourrez créer des fiches détaillées pour chacun de vos séjours : description, photos, dates, tarifs, âges requis, et nombre de places disponibles.
                        </p>
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center"><i class="fa-solid fa-circle-question text-purple-400 mr-2 text-sm"></i> Quels sont les coûts pour les organisateurs ?</h3>
                        <p class="text-gray-600 leading-relaxed">
                            L'inscription et la publication des annonces sont entièrement gratuites. ColoMap se rémunère uniquement via une commission de service prélevée lors d'une réservation confirmée. Ce modèle "gagnant-gagnant" vous permet de bénéficier d'une visibilité nationale sans risque financier initial. Aucun frais caché, tout est transparent dès la création de votre compte.
                        </p>
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center"><i class="fa-solid fa-circle-question text-purple-400 mr-2 text-sm"></i> Comment gérer les inscriptions et les paiements ?</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Tout est centralisé sur votre tableau de bord. Vous recevez une notification à chaque nouvelle inscription. Vous pouvez voir la liste des inscrits, télécharger leurs fiches sanitaires et suivre l'état des paiements en temps réel. Les fonds versés par les parents sont sécurisés via notre partenaire bancaire Stripe et vous sont reversés automatiquement selon l'échéancier défini (généralement une partie à la réservation et le solde avant le début du séjour).
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section id="animateurs" class="mb-16 scroll-mt-24">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-green-50 p-6 border-b border-green-100 flex items-center">
                    <div class="bg-green-100 p-3 rounded-full mr-4 text-green-600"><i class="fa-solid fa-bullhorn text-2xl"></i></div>
                    <h2 class="text-2xl font-bold text-gray-800">Espace Animation / Recrutement</h2>
                </div>
                
                <div class="p-8 space-y-8">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center"><i class="fa-solid fa-circle-question text-green-400 mr-2 text-sm"></i> Je cherche un poste d'animateur, comment faire ?</h3>
                        <p class="text-gray-600 leading-relaxed">
                            ColoMap dispose d'une section dédiée à l'emploi. Créez votre profil "Animateur", renseignez vos qualifications (BAFA, BAFD, PSC1, SB...), vos expériences passées et vos disponibilités. Vous pourrez alors postuler directement aux offres publiées par les organisateurs ou rendre votre CV visible dans la CVthèque consultée par les directeurs de séjours.
                        </p>
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center"><i class="fa-solid fa-circle-question text-green-400 mr-2 text-sm"></i> Faut-il obligatoirement le BAFA ?</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Bien que le BAFA (Brevet d'Aptitude aux Fonctions d'Animateur) soit le diplôme de référence et le plus demandé, certains séjours acceptent des animateurs stagiaires (en cours de formation) ou non diplômés dans la limite des quotas légaux imposés par la Jeunesse et Sports. N'hésitez pas à filtrer les offres d'emploi selon votre niveau de qualification.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div class="my-12 text-center bg-gray-100 rounded-xl p-6 border border-gray-200">
            <span class="text-[10px] text-gray-400 uppercase tracking-widest block mb-4">Publicité</span>
            <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3659884670016121" crossorigin="anonymous"></script>
            <ins class="adsbygoogle"
                 style="display:block; text-align:center;"
                 data-ad-layout="in-article"
                 data-ad-format="fluid"
                 data-ad-client="ca-pub-3659884670016121"
                 data-ad-slot="6405652824"></ins>
            <script>
                 (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
        </div>

        <section id="paiements" class="mb-16 scroll-mt-24">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-yellow-50 p-6 border-b border-yellow-100 flex items-center">
                    <div class="bg-yellow-100 p-3 rounded-full mr-4 text-yellow-600"><i class="fa-solid fa-lock text-2xl"></i></div>
                    <h2 class="text-2xl font-bold text-gray-800">Paiements & Sécurité</h2>
                </div>
                
                <div class="p-8 space-y-8">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center"><i class="fa-solid fa-circle-question text-yellow-400 mr-2 text-sm"></i> Le paiement en ligne est-il sécurisé ?</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Oui, totalement. ColoMap utilise la technologie <strong>Stripe</strong>, leader mondial des paiements en ligne, utilisé par des entreprises comme Amazon ou Booking. Vos informations bancaires sont cryptées (protocole SSL/TLS) et ne sont jamais stockées sur nos serveurs. Nous prenons également en charge l'authentification forte (3D Secure) pour éviter toute fraude.
                        </p>
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center"><i class="fa-solid fa-circle-question text-yellow-400 mr-2 text-sm"></i> Acceptez-vous les Chèques Vacances (ANCV) ou les aides CAF ?</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Cela dépend de chaque organisateur. Sur la fiche de chaque séjour, une section "Moyens de paiement acceptés" vous indique si l'organisme est habilité à recevoir les Chèques Vacances (papier ou Connect) ou s'il est conventionné VACAF. Si c'est le cas, la procédure spécifique vous sera indiquée lors de la réservation (généralement : paiement d'un acompte en ligne, puis envoi des chèques ou attestation CAF pour le solde).
                        </p>
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center"><i class="fa-solid fa-circle-question text-yellow-400 mr-2 text-sm"></i> Quelle est la politique d'annulation ?</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Chaque organisateur définit ses propres conditions générales de vente (CGV). Elles sont consultables avant toute validation de commande. En général, plus l'annulation est proche de la date de départ, moins le remboursement est important. ColoMap propose également une option "Assurance Annulation" lors du paiement pour vous couvrir en cas d'imprévu majeur (maladie, accident, etc.).
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div class="text-center mt-20 mb-12">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Vous ne trouvez pas votre réponse ?</h3>
            <p class="text-gray-600 mb-8">Notre équipe support est disponible 7j/7 pour vous aider.</p>
            <a href="mailto:auth@moncamp.fr" class="inline-flex items-center px-8 py-4 bg-gray-900 text-white rounded-full font-bold shadow-lg hover:bg-gray-800 hover:-translate-y-1 transition-all duration-300">
                <i class="fa-regular fa-envelope mr-3 text-xl"></i> Contacter le support
            </a>
        </div>

    </div>
</div>

<style>
/* Permet un défilement fluide vers les ancres */
html { scroll-behavior: smooth; }
</style>

<?php require_once 'partials/footer.php'; ?>