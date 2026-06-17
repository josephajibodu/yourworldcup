<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bracket_slots', function (Blueprint $table) {
            $table->dropUnique(['stage', 'position']);
        });

        Schema::table('bracket_slots', function (Blueprint $table) {
            $table->dropColumn(['stage', 'position']);
            $table->string('side', 8)->after('id');
            $table->string('slot_type')->after('side');
            $table->json('slot_spec')->after('slot_type');
            $table->unique(['feeds_fixture_id', 'side']);
        });
    }

    public function down(): void
    {
        Schema::table('bracket_slots', function (Blueprint $table) {
            $table->dropUnique(['feeds_fixture_id', 'side']);
            $table->dropColumn(['side', 'slot_type', 'slot_spec']);
            $table->string('stage')->after('id');
            $table->unsignedInteger('position')->after('stage');
            $table->unique(['stage', 'position']);
        });
    }
};
