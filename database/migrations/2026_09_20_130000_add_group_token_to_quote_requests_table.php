<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            // Set only when a client submits several categories at once as a package
            // (e.g. photographer + DJ + venue for the same wedding); null for a lone request.
            $table->uuid('group_token')->nullable()->after('user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropColumn('group_token');
        });
    }
};
