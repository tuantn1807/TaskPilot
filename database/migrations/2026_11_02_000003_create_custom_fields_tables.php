<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('project_id')->nullable(); // nullable = global field or project-specific
            $table->string('name', 100);
            $table->string('field_key', 50); // e.g. environment, severity, os_version
            $table->string('field_type', 30); // text, number, date, select, checkbox, url
            $table->json('options')->nullable(); // for select dropdown: ["Dev", "Staging", "Prod"]
            $table->boolean('is_required')->default(false);
            $table->text('description')->nullable();
            $table->integer('order_index')->default(0);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['project_id', 'field_key']);
        });

        Schema::create('task_custom_field_values', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('task_id');
            $table->unsignedInteger('custom_field_id');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('custom_field_id')->references('id')->on('custom_fields')->onDelete('cascade');
            $table->unique(['task_id', 'custom_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_custom_field_values');
        Schema::dropIfExists('custom_fields');
    }
};

