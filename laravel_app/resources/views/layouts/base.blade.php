<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>@yield('title','Guldagil')</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="{{ asset('favicon.ico') }}">
  <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
  <link rel="stylesheet" href="{{ asset('css/main.css') }}">
</head>
<body class="bg-slate">
  <header class="topbar">
    <div class="container">
      <a href="{{ route('landing') }}" class="brand">
        <img src="{{ asset('img/logo.png') }}" alt="Guldagil" class="logo">
      </a>
      <nav class="nav">
        @auth
          <a href="{{ route('home') }}">Accueil</a>
          <form method="post" action="{{ route('logout') }}" style="display:inline">
            @csrf
            <button class="btn">Déconnexion</button>
          </form>
        @else
          <a href="{{ route('login') }}" class="btn">Se connecter</a>
        @endauth
      </nav>
    </div>
  </header>

  <main class="container">@yield('content')</main>

  <script src="{{ asset('js/main.js') }}" defer></script>
</body>
</html>
