<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retrospectives', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('project_id');
            $table->unsignedInteger('sprint_id')->nullable();
            $table->string('title');
            $table->string('status')->default('open'); // open, completed
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('sprint_id')->references('id')->on('sprints')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('retro_cards', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('retrospective_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('column_type'); // went_well, to_improve, action_item
            $table->text('content');
            $table->unsignedInteger('converted_task_id')->nullable();
            $table->timestamps();

            $table->foreign('retrospective_id')->references('id')->on('retrospectives')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('converted_task_id')->references('id')->on('tasks')->onDelete('set null');
        });

        Schema::create('retro_votes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('retro_card_id');
            $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->foreign('retro_card_id')->references('id')->on('retro_cards')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['retro_card_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retro_votes');
        Schema::dropIfExists('retro_cards');
        Schema::dropIfExists('retrospectives');
    }
};

