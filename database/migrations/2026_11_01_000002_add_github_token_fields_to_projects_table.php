<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                if (!Schema::hasColumn('projects', 'git_token')) {
                    $table->text('git_token')->nullable()->after('git_provider');
                }
                if (!Schema::hasColumn('projects', 'git_webhook_secret')) {
                    $table->string('git_webhook_secret', 191)->nullable()->after('git_token');
                }
                if (!Schema::hasColumn('projects', 'git_auto_transition')) {
                    $table->boolean('git_auto_transition')->default(true)->after('git_webhook_secret');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                $cols = array_filter(
                    ['git_token', 'git_webhook_secret', 'git_auto_transition'],
                    fn ($col) => Schema::hasColumn('projects', $col)
                );
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};

