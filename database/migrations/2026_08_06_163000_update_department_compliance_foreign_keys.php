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
        Schema::table('department_compliance_snapshots', function (Blueprint $table) {
            // Drop legacy foreign key pointing to `departments` table
            $table->dropForeign(['department_id']);

            // Add foreign key pointing to `compliance_departments` table
            $table->foreign('department_id')
                ->references('id')
                ->on('compliance_departments')
                ->cascadeOnDelete();
        });

        Schema::table('department_compliance_monthlies', function (Blueprint $table) {
            // Drop legacy foreign key pointing to `departments` table
            $table->dropForeign(['department_id']);

            // Add foreign key pointing to `compliance_departments` table
            $table->foreign('department_id')
                ->references('id')
                ->on('compliance_departments')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('department_compliance_snapshots', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->cascadeOnDelete();
        });

        Schema::table('department_compliance_monthlies', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->cascadeOnDelete();
        });
    }
};
