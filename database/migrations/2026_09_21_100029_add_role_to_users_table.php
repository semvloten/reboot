<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Onderdeel: Accountrol opslaan met klant als standaardrol.
 * Eisen: FE-13, RV-02, TE-02.
 * Ontwerp: T-24 (roltoegang).
 * Bouw: T-25 (roltoegang).
 * Geplande controle: T-26.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('customer')->after('email');
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
            //
        });
    }
};
