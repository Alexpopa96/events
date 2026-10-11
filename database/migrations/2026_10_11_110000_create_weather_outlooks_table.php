<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Long-range (ensemble) outlook from Open-Meteo, used before the tracked dates
        // enter AccuWeather's 15-day window. Values summarise all ensemble members.
        Schema::create('weather_outlooks', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('source');
            $table->unsignedSmallInteger('members');
            $table->decimal('max_median', 4, 1);
            $table->decimal('max_p10', 4, 1);
            $table->decimal('max_p90', 4, 1);
            $table->decimal('min_median', 4, 1);
            $table->decimal('mean_median', 4, 1);
            $table->unsignedTinyInteger('precipitation_chance');
            $table->decimal('precipitation_median_mm', 5, 1);
            $table->decimal('precipitation_p90_mm', 5, 1);
            $table->decimal('sun_hours', 4, 1)->nullable();
            // Warmest max among the drier 3/4 of dry members: a good but realistic day.
            $table->decimal('good_day_max', 4, 1)->nullable();
            $table->unsignedTinyInteger('window_from');
            $table->unsignedTinyInteger('window_to');
            $table->decimal('window_min_celsius', 4, 1);
            $table->decimal('window_max_celsius', 4, 1);
            $table->timestamp('fetched_at');
            $table->timestamp('sent_to_telegram_at')->nullable();
            $table->timestamps();

            $table->unique('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_outlooks');
    }
};
