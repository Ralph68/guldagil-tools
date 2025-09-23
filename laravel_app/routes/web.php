<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Accueil
Route::get('/', function () {
    return view('welcome');
})->name('home');

/**
 * Placeholders de modules (OK)
 * NB: on RETIRE le prefix('admin') placeholder ci-dessous
 */
Route::prefix('adr')->group(function () {
    Route::get('/', fn() => view('modules.adr.index'))->name('adr.index');
});
Route::prefix('epi')->group(function () {
    Route::get('/', fn() => view('modules.epi.index'))->name('epi.index');
});
Route::prefix('materiel')->group(function () {
    Route::get('/', fn() => view('modules.materiel.index'))->name('materiel.index');
});
Route::prefix('qualite')->group(function () {
    Route::get('/', fn() => view('modules.qualite.index'))->name('qualite.index');
});
Route::prefix('port')->group(function () {
    Route::get('/', fn() => view('modules.port.index'))->name('port.index');
});
Route::prefix('api')->group(function () {
    Route::get('/', fn() => view('modules.api.index'))->name('api.index');
});
Route::prefix('legal')->group(function () {
    Route::get('/', fn() => view('modules.legal.index'))->name('legal.index');
});

// ⚠️ SUPPRIMÉ : ancien placeholder admin qui renvoyait juste une vue
// Route::prefix('admin')->group(function () {
//     Route::get('/', fn() => view('modules.admin.index'))->name('admin.index');
// });

Route::prefix('user')->group(function () {
    Route::get('/', fn() => view('modules.user.index'))->name('user.index');
});

// Auth minimal (provisoire)
Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');
Route::post('/logout', function () {
    Auth::logout();
    return redirect()->route('home');
})->name('logout');

// --- ADMIN ---

// ⬇️ Import du contrôleur (corrige Intelephense P1009)
use App\Http\Controllers\Admin\AdminController;

/**
 * Groupe /admin avec middlewares
 * - 'auth' = nécessite connexion
 * - 'admin' = nécessite rôle admin (middleware ci-dessous)
 */
Route::prefix('admin')
    ->middleware(['auth','admin'])
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    });
