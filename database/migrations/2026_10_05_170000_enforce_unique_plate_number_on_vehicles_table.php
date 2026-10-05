<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Drop composite unique that permitted duplicate plate numbers when deleted_at was null
            $table->dropUnique('vehicles_plate_number_deleted_at_unique');
            // Enforce strict uniqueness on plate_number
            $table->unique('plate_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropUnique(['plate_number']);
            $table->unique(['plate_number', 'deleted_at']);
        });
    }
};
