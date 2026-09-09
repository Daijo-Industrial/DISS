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
        Schema::table('evaluation_datas', function (Blueprint $table) {
            if (! Schema::hasColumn('evaluation_datas', 'created_at')) {
                $table->timestamps();
            }
        });

        // Backfill existing rows with current timestamp
        DB::table('evaluation_datas')
            ->whereNull('created_at')
            ->update([
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation_datas', function (Blueprint $table) {
            if (Schema::hasColumn('evaluation_datas', 'created_at')) {
                $table->dropTimestamps();
            }
        });
    }
};
