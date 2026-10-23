<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Set while staff record a payment taken outside Stripe, so expiring the Checkout Session doesn't look like an
 * abandoned checkout and no new session opens meanwhile.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('offline_settlement_at')->nullable();
            $table->string('offline_settlement_token', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['offline_settlement_at', 'offline_settlement_token']);
        });
    }
};
