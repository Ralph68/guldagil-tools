{{-- resources/views/partials/nav.blade.php --}}
<nav class="bg-white border-b border-slate-200">
  <div class="container mx-auto px-4 py-3 flex items-center gap-4">
    <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold">
      <img src="{{ asset('assets/logo.svg') }}" alt="Guldagil" class="h-6 w-6">
      <span>Guldagil Tools</span>
    </a>

    <ul class="flex items-center gap-4 ml-6">
      <li><a href="{{ route('adr.index') }}" class="hover:underline">ADR</a></li>
      <li><a href="{{ route('epi.index') }}" class="hover:underline">EPI</a></li>
      <li><a href="{{ route('materiel.index') }}" class="hover:underline">Matériel</a></li>
      <li><a href="{{ route('qualite.index') }}" class="hover:underline">Qualité</a></li>
      <li><a href="{{ route('port.index') }}" class="hover:underline">Frais de port</a></li>
      <li><a href="{{ route('api.index') }}" class="hover:underline">API</a></li>
      <li><a href="{{ route('legal.index') }}" class="hover:underline">Légal</a></li>
      @auth
  @if(auth()->user()?->is_admin)
    <li><a href="{{ route('admin.index') }}" class="hover:underline">Admin</a></li>
  @endif
  <li><a href="{{ route('user.index') }}" class="hover:underline">Mon compte</a></li>
@endauth

    </ul>

    <div class="ml-auto">
      @auth
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="px-3 py-1 rounded bg-slate-800 text-white text-sm">Se déconnecter</button>
        </form>
      @else
        <a href="{{ route('login') }}" class="px-3 py-1 rounded bg-slate-800 text-white text-sm">Se connecter</a>
      @endauth
    </div>
  </div>
</nav>
