<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Drop foreign keys referencing vehicles(id)
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
        });

        Schema::table('service_records', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
        });

        Schema::table('vehicle_documents', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
        });

        Schema::table('vehicle_inspections', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
        });

        // 2. Add temporary uuid columns
        Schema::table('vehicles', function (Blueprint $table) {
            $table->uuid('uuid_id')->nullable()->after('id');
        });

        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->uuid('uuid_vehicle_id')->nullable()->after('vehicle_id');
        });

        Schema::table('service_records', function (Blueprint $table) {
            $table->uuid('uuid_vehicle_id')->nullable()->after('vehicle_id');
        });

        Schema::table('vehicle_documents', function (Blueprint $table) {
            $table->uuid('uuid_vehicle_id')->nullable()->after('vehicle_id');
        });

        Schema::table('vehicle_inspections', function (Blueprint $table) {
            $table->uuid('uuid_vehicle_id')->nullable()->after('vehicle_id');
        });

        // 3. Populate UUID for existing vehicles
        $vehicles = DB::table('vehicles')->get();
        foreach ($vehicles as $v) {
            $newUuid = (string) Str::uuid();
            DB::table('vehicles')->where('id', $v->id)->update(['uuid_id' => $newUuid]);
            DB::table('delivery_notes')->where('vehicle_id', $v->id)->update(['uuid_vehicle_id' => $newUuid]);
            DB::table('service_records')->where('vehicle_id', $v->id)->update(['uuid_vehicle_id' => $newUuid]);
            DB::table('vehicle_documents')->where('vehicle_id', $v->id)->update(['uuid_vehicle_id' => $newUuid]);
            DB::table('vehicle_inspections')->where('vehicle_id', $v->id)->update(['uuid_vehicle_id' => $newUuid]);
        }

        // 4. Update vehicles table to use UUID primary key
        DB::statement('ALTER TABLE vehicles MODIFY id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE vehicles DROP PRIMARY KEY');
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('id');
        });
        DB::statement('ALTER TABLE vehicles CHANGE uuid_id id CHAR(36) NOT NULL PRIMARY KEY FIRST');

        // 5. Update referencing tables
        // delivery_notes
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropColumn('vehicle_id');
        });
        DB::statement('ALTER TABLE delivery_notes CHANGE uuid_vehicle_id vehicle_id CHAR(36) NULL AFTER id');
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->nullOnDelete();
        });

        // service_records
        Schema::table('service_records', function (Blueprint $table) {
            $table->dropColumn('vehicle_id');
        });
        DB::statement('ALTER TABLE service_records CHANGE uuid_vehicle_id vehicle_id CHAR(36) NOT NULL AFTER id');
        Schema::table('service_records', function (Blueprint $table) {
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
        });

        // vehicle_documents
        Schema::table('vehicle_documents', function (Blueprint $table) {
            $table->dropColumn('vehicle_id');
        });
        DB::statement('ALTER TABLE vehicle_documents CHANGE uuid_vehicle_id vehicle_id CHAR(36) NOT NULL AFTER id');
        Schema::table('vehicle_documents', function (Blueprint $table) {
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
        });

        // vehicle_inspections
        Schema::table('vehicle_inspections', function (Blueprint $table) {
            $table->dropColumn('vehicle_id');
        });
        DB::statement('ALTER TABLE vehicle_inspections CHANGE uuid_vehicle_id vehicle_id CHAR(36) NOT NULL AFTER id');
        Schema::table('vehicle_inspections', function (Blueprint $table) {
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse if needed
    }
};
