<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ SEO::getTitle($session = false); }}</title>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat">

    <!-- Scripts -->
    @vite('resources/css/app.css')
    <script src="https://kit.fontawesome.com/f77b4e0d38.js" crossorigin="anonymous"></script>

    <!-- SEO -->
    {!! SEO::generate() !!}

    @if(App::environment() == 'production')
    <!-- Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-JMVR4M354F"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-JMVR4M354F');
    </script>
    @endif
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-base-100 text-neutral-content">
    @include('layouts.public-navigation')
        <main class="z-0">
            <div class="overflow-hidden">
                <x-application-alert />
            </div>
            <div id="content">
                {{ $slot }}
            </div>
        </main>
    @include('layouts.public-footer')
    </div>
</body>
</html>
