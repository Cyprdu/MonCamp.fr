<?php
require_once 'partials/header.php';
http_response_code(404);
?>

<main class="flex-grow flex flex-col items-center justify-center w-full px-4 py-12 text-center bg-gray-50">
    
    <div class="mb-8 transform hover:scale-105 transition-transform duration-500 ease-in-out">
        <img src="img/error404.gif" alt="Erreur 404" class="w-full max-w-md mx-auto object-contain drop-shadow-xl rounded-lg">
    </div>

    <h1 class="text-6xl md:text-8xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 mb-2 tracking-tighter">
        404
    </h1>
    
    <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4">
        Oups ! Vous semblez perdu.
    </h2>

    <p class="text-gray-600 mb-10 max-w-lg mx-auto text-lg leading-relaxed">
        La page que vous recherchez a peut-être été supprimée, son nom a changé ou elle est temporairement indisponible.
    </p>

    <a href="index" class="group relative inline-flex items-center justify-center px-8 py-4 text-base font-bold text-white transition-all duration-300 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 rounded-full shadow-lg hover:shadow-2xl hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
        <span class="absolute inset-0 w-full h-full -mt-1 rounded-lg opacity-30 bg-gradient-to-b from-transparent via-transparent to-black"></span>
        <span class="relative flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Retourner à l'accueil
        </span>
    </a>

</main>

<?php
require_once 'partials/footer.php';
?>