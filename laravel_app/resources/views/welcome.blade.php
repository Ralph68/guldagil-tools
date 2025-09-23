{{-- resources/views/welcome.blade.php --}}
@extends('layouts.app')

@section('title', 'Accueil')

@section('content')
  <div class="grid md:grid-cols-2 gap-6">
    {{-- Bloc gauche : Présentation --}}
    <section class="bg-white rounded-2xl p-6 shadow-sm">
      <h1 class="text-2xl font-semibold mb-2">Bienvenue sur Guldagil Tools</h1>
      @guest
        <p class="text-slate-600">
          Accédez aux modules internes (ADR, EPI, Matériel, Qualité, etc.). 
          Merci de vous connecter pour voir les sections réservées.
        </p>
        <div class="mt-4">
          <a href="{{ route('login') }}" class="px-4 py-2 bg-slate-800 text-white rounded">Connexion</a>
          <a href="{{ route('register') }}" class="ml-2 px-4 py-2 border rounded">Créer un compte</a>
        </div>
      @else
        <p class="text-slate-600">
          Vous êtes connecté. Sélectionnez un module dans le menu ci-dessus pour commencer.
        </p>
      @endguest
    </section>

    {{-- Bloc droite : Raccourcis modules --}}
    <section class="bg-white rounded-2xl p-6 shadow-sm">
      <h2 class="text-xl font-semibold mb-4">Modules</h2>
      <div class="grid grid-cols-2 gap-3">
        <a class="p-3 rounded border hover:bg-slate-50" href="{{ route('adr.index') }}">ADR</a>
        <a class="p-3 rounded border hover:bg-slate-50" href="{{ route('epi.index') }}">EPI</a>
        <a class="p-3 rounded border hover:bg-slate-50" href="{{ route('materiel.index') }}">Matériel</a>
        <a class="p-3 rounded border hover:bg-slate-50" href="{{ route('qualite.index') }}">Qualité</a>
        <a class="p-3 rounded border hover:bg-slate-50" href="{{ route('port.index') }}">Frais de port</a>
        <a class="p-3 rounded border hover:bg-slate-50" href="{{ route('api.index') }}">API</a>
        <a class="p-3 rounded border hover:bg-slate-50" href="{{ route('legal.index') }}">Légal</a>
        @auth
          <a class="p-3 rounded border hover:bg-slate-50" href="{{ route('admin.index') }}">Admin</a>
        @endauth
      </div>
    </section>
  </div>
@endsection
