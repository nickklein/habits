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
        Schema::table('habit_user', function (Blueprint $table) {
            // Add archive column
            $table->boolean('archive')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('habit_user', function (Blueprint $table) {
            // Drop archive column
            $table->dropColumn('archive');
        });
    }
};
