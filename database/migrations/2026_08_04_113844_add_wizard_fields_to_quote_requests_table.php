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
            $table->string('title')->nullable()->after('category_id');
            $table->string('event_type')->nullable()->after('event_date');
            $table->string('guest_count')->nullable()->after('city');
            $table->json('preferences')->nullable()->after('budget_range');
            $table->text('notes')->nullable()->after('message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropColumn(['title', 'event_type', 'guest_count', 'preferences', 'notes']);
        });
    }
};
