<?php

use App\Http\Controllers\DeviceController;
use Illuminate\Support\Facades\Route;

/**
 * Onderdeel: Verbindt winkel-, klant- en keurmeesterfuncties aan routes met authenticatie en rolmiddleware.
 * Eisen: RV-01, RV-02, FE-13, TE-06.
 * Ontwerp: T-24 (roltoegang).
 * Bouw: T-25 (roltoegang), T-31 (structuur).
 * Geplande controle: T-26, T-32.
 */
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return to_route('dashboard');
    })->name('home');

    Route::get('dashboard', fn () => to_route('shop.index'))->name('dashboard');
    Route::get('/winkel', [DeviceController::class, 'shop'])->name('shop.index');
    Route::get('/winkel/{device}/foto', [DeviceController::class, 'shopPhoto'])->name('shop.photo');
    Route::get('/winkel/{device}/betalen', [DeviceController::class, 'checkout'])->name('shop.checkout');
    Route::post('/winkel/{device}/reserveren', [DeviceController::class, 'reserve'])->name('shop.reserve');
    Route::get('/winkel/{device}', [DeviceController::class, 'shopShow'])->name('shop.show');

    Route::middleware('inspector')->group(function () {
        Route::get('/inspector', fn () => to_route('inspector.devices.index'))->name('inspector');
        Route::get('/apparaten-keuren-overzicht', [DeviceController::class, 'inspectorIndex'])->name('inspector.devices.index');
        Route::get('/apparaten-keuren/{device}', [DeviceController::class, 'inspect'])->name('inspector.devices.inspect');
        Route::patch('/apparaten-keuren/{device}/toewijzing', [DeviceController::class, 'updateTriage'])->name('inspector.devices.triage');
        Route::patch('/apparaten-keuren/{device}', [DeviceController::class, 'updateInspection'])->name('inspector.devices.update');
    });

    Route::get('/mijn-apparaten/{device}/foto', [DeviceController::class, 'photo'])->name('devices.photo');
    Route::get('/mijn-apparaten', [DeviceController::class, 'index'])->name('devices.index');
    Route::get('/mijn-reserveringen', [DeviceController::class, 'reservations'])->name('devices.reservations');

    Route::get('/apparaat-aanmelden', [DeviceController::class, 'create'])->name('devices.create');
    Route::post('/apparaat-aanmelden', [DeviceController::class, 'store'])->name('devices.store');

    require __DIR__.'/settings.php';
});

require __DIR__.'/auth.php';
