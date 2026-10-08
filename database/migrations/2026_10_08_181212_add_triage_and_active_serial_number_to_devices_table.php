<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->unsignedTinyInteger('priority')->default(1);
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('active_serial_number', 100)
                ->nullable()
                ->virtualAs("CASE WHEN status <> 'afgekeurd' THEN UPPER(TRIM(serial_number)) ELSE NULL END");
            $table->unique('active_serial_number');
        });

        Schema::table('devices', function (Blueprint $table): void {
            $table->dropUnique('devices_serial_number_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('devices')->select('serial_number')->groupBy('serial_number')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Er bestaan meerdere apparaten met hetzelfde serienummer. Los dit eerst op voordat de migratie wordt teruggedraaid.');
        }

        Schema::table('devices', function (Blueprint $table): void {
            $table->unique('serial_number');
            $table->dropUnique('devices_active_serial_number_unique');
            $table->dropColumn('active_serial_number');
            $table->dropConstrainedForeignId('assigned_to_user_id');
            $table->dropColumn('priority');
        });
    }
};
