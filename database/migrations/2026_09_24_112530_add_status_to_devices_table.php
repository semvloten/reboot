<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('devices', 'status')) {
            Schema::table('devices', function (Blueprint $table): void {
                $table->enum('status', ['afgekeurd', 'in behandeling', 'goedgekeurd', 'onderhoud nodig'])->default('in behandeling');
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
