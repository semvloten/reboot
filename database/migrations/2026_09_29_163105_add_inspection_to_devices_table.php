<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Onderdeel: Checklist, opmerkingen, uitvoerende keurmeester en keuringsdatum bij het apparaat opslaan.
 * Eisen: FE-05, FE-07, TE-02.
 * Bouw: T-10 (keuringsopmerkingen), T-13 (checklist).
 * Geplande controle: T-11, T-14.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->json('inspection')->nullable();
            $table->foreignId('inspected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('inspected_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('inspected_by_user_id');
            $table->dropColumn(['inspection', 'inspected_at']);
        });
    }
};
