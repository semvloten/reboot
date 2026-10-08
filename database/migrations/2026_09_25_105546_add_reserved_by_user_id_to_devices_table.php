<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Onderdeel: Een reservering aan de reserverende klant koppelen.
 * Eisen: FE-12, RV-02, TE-02.
 * Bouw: T-22 (reserveren).
 * Geplande controle: T-23.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->foreignId('reserved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reserved_by_user_id');
        });
    }
};
