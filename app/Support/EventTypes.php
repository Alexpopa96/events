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

    /**
     * How each event type reads inside a sentence or a search phrase
     * ("Fotograf nuntă", "DJ pentru petrecere privată"). Types missing here
     * are too vague to get their own landing pages.
     */
    public const TERMS = [
        'nunta' => 'nuntă',
        'botez' => 'botez',
        'aniversare' => 'aniversare',
        'corporate' => 'evenimente corporate',
        'petrecere-privata' => 'petrecere privată',
        'concert' => 'concert',
    ];

    /**
     * Event types that get /<event>/<category> landing pages.
     *
     * @return list<string>
     */
    public static function landingValues(): array
    {
        return array_keys(self::TERMS);
    }

    public static function term(string $value): string
    {
        return self::TERMS[$value] ?? mb_strtolower(self::LABELS[$value] ?? $value);
    }

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
