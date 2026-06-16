<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fixture_market_id')->constrained()->cascadeOnDelete();
            $table->json('value');
            $table->boolean('is_banker')->default(false);
            $table->integer('points_awarded')->nullable();
            $table->timestamp('scored_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'fixture_market_id']);
            $table->index(['user_id', 'is_banker']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
