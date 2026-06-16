<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixture_markets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prediction_market_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('base_points')->nullable();
            $table->timestamp('lock_at')->nullable();
            $table->json('options')->nullable();
            $table->json('settlement_value')->nullable();
            $table->string('status')->default('open');
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();

            $table->unique(['fixture_id', 'prediction_market_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixture_markets');
    }
};
