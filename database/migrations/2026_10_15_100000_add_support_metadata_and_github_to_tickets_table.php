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
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('reporter_email')->nullable()->after('reporter_id')->comment('Contact email for notifications or unauthenticated help');
            $table->text('current_url')->nullable()->after('description')->comment('Page URL or route where issue occurred');
            $table->string('screenshot_path')->nullable()->after('current_url')->comment('Path to uploaded screenshot in storage');
            $table->text('device_info')->nullable()->after('screenshot_path')->comment('Client browser, OS, and platform metadata');
            $table->string('github_issue_url')->nullable()->after('reopen_count')->comment('Escalated GitHub issue link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'reporter_email',
                'current_url',
                'screenshot_path',
                'device_info',
                'github_issue_url',
            ]);
        });
    }
};
