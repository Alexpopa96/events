<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->text('provider_reply')->nullable()->after('comment');
            $table->timestamp('provider_replied_at')->nullable()->after('provider_reply');
            $table->timestamp('moderated_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['provider_reply', 'provider_replied_at', 'moderated_at']);
        });
    }
};
