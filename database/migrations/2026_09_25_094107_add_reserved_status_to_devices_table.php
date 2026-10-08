<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Onderdeel: Reserveringsstatus beschikbaar maken in het databaseschema.
 * Eisen: FE-12, TE-02.
 * Bouw: T-22 (reserveren).
 * Geplande controle: T-23.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->enum('status', ['afgekeurd', 'in behandeling', 'goedgekeurd', 'onderhoud nodig', 'gereserveerd'])
                ->default('in behandeling')
                ->change();
        });
    }

    public function down(): void
    {
        if (DB::table('devices')->where('status', 'gereserveerd')->exists()) {
            throw new RuntimeException('Wijzig eerst de status van gereserveerde apparaten voordat deze migratie wordt teruggedraaid.');
        }

        Schema::table('devices', function (Blueprint $table): void {
            $table->enum('status', ['afgekeurd', 'in behandeling', 'goedgekeurd', 'onderhoud nodig'])
                ->default('in behandeling')
                ->change();
        });
    }
};
