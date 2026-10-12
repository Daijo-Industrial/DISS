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
        Schema::create('project_approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();

            $table->string('stage_target', 30); // CLOSURE
            $table->string('decision', 20);     // APPROVED, REJECTED
            $table->foreignId('decided_by')->constrained('users');
            $table->text('comments')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_approvals');
    }
};
