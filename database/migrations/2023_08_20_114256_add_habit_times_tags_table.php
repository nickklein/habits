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
        // create table called habit_times_tags with the columns id, habit_time_id (int), tag_id (int), (no timestamps)
        Schema::create('habit_times_tags', function (Blueprint $table) {
            $table->id();
            $table->integer('habit_time_id');
            $table->integer('tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('habit_times_tags');
    }
};
