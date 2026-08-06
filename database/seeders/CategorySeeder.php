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
        $descriptions = [
            'Fotograf' => 'Fotografi profesioniști pentru orice tip de eveniment.',
            'Videograf' => 'Videografi și filmări cinematice pentru evenimentul tău.',
            'DJ' => 'DJ și muzică live pentru petreceri de neuitat.',
            'Formație' => 'Formații și soliști pentru orice stil muzical.',
            'MC' => 'Prezentatori și maeștri de ceremonii pentru evenimentul tău.',
            'Wedding Planner' => 'Organizatori de nunți care se ocupă de tot.',
            'Restaurant' => 'Restaurante și meniuri pentru evenimente private.',
            'Salon evenimente' => 'Săli și locații pentru nunți, botezuri și petreceri.',
            'Decor' => 'Decorațiuni și amenajări florale pentru orice tematică.',
            'Florist' => 'Buchete și aranjamente florale pentru evenimente.',
            'Torturi' => 'Torturi personalizate pentru orice ocazie.',
            'Candy Bar' => 'Candy bar și deserturi pentru evenimentul tău.',
            'Cabină Foto' => 'Cabine foto pentru amintiri de neuitat.',
            'Cabină 360' => 'Cabine video 360° pentru momente virale.',
            'Limuzine' => 'Închirieri auto și limuzine pentru evenimente.',
            'Sonorizare' => 'Sonorizare profesională pentru orice tip de eveniment.',
            'Lumini' => 'Lumini arhitecturale și efecte speciale.',
            'Machiaj' => 'Machiaj profesional pentru ziua ta specială.',
            'Coafură' => 'Coafuri și styling pentru evenimente.',
            'Invitații' => 'Invitații personalizate și materiale tipărite.',
            'Cazare' => 'Cazare pentru invitați aproape de locația evenimentului.',
        ];

        collect(array_keys($descriptions))->values()->each(function (string $name, int $index) use ($descriptions) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'position' => $index,
                    'is_active' => true,
                    'description' => $descriptions[$name],
                ]
            );
        });
    }
}
