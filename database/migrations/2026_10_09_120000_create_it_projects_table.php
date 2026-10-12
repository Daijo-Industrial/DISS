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
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('project_code', 50)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();

            $table->string('requester_name', 150);
            $table->foreignId('requester_department_id')->constrained('departments');
            $table->foreignId('primary_pic_id')->constrained('users');

            $table->string('priority', 20)->default('MEDIUM');
            $table->string('stage', 30)->default('DRAFT');
            $table->string('status', 20)->default('ACTIVE');

            $table->date('start_date')->nullable();
            $table->date('original_due_date');
            $table->date('due_date');
            $table->date('finished_date')->nullable();

            $table->decimal('progress_percent', 5, 2)->default(0.00);
            $table->unsignedInteger('deadline_extension_count')->default(0);

            $table->timestamp('paused_at')->nullable();
            $table->timestamp('resumed_at')->nullable();
            $table->text('hold_reason')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason_category', 100)->nullable();
            $table->text('cancellation_notes')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'stage']);
            $table->index(['primary_pic_id', 'status']);
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
