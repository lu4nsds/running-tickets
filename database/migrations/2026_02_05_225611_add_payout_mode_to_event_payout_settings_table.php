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
        Schema::table('event_payout_settings', function (Blueprint $table) {
            $table->enum('payout_mode', ['direct', 'platform'])
                  ->default('direct')
                  ->after('method')
                  ->comment('direct: organizer MP account, platform: platform MP account with manual repasse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_payout_settings', function (Blueprint $table) {
            $table->dropColumn('payout_mode');
        });
    }
};
