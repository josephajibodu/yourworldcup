<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->string('referee')->nullable()->after('stadium_id');
            $table->index('referee');
        });
    }

    public function down(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->dropIndex(['referee']);
            $table->dropColumn('referee');
        });
    }
};
