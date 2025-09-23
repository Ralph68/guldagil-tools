<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');

/* Landing publique */
Route::get('/', function () {
    // Si déjà connecté → redirige directement vers l’accueil privée
    if (auth()->check()) {
        return redirect()->route('home');
    }
    return view('landing'); // vue publique
})->name('landing');

/* Zone privée */
Route::middleware('auth')->group(function () {
    Route::get('/app', [HomeController::class, 'index'])->name('home');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
