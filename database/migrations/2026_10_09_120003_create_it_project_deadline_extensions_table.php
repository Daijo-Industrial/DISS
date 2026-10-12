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
        Schema::create('project_deadline_extensions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();

            $table->date('old_due_date');
            $table->date('new_due_date');
            $table->string('reason_category', 100);
            $table->text('reason_details');

            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_deadline_extensions');
    }
};
