<?php

namespace App\Support;

class EventTypes
{
    public const LABELS = [
        'nunta' => 'Nuntă',
        'botez' => 'Botez',
        'aniversare' => 'Aniversare',
        'corporate' => 'Corporate',
        'petrecere-privata' => 'Petrecere privată',
        'concert' => 'Concert / Festival',
        'altul' => 'Altele',
    ];

    public static function values(): array
    {
        return array_keys(self::LABELS);
    }

    public static function options(): array
    {
        return collect(self::LABELS)
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }
}
