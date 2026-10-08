<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Onderdeel: Apparaatstatus opslaan: in behandeling, goedgekeurd, afgekeurd, onderhoud nodig of gereserveerd.
 * Eisen: FE-04, FE-08, FE-09, FE-10, FE-12, TE-02.
 * Bouw: T-10 (status tonen), T-16 (keuringsstatussen), T-22 (reserveren).
 * Geplande controle: T-11, T-17, T-23.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('devices', 'status')) {
            Schema::table('devices', function (Blueprint $table): void {
                $table->enum('status', ['afgekeurd', 'in behandeling', 'goedgekeurd', 'onderhoud nodig', 'gereserveerd'])->default('in behandeling');
            });
        }
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->dropColumn('status');
        });
    }
};
