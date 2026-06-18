<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('external_id');
            $table->string('provider_team_id')->nullable()->after('provider');

            $table->unique(['provider', 'provider_team_id']);
        });

        Schema::table('fixtures', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('external_id');
            $table->string('provider_match_id')->nullable()->after('provider');

            $table->unique(['provider', 'provider_match_id']);
        });
    }

    public function down(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->dropUnique(['provider', 'provider_match_id']);
            $table->dropColumn(['provider', 'provider_match_id']);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropUnique(['provider', 'provider_team_id']);
            $table->dropColumn(['provider', 'provider_team_id']);
        });
    }
};
