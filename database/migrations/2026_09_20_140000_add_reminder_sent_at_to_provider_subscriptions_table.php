<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_subscriptions', function (Blueprint $table) {
            // Set once a renewal-reminder email goes out, so the daily check never sends it twice.
            $table->timestamp('reminder_sent_at')->nullable()->after('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('provider_subscriptions', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }
};
