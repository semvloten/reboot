<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Onderdeel: Apparaten met eigenaar, uniek ID en merk, model, conditie, accessoires, vraagprijs en foto's opslaan.
 * Eisen: FE-02, FE-03, RV-04, TE-02.
 * Bouw: T-07 (apparaat aanmelden en gegevens opslaan).
 * Geplande controle: T-08.
 * Toelichting: De latere migratie voor active_serial_number beperkt serienummeruniciteit tot actieve apparaten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('brand', 100);
            $table->string('model', 100);
            $table->string('serial_number', 100)->unique();
            $table->string('condition', 20);
            $table->text('accessories')->nullable();
            $table->decimal('asking_price', 10, 2);
            $table->json('photos')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
