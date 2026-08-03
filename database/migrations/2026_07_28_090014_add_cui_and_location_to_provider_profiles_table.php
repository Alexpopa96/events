<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->dropIndex(['city', 'county']);
            $table->dropColumn(['city', 'county']);

            $table->string('cui')->nullable()->unique()->after('company_name');
            $table->string('reg_com')->nullable()->after('cui');
            $table->foreignId('county_id')->nullable()->after('address')->constrained('counties')->nullOnDelete();
            $table->foreignId('locality_id')->nullable()->after('county_id')->constrained('localities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('locality_id');
            $table->dropConstrainedForeignId('county_id');
            $table->dropColumn(['cui', 'reg_com']);

            $table->string('city')->nullable();
            $table->string('county')->nullable();
            $table->index(['city', 'county']);
        });
    }
};
