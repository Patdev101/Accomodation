<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // photos of the room shown on the public site: a JSON list of files on the public disk
        Schema::table('rooms', function (Blueprint $table) {
            $table->text('photos')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('photos');
        });
    }
};
