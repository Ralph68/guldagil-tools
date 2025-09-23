{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Guldagil Tools')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Favicon & logos : place-les dans public/assets/ --}}
    <link rel="icon" href="{{ asset('assets/favicon.ico') }}">

    {{-- Vite (si activé dans le projet) --}}
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen">
    @include('partials.nav')

    <main class="container mx-auto px-4 py-6">
        @yield('content')
    </main>

    <footer class="text-center text-sm text-slate-500 py-6">
        © {{ date('Y') }} Guldagil — Outils internes
    </footer>
</body>
</html>
