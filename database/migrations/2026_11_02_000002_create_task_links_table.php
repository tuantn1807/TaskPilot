<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_links', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('source_task_id');
            $table->unsignedInteger('target_task_id');
            $table->string('link_type', 50); // blocks, is_blocked_by, relates_to, duplicates, is_duplicated_by, causes, is_caused_by
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('source_task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('target_task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['source_task_id', 'target_task_id', 'link_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_links');
    }
};

