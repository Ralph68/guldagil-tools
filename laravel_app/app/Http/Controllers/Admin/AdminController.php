<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class AdminController extends Controller
{
    // Dashboard admin
    public function index()
    {
        // Stats rapides (placeholder)
        $usersCount = User::count();
        $adminsCount = User::where('is_admin', true)->count();

        return view('modules.admin.index', compact('usersCount','adminsCount'));
    }

    // Liste utilisateurs (placeholder simple)
    public function users()
    {
        $users = User::select('id','name','email','is_admin','created_at')->orderBy('id','desc')->paginate(20);
        return view('modules.admin.users', compact('users'));
    }

    // Réglages (placeholder)
    public function settings()
    {
        return view('modules.admin.settings');
    }
}
