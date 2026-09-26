<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="color-scheme" content="dark" />
<meta name="theme-color" content="#0b0a1a" />
<title>@yield('title', 'QuizBlast') — QuizBlast</title>
<link rel="icon" type="image/svg+xml" href="/favicon.svg" />
<link rel="shortcut icon" href="/favicon.svg" />
<link rel="preload" href="/fonts/lexend-latin.woff2" as="font" type="font/woff2" crossorigin />
<link rel="preload" href="/fonts/atkinson-400-latin.woff2" as="font" type="font/woff2" crossorigin />
{{-- Effects mode is applied before first paint so the page never flashes the wrong mode.
     Default: calm when the OS asks for reduced motion, otherwise party. Storage is read in its own try, so blocked storage still gets the reduced-motion default. --}}
<script>(function(){var f=null;try{f=localStorage.getItem('qb-fx');}catch(e){}try{if(f!=='calm'&&f!=='party'){f=(window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches)?'calm':'party';}document.documentElement.setAttribute('data-fx',f);}catch(e){}})();</script>
<link rel="stylesheet" href="/css/app.css?v={{ filemtime(public_path('css/app.css')) }}" />
