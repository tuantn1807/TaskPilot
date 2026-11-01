<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_development_links')) {
            Schema::table('project_development_links', function (Blueprint $table) {
                if (!Schema::hasColumn('project_development_links', 'task_id')) {
                    $table->unsignedInteger('task_id')->nullable()->after('user_id');
                    $table->index('task_id');
                }
                if (!Schema::hasColumn('project_development_links', 'branch_name')) {
                    $table->string('branch_name')->nullable()->after('label');
                }
                if (!Schema::hasColumn('project_development_links', 'commit_sha')) {
                    $table->string('commit_sha', 64)->nullable()->after('branch_name');
                }
                if (!Schema::hasColumn('project_development_links', 'author_name')) {
                    $table->string('author_name')->nullable()->after('commit_sha');
                }
                if (!Schema::hasColumn('project_development_links', 'repo_name')) {
                    $table->string('repo_name')->nullable()->after('author_name');
                }
            });
        }

        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                if (!Schema::hasColumn('projects', 'git_repo_url')) {
                    $table->string('git_repo_url', 500)->nullable()->after('workflow_config');
                }
                if (!Schema::hasColumn('projects', 'git_default_branch')) {
                    $table->string('git_default_branch', 100)->nullable()->default('develop')->after('git_repo_url');
                }
                if (!Schema::hasColumn('projects', 'git_provider')) {
                    $table->string('git_provider', 50)->nullable()->default('github')->after('git_default_branch');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_development_links')) {
            Schema::table('project_development_links', function (Blueprint $table) {
                $cols = array_filter(
                    ['task_id', 'branch_name', 'commit_sha', 'author_name', 'repo_name'],
                    fn ($col) => Schema::hasColumn('project_development_links', $col)
                );
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                $cols = array_filter(
                    ['git_repo_url', 'git_default_branch', 'git_provider'],
                    fn ($col) => Schema::hasColumn('projects', $col)
                );
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};

