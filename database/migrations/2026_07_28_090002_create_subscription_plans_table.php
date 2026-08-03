<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('RON');
            $table->enum('billing_period', ['monthly', 'yearly'])->default('monthly');
            $table->unsignedInteger('max_listings')->nullable();
            $table->unsignedInteger('max_photos_per_listing')->nullable();
            $table->unsignedInteger('max_videos_per_listing')->nullable();
            $table->boolean('allows_featured_placement')->default(false);
            $table->json('features')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
