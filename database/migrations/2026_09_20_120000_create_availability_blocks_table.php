<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            // 'manual' — the provider marked the day busy themselves.
            // 'offer' — created automatically when a client accepted this provider's offer.
            $table->string('source')->default('manual');
            $table->foreignId('offer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            // One block per provider per day; blocking an already-blocked day is a no-op.
            $table->unique(['provider_profile_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_blocks');
    }
};
