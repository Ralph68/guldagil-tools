@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Admin — Tableau de bord</h1>
    <p>Bienvenue sur l’interface d’administration.</p>
    <ul>
        <li><a href="{{ route('admin.users') }}">Utilisateurs</a></li>
        <li><a href="{{ route('admin.settings') }}">Paramètres</a></li>
    </ul>
</div>
@endsection
