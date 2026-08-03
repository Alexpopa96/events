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
        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex(['city', 'county']);
            $table->dropColumn(['city', 'county']);

            $table->foreignId('county_id')->nullable()->after('currency')->constrained('counties')->nullOnDelete();
            $table->foreignId('locality_id')->nullable()->after('county_id')->constrained('localities')->nullOnDelete();
            $table->index(['county_id', 'locality_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex(['county_id', 'locality_id']);
            $table->dropConstrainedForeignId('county_id');
            $table->dropConstrainedForeignId('locality_id');

            $table->string('city')->nullable();
            $table->string('county')->nullable();
            $table->index(['city', 'county']);
        });
    }
};
