<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE provider_profiles MODIFY status ENUM('pending', 'active', 'rejected', 'suspended') NOT NULL DEFAULT 'pending'");

        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('approved_at');
            $table->text('suspension_reason')->nullable()->after('rejection_reason');
            $table->timestamp('rejected_at')->nullable()->after('suspension_reason');
            $table->timestamp('suspended_at')->nullable()->after('rejected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->dropColumn(['rejection_reason', 'suspension_reason', 'rejected_at', 'suspended_at']);
        });

        DB::statement("ALTER TABLE provider_profiles MODIFY status ENUM('pending', 'active', 'suspended') NOT NULL DEFAULT 'pending'");
    }
};
