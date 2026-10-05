<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vehicle_inspections', function (Blueprint $table) {
            $table->timestamp('checked_at')->nullable()->after('inspection_type');
        });

        // Backfill existing records so checked_at matches created_at
        DB::table('vehicle_inspections')
            ->whereNull('checked_at')
            ->update([
                'checked_at' => DB::raw('created_at'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_inspections', function (Blueprint $table) {
            $table->dropColumn('checked_at');
        });
    }
};
