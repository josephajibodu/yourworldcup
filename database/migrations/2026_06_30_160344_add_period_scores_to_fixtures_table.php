<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->unsignedTinyInteger('extra_time_home')->nullable()->after('away_score');
            $table->unsignedTinyInteger('extra_time_away')->nullable()->after('extra_time_home');
            $table->unsignedTinyInteger('penalties_home')->nullable()->after('extra_time_away');
            $table->unsignedTinyInteger('penalties_away')->nullable()->after('penalties_home');
            $table->string('result_duration')->nullable()->after('penalties_away');
        });
    }

    public function down(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->dropColumn([
                'extra_time_home',
                'extra_time_away',
                'penalties_home',
                'penalties_away',
                'result_duration',
            ]);
        });
    }
};
