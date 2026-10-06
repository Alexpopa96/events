<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            // Set only when the CUI was actually confirmed live against ANAF (at
            // registration, or later by an admin) — never backfilled for existing
            // rows, since plenty of those (demo/seed data) were never really checked.
            $table->timestamp('anaf_verified_at')->nullable()->after('reg_com');
            $table->string('anaf_status')->nullable()->after('anaf_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->dropColumn(['anaf_verified_at', 'anaf_status']);
        });
    }
};
