<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Reception may enter the real arrival / leaving time (up to 24 hours back) when it is encoded later.
// These keep the reason, when it was typed in, and who typed it.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('checkin_time_reason')->nullable();
            $table->timestamp('checkin_recorded_at')->nullable();
            $table->string('checkin_recorded_by')->nullable();
            $table->string('checkout_time_reason')->nullable();
            $table->timestamp('checkout_recorded_at')->nullable();
            $table->string('checkout_recorded_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'checkin_time_reason',
                'checkin_recorded_at',
                'checkin_recorded_by',
                'checkout_time_reason',
                'checkout_recorded_at',
                'checkout_recorded_by',
            ]);
        });
    }
};
