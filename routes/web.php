<?php

use App\Http\Controllers\DeviceController;
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

    Route::get('/mijn-apparaten/{device}/foto', [DeviceController::class, 'photo'])->name('devices.photo');
    Route::get('/mijn-apparaten', [DeviceController::class, 'index'])->name('devices.index');

    Route::get('/apparaat-aanmelden', [DeviceController::class, 'create'])->name('devices.create');
    Route::post('/apparaat-aanmelden', [DeviceController::class, 'store'])->name('devices.store');

    require __DIR__.'/settings.php';
});

require __DIR__.'/auth.php';
