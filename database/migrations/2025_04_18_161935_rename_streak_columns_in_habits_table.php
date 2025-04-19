<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE habit_user CHANGE COLUMN streak_time_goal streak_goal INT(11) NULL");
        DB::statement("ALTER TABLE habit_user CHANGE COLUMN streak_type habit_type VARCHAR(255) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE habit_user CHANGE COLUMN streak_goal streak_time_goal INT(11) NULL");
        DB::statement("ALTER TABLE habit_user CHANGE COLUMN habit_type streak_type VARCHAR(255) NULL");
    }
};
