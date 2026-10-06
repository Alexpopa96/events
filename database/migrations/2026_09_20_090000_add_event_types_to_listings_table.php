<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // null / empty = the listing serves every event type.
        Schema::table('listings', function (Blueprint $table) {
            $table->json('event_types')->nullable()->after('benefits');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('event_types');
        });
    }
};
