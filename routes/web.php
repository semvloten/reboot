<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return to_route('dashboard');
    })->name('home');

    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('/db-test', function () {
        $userCount = User::count();

        return view('db-test', [
            'userCount' => $userCount,
        ]);
    });

    Route::middleware('inspector')->get('/inspector', function () {
        return 'Keurmeesterpaneel';
    })->name('inspector');

    require __DIR__.'/settings.php';
});

require __DIR__.'/auth.php';
