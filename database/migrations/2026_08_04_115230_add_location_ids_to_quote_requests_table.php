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
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->foreignId('county_id')->nullable()->after('event_type')->constrained('counties')->nullOnDelete();
            $table->foreignId('locality_id')->nullable()->after('county_id')->constrained('localities')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('locality_id');
            $table->dropConstrainedForeignId('county_id');
        });
    }
};
