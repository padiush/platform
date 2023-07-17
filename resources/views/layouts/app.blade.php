<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://kit.fontawesome.com/f77b4e0d38.js" crossorigin="anonymous"></script>
</head>
<body class="font-sans antialiased" data-theme="dark">
    <div class="min-h-screen bg-neutral">
        @include('layouts.navigation')

        <!-- Page Heading -->
        <header class="bg-base-100 shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 text-base-content flex items-center">
                @isset($action)<span class="inline-block w-16">{{ $action }}</span>@endisset
                <span class="inline-block grow">
                    <h2 class="font-semibold text-xl leading-tight text-base-content">
                        {{ $header }}
                    </h2>
                    @isset($subtitle)<span class="text-sm">{{ $subtitle }}</span>@endisset
                </span>
                @isset($action_right)<span class="inline-block w-32">{{ $action_right }}</span>@endisset
            </div>
        </header>

        <!-- Page Content -->
        <main class="z-0">
            <div class="overflow-hidden">
                <x-application-alert />
            </div>
            <div class="py-12" id="content">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </div>
            @isset($bottom_bar)
            <div class="btm-nav bg-primary text-primary-content">
                {{ $bottom_bar }}
            </div>
            @endisset
        </main>
    </div>
</body>
</html>
