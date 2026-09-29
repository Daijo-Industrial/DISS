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
        Schema::create('vehicle_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('parent_inspection_id')->nullable()->constrained('vehicle_inspections')->nullOnDelete();
            $table->string('inspection_type'); // 'check_out', 'check_in'
            $table->string('driver_name');
            $table->foreignId('inspector_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('odometer');
            $table->unsignedTinyInteger('fuel_percentage')->default(100);
            $table->json('checklist_results');
            $table->string('severity')->default('none'); // 'none', 'minor', 'critical_grounded'
            $table->text('defect_notes')->nullable();
            $table->json('defect_photos')->nullable();
            $table->text('trip_purpose')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'inspection_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_inspections');
    }
};
