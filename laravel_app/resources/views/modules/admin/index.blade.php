@extends('layouts.app')
@section('title', 'Admin — Tableau de bord')

@section('content')
<div class="grid md:grid-cols-3 gap-6">
  <div class="bg-white p-6 rounded-2xl shadow-sm">
    <h2 class="text-lg font-semibold mb-2">Utilisateurs</h2>
    <p class="text-3xl font-bold">{{ $usersCount }}</p>
    <a href="{{ route('admin.users') }}" class="text-sm underline mt-2 inline-block">Voir la liste</a>
  </div>

  <div class="bg-white p-6 rounded-2xl shadow-sm">
    <h2 class="text-lg font-semibold mb-2">Admins</h2>
    <p class="text-3xl font-bold">{{ $adminsCount }}</p>
  </div>

  <div class="bg-white p-6 rounded-2xl shadow-sm">
    <h2 class="text-lg font-semibold mb-2">Réglages</h2>
    <a href="{{ route('admin.settings') }}" class="px-3 py-1 rounded border inline-block">Ouvrir</a>
  </div>
</div>
@endsection
