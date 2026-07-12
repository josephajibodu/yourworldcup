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
        Schema::create('weekly_reward_claims', function (Blueprint $table) {
            $table->id();
            $table->date('week_start');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('reward_position');
            $table->unsignedTinyInteger('leaderboard_rank');
            $table->string('preference')->nullable();
            $table->boolean('passed_on')->default(false);
            $table->text('pass_on_message')->nullable();
            $table->timestamps();

            $table->unique(['week_start', 'user_id', 'reward_position']);
            $table->index(['week_start', 'reward_position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_reward_claims');
    }
};
