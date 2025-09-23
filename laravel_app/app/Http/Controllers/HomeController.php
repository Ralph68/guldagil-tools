<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Affiche une petite page d'accueil privée
        return view('home', compact('user'));
    }
}
