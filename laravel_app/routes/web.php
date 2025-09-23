<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Placeholders de modules (à remplacer par tes contrôleurs au fur et à mesure)
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
Route::prefix('admin')->group(function () {
    Route::get('/', fn() => view('modules.admin.index'))->name('admin.index');
});
Route::prefix('user')->group(function () {
    Route::get('/', fn() => view('modules.user.index'))->name('user.index');
});

// Auth minimal (à remplacer plus tard par Breeze/Jetstream/fortify)
Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');
Route::post('/logout', function () {
    Auth::logout();
    return redirect()->route('home');
})->name('logout');
