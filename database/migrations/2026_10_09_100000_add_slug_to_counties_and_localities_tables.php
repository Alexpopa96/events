<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('counties', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        Schema::table('localities', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        DB::table('counties')->orderBy('id')->each(
            fn ($county) => DB::table('counties')->where('id', $county->id)->update(['slug' => Str::slug($county->name)])
        );

        DB::table('localities')->orderBy('id')->chunkById(1000, function ($localities) {
            foreach ($localities as $locality) {
                DB::table('localities')->where('id', $locality->id)->update(['slug' => Str::slug($locality->name)]);
            }
        });

        Schema::table('counties', function (Blueprint $table) {
            $table->unique('slug');
        });

        Schema::table('localities', function (Blueprint $table) {
            $table->index(['county_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('localities', function (Blueprint $table) {
            $table->dropIndex(['county_id', 'slug']);
            $table->dropColumn('slug');
        });

        Schema::table('counties', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
