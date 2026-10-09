<?php

namespace Tests\Unit;

use App\Support\Search\FuzzySearch;
use PHPUnit\Framework\TestCase;

class FuzzySearchTest extends TestCase
{
    public function test_singular_plural_and_articulated_forms_share_a_stem(): void
    {
        foreach ([['fotografi', 'Fotografie', 'fotograful'], ['formații', 'Formație', 'FORMATIE'], ['torturi', 'tort'], ['limuzine', 'Limuzină']] as $forms) {
            $stems = array_map(fn ($form) => FuzzySearch::terms($form)[0][0], $forms);
            $this->assertCount(1, array_unique($stems), implode(', ', $forms));
        }
    }

    public function test_vowel_alternation_variants_and_stopwords(): void
    {
        $this->assertContains('salon', FuzzySearch::terms('saloane')[0]);
        $this->assertSame([['dj'], ['nunt']], FuzzySearch::terms('DJ pentru nuntă'));
        $this->assertSame([['dj']], FuzzySearch::terms('DJ-i'));
    }

    public function test_score_ignores_diacritics_and_rewards_word_starts(): void
    {
        $this->assertGreaterThan(0, FuzzySearch::score('Formație live nuntă', 'formatii nunti', requireAll: true));
        $this->assertSame(0, FuzzySearch::score('Formație live', 'formatii cluj', requireAll: true));
        $this->assertGreaterThan(
            FuzzySearch::score('Toate anunțurile', 'nunti'),
            FuzzySearch::score('Nuntă de vis', 'nunti')
        );
    }
}
