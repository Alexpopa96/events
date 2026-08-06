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
        if (DB::getDriverName() === 'sqlite') {
            // SQLite has no ALTER ... MODIFY; widening the enum's CHECK
            // constraint means rebuilding the column via a native change().
            Schema::table('quote_requests', function (Blueprint $table) {
                $table->string('status')->default('pending_review')->change();
            });
        } else {
            DB::statement("ALTER TABLE quote_requests MODIFY status ENUM('pending_review', 'open', 'rejected', 'closed') NOT NULL DEFAULT 'pending_review'");
        }

        Schema::table('quote_requests', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('rejection_reason');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropColumn(['rejection_reason', 'approved_at', 'rejected_at']);
        });

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('quote_requests', function (Blueprint $table) {
                $table->string('status')->default('open')->change();
            });
        } else {
            DB::statement("ALTER TABLE quote_requests MODIFY status ENUM('open', 'closed') NOT NULL DEFAULT 'open'");
        }
    }
};
