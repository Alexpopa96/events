<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect([
            'Fotograf',
            'Videograf',
            'DJ',
            'Formație',
            'MC',
            'Wedding Planner',
            'Restaurant',
            'Salon evenimente',
            'Decor',
            'Florist',
            'Torturi',
            'Candy Bar',
            'Cabină Foto',
            'Cabină 360',
            'Limuzine',
            'Sonorizare',
            'Lumini',
            'Machiaj',
            'Coafură',
            'Invitații',
            'Cazare',
        ])->values()->each(function (string $name, int $index) {
            Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'position' => $index,
                    'is_active' => true,
                ]
            );
        });
    }
}
