<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable()->index();
            $table->string('name');
            $table->string('code', 3)->unique();
            $table->string('iso2', 8)->nullable();
            $table->char('group_code', 1)->nullable()->index();
            $table->string('confederation')->nullable();
            $table->boolean('is_african')->default(false);
            $table->string('flag')->nullable();
            $table->timestamp('eliminated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
