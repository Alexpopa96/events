<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_readings', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('location_key');
            $table->string('location_name')->nullable();
            $table->decimal('min_celsius', 4, 1);
            $table->decimal('max_celsius', 4, 1);
            // Mean of the 24 hourly values when available, otherwise (min + max) / 2.
            $table->decimal('avg_celsius', 4, 1);
            $table->boolean('avg_from_hourly')->default(false);
            $table->boolean('has_precipitation')->default(false);
            $table->string('precipitation_type')->nullable();
            $table->unsignedTinyInteger('precipitation_probability')->default(0);
            $table->decimal('rain_mm', 5, 1)->default(0);
            $table->decimal('snow_cm', 5, 1)->default(0);
            $table->decimal('total_liquid_mm', 5, 1)->default(0);
            $table->decimal('hours_of_precipitation', 4, 1)->default(0);
            // Event time window for this date and the forecast inside it; from AccuWeather's
            // hourly data within 5 days, otherwise estimated from the daily min/max.
            $table->unsignedTinyInteger('window_from');
            $table->unsignedTinyInteger('window_to');
            $table->decimal('window_min_celsius', 4, 1);
            $table->decimal('window_max_celsius', 4, 1);
            $table->unsignedTinyInteger('window_precipitation_probability');
            $table->boolean('window_from_hourly')->default(false);
            $table->decimal('hours_of_sun', 4, 1)->nullable();
            $table->string('day_phrase')->nullable();
            $table->string('night_phrase')->nullable();
            $table->string('link')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamp('sent_to_telegram_at')->nullable();
            $table->timestamps();

            $table->unique(['date', 'location_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_readings');
    }
};
