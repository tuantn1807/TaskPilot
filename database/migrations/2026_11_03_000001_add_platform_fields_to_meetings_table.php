<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('platform', 50)->default('taskpilot')->after('room_name');
            $table->string('meeting_link', 1000)->nullable()->after('platform');
            $table->string('location', 255)->nullable()->after('meeting_link');
            $table->string('passcode', 100)->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['platform', 'meeting_link', 'location', 'passcode']);
        });
    }
};

