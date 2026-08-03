<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_profile_id')->constrained('provider_profiles')->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained('listings')->cascadeOnDelete();
            $table->foreignId('quote_request_id')->nullable()->constrained('quote_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', [
                'view',
                'phone_click',
                'whatsapp_click',
                'email_click',
                'contact_form_submit',
                'quote_request_view',
            ]);
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['provider_profile_id', 'type', 'created_at']);
            $table->index(['listing_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_events');
    }
};
