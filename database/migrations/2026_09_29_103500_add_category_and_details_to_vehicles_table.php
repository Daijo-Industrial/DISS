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
            $table->string('category')->default('passenger')->after('model');
            $table->string('fuel_type')->default('petrol')->after('category');
            $table->boolean('requires_kir')->default(false)->after('fuel_type');
            $table->index(['category', 'requires_kir']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['category', 'requires_kir']);
            $table->dropColumn(['category', 'fuel_type', 'requires_kir']);
        });
    }
};
