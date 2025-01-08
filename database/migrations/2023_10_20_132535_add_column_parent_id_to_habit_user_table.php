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
            // Add column called parent_id
            $table->unsignedBigInteger('parent_id')->nullable()->after('habit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('habit_user', function (Blueprint $table) {
            // Drop column called parent_id
            $table->dropColumn('parent_id');
        });
    }
};
