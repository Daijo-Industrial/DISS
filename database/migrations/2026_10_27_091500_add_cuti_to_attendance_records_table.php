<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance_records', 'cuti')) {
                $table->unsignedTinyInteger('cuti')->default(0)->after('telat');
            }
        });

        // Migrate historical data: previously JPayroll CT was populated into izin
        DB::statement('UPDATE attendance_records SET cuti = izin, izin = 0 WHERE izin > 0');
    }

    public function down(): void
    {
        // Revert historical data migration if rolling back
        DB::statement('UPDATE attendance_records SET izin = cuti WHERE cuti > 0');

        Schema::table('attendance_records', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_records', 'cuti')) {
                $table->dropColumn('cuti');
            }
        });
    }
};
