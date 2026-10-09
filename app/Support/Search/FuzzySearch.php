<?php

namespace App\Support\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Forgiving free-text search for Romanian input: case/diacritic insensitive,
 * multi-word (every word must match somewhere) and tolerant to singular/plural
 * and articulated forms, so "fotografi", "fotografie" and "Fotograf" all match
 * each other, as do "formatii" / "Formație" or "saloane" / "Salon".
 *
 * Words are reduced to a crude stem (common inflection suffixes stripped) and
 * matched with LIKE %stem%. The DB collation (utf8mb4_unicode_ci) already makes
 * the comparison accent-insensitive, so ASCII stems match diacritic text.
 */
class FuzzySearch
{
    /** Inflection endings, longest first so "urilor" wins over "or". */
    private const SUFFIXES = [
        'urilor', 'urile', 'iile', 'elor', 'ilor', 'ului',
        'uri', 'ele', 'ile', 'lor', 'ul', 'le', 'ii', 'ie', 'ea', 'ei',
        'a', 'e', 'i', 'u',
    ];

    private const STOPWORDS = [
        'de', 'la', 'si', 'in', 'cu', 'un', 'o', 'pe', 'din', 'pt', 'pentru',
        'sau', 'al', 'ale', 'a', 'pentu', 'mai', 'care', 'the', 'for', 'and',
    ];

    private const MIN_STEM = 3;

    /** Lowercase, strip diacritics and punctuation. */
    public static function normalize(string $text): string
    {
        $ascii = Str::lower(Str::ascii($text));

        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9]+/', ' ', $ascii)));
    }

    /**
     * Every searchable word of the query mapped to its accepted variants.
     *
     * @return list<list<string>>
     */
    public static function terms(string $query): array
    {
        $words = array_values(array_filter(explode(' ', self::normalize($query))));
        $meaningful = array_values(array_filter(
            $words,
            fn ($word) => strlen($word) > 1 && ! in_array($word, self::STOPWORDS, true)
        ));

        return array_map(fn ($word) => self::variants($word), $meaningful ?: $words);
    }

    /**
     * Stems to highlight / match against, flattened (handy for the frontend).
     *
     * @return list<string>
     */
    public static function stems(string $query): array
    {
        return array_values(array_unique(array_merge(...(self::terms($query) ?: [[]]))));
    }

    /**
     * Constrain the query so every word (or, with $matchAll = false, at least one
     * word) matches at least one of the given columns. Columns may be
     * relation-qualified ("category.name") to search via whereHas.
     *
     * @param  list<string>  $columns
     */
    public static function apply(Builder $query, string $search, array $columns, bool $matchAll = true): Builder
    {
        $wordMatches = function (Builder $query, array $variants) use ($columns) {
            foreach ($columns as $column) {
                if (str_contains($column, '.')) {
                    [$relation, $field] = explode('.', $column, 2);
                    $query->orWhereHas($relation, fn (Builder $query) => self::anyLike($query, $field, $variants));
                } else {
                    $query->orWhere(fn (Builder $query) => self::anyLike($query, $column, $variants));
                }
            }
        };

        $terms = self::terms($search);

        if ($matchAll) {
            foreach ($terms as $variants) {
                $query->where(fn (Builder $query) => $wordMatches($query, $variants));
            }

            return $query;
        }

        return $query->where(function (Builder $query) use ($terms, $wordMatches) {
            foreach ($terms as $variants) {
                $query->orWhere(fn (Builder $query) => $wordMatches($query, $variants));
            }
        });
    }

    /**
     * Relevance of a text for the query: each word scores higher when it matches
     * at the start of the text / of a word. With $requireAll, a single missing
     * word makes the whole score 0.
     */
    public static function score(?string $text, string $search, bool $requireAll = false): int
    {
        $haystack = ' '.self::normalize((string) $text);
        $score = 0;

        foreach (self::terms($search) as $variants) {
            $best = 0;
            foreach ($variants as $variant) {
                $position = strpos($haystack, $variant);
                if ($position === false) {
                    continue;
                }
                $best = max($best, match (true) {
                    $position === 1 => 4,
                    $haystack[$position - 1] === ' ' => 3,
                    default => 1,
                });
            }
            if ($best === 0 && $requireAll) {
                return 0;
            }
            $score += $best;
        }

        return $score;
    }

    /** @return list<string> */
    private static function variants(string $word): array
    {
        $stem = self::stem($word);
        $variants = [$stem];

        // Vowel alternations between singular and plural (salon/saloane, seara/seri).
        foreach (['oa' => 'o', 'ea' => 'e'] as $from => $to) {
            if (str_contains($stem, $from)) {
                $variants[] = str_replace($from, $to, $stem);
            }
        }

        return array_values(array_unique($variants));
    }

    private static function stem(string $word): string
    {
        if (strlen($word) <= self::MIN_STEM || ctype_digit($word)) {
            return $word;
        }

        foreach (self::SUFFIXES as $suffix) {
            if (str_ends_with($word, $suffix) && strlen($word) - strlen($suffix) >= self::MIN_STEM) {
                return substr($word, 0, -strlen($suffix));
            }
        }

        return $word;
    }

    /** @param  list<string>  $variants */
    private static function anyLike(Builder $query, string $column, array $variants): Builder
    {
        return $query->where(function (Builder $query) use ($column, $variants) {
            foreach ($variants as $variant) {
                $query->orWhere($column, 'like', "%{$variant}%");
            }
        });
    }
}
