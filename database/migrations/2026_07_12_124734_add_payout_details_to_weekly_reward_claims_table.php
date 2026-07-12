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
        Schema::table('weekly_reward_claims', function (Blueprint $table) {
            $table->string('phone_number', 20)->nullable()->after('preference');
            $table->string('mobile_network')->nullable()->after('phone_number');
            $table->string('account_holder_name')->nullable()->after('mobile_network');
            $table->string('bank_name')->nullable()->after('account_holder_name');
            $table->string('account_number', 20)->nullable()->after('bank_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_reward_claims', function (Blueprint $table) {
            $table->dropColumn([
                'phone_number',
                'mobile_network',
                'account_holder_name',
                'bank_name',
                'account_number',
            ]);
        });
    }
};
