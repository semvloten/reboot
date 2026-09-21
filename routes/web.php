<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\User;


Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

Route::get('/db-test', function () {
    $userCount = User::count();

    return view('db-test', [
        'userCount' => $userCount,
    ]);
});

Route::middleware(['auth', 'inspector'])->get('/inspector', function () {
    return 'Keurmeesterpaneel';
})->name('inspector');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
