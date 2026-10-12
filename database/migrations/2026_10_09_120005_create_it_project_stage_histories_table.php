<?php

declare(strict_types=1);

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
        Schema::create('project_stage_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();

            $table->string('from_stage', 30);
            $table->string('to_stage', 30);
            $table->foreignId('changed_by')->constrained('users');
            $table->boolean('is_override')->default(false);
            $table->text('reason')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_stage_histories');
    }
};
