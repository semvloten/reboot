<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Onderdeel: Eerder gemaakte zelfreserveringen herstellen door het apparaat weer beschikbaar te maken.
 * Eisen: FE-12, TE-02.
 * Bouw: T-34 (gevonden problemen oplossen).
 * Geplande controle: T-23, T-33.
 */
return new class extends Migration
{
    public function up(): void
    {
        $resetCount = DB::table('devices')
            ->where('status', 'gereserveerd')
            ->whereColumn('reserved_by_user_id', 'user_id')
            ->update([
                'status' => 'goedgekeurd',
                'reserved_by_user_id' => null,
                'updated_at' => now(),
            ]);

        echo PHP_EOL.'Zelfreserveringen teruggezet: '.$resetCount.PHP_EOL;
    }

    /**
     * Ongeldige zelfreserveringen worden bij terugdraaien niet hersteld.
     */
    public function down(): void {}
};
