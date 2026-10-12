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
        Schema::create('checkpoint_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('checkpoint_id')->constrained('project_checkpoints')->cascadeOnDelete();

            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type', 100);

            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();

            $table->index('checkpoint_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkpoint_attachments');
    }
};
