@extends('layouts.base')
@section('title','Guldagil — Portail')

@section('content')
  <section class="card">
    <h1>Bienvenue sur Guldagil</h1>
    <p>Portail privé de l’entreprise. Accès réservé aux collaborateurs.</p>
    <p><a class="btn primary" href="{{ route('login') }}">Se connecter</a></p>
  </section>

  <section class="grid">
    <div class="card">
      <h2>EPI</h2>
      <p>Gestion des EPI, stocks et affectations.</p>
    </div>
    <div class="card">
      <h2>Qualité</h2>
      <p>Suivi des non-conformités et actions.</p>
    </div>
    <div class="card">
      <h2>Matériel</h2>
      <p>Inventaire, demandes et reports.</p>
    </div>
  </section>
@endsection
