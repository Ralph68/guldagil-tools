@extends('layouts.app')
@section('title', 'Admin — Utilisateurs')

@section('content')
<div class="bg-white p-6 rounded-2xl shadow-sm">
  <h1 class="text-xl font-semibold mb-4">Utilisateurs</h1>

  <table class="w-full text-sm">
    <thead>
      <tr class="text-left border-b">
        <th class="py-2">ID</th>
        <th>Nom</th>
        <th>Email</th>
        <th>Admin</th>
        <th>Créé le</th>
      </tr>
    </thead>
    <tbody>
      @foreach($users as $u)
      <tr class="border-b">
        <td class="py-2">{{ $u->id }}</td>
        <td>{{ $u->name }}</td>
        <td>{{ $u->email }}</td>
        <td>
          @if($u->is_admin)
            <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-xs">Oui</span>
          @else
            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-xs">Non</span>
          @endif
        </td>
        <td>{{ $u->created_at?->format('d/m/Y') }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="mt-4">
    {{ $users->links() }}
  </div>
</div>
@endsection
