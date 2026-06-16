<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bracket_slots', function (Blueprint $table) {
            $table->id();
            $table->string('stage');
            $table->unsignedInteger('position');
            $table->string('label')->nullable();
            $table->foreignId('resolved_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('source_fixture_id')->nullable()->constrained('fixtures')->nullOnDelete();
            $table->foreignId('feeds_fixture_id')->nullable()->constrained('fixtures')->nullOnDelete();
            $table->timestamps();

            $table->unique(['stage', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bracket_slots');
    }
};
