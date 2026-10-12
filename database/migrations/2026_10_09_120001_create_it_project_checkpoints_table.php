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
        Schema::create('project_checkpoints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();

            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->unsignedInteger('sequence_order')->default(1);
            $table->boolean('is_required')->default(true);

            $table->string('status', 30)->default('PENDING');
            $table->date('due_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users');

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'sequence_order']);
            $table->index(['project_id', 'is_required', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_checkpoints');
    }
};
