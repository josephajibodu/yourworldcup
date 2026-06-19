<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('is_banker');
        });

        DB::table('predictions')
            ->whereNull('submitted_at')
            ->update(['submitted_at' => DB::raw('created_at')]);

        Schema::table('predictions', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropColumn('submitted_at');
        });
    }
};
