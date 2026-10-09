<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // an online reservation waits (status "Pending") until reception or the admin
        // has checked the uploaded ID and approved or declined it
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('id_photo')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->string('decline_reason')->nullable();
            // when the guest saw the "confirmed" or "declined" message on the site
            $table->timestamp('guest_seen_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['id_photo', 'reviewed_at', 'decline_reason', 'guest_seen_at']);
        });
    }
};
