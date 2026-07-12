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
        Schema::table('weekly_reward_claims', function (Blueprint $table) {
            $table->dropUnique(['week_start', 'user_id', 'reward_position']);
            $table->unsignedTinyInteger('reward_position')->nullable()->change();
            $table->unique(['week_start', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_reward_claims', function (Blueprint $table) {
            $table->dropUnique(['week_start', 'user_id']);
            $table->unsignedTinyInteger('reward_position')->nullable(false)->change();
            $table->unique(['week_start', 'user_id', 'reward_position']);
        });
    }
};
