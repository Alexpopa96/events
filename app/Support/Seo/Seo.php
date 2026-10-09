<?php

namespace App\Support\Seo;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Builds the `seo` page prop that app.blade.php renders server-side as
 * <title>, meta description, canonical, Open Graph and JSON-LD, so crawlers
 * get them on the first response whether or not the SSR server is running.
 */
class Seo
{
    public const BRAND = 'Invita';

    private array $jsonLd = [];

    private ?string $image = null;

    private bool $noindex = false;

    private string $type = 'website';

    public function __construct(
        private string $title,
        private string $description,
        private ?string $canonical = null,
    ) {}

    public static function make(string $title, string $description, ?string $canonical = null): self
    {
        return new self($title, $description, $canonical);
    }

    /**
     * For browsable index pages: a filtered/sorted/searched view is a near-duplicate of
     * the clean URL, so it is kept out of the index; later pages canonicalise to themselves.
     */
    public function forIndexPage(Request $request): self
    {
        $page = (int) $request->query('page', 1);

        if ($page > 1 && $this->canonical) {
            $this->canonical .= "?page={$page}";
            $this->title .= " — pagina {$page}";
        }

        return $this->noindex(collect($request->query())->except('page')->filter(fn ($v) => filled($v))->isNotEmpty());
    }

    public function image(?string $url): self
    {
        $this->image = $url ? self::absolute($url) : null;

        return $this;
    }

    public function noindex(bool $noindex = true): self
    {
        $this->noindex = $noindex;

        return $this;
    }

    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function jsonLd(?array $schema): self
    {
        if ($schema) {
            $this->jsonLd[] = ['@context' => 'https://schema.org'] + $schema;
        }

        return $this;
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $crumbs
     */
    public function breadcrumbs(array $crumbs): self
    {
        return $this->jsonLd([
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($crumbs)->values()->map(fn (array $crumb, int $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ])->all(),
        ]);
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'full_title' => Str::contains($this->title, self::BRAND) ? $this->title : "{$this->title} | ".self::BRAND,
            'description' => Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($this->description))), 157),
            'canonical' => $this->canonical,
            'image' => $this->image,
            'type' => $this->type,
            'robots' => $this->noindex ? 'noindex, follow' : 'index, follow',
            'json_ld' => $this->jsonLd,
        ];
    }

    /**
     * schema.org AggregateRating, or null when there is nothing to rate yet
     * (Google rejects ratings with zero reviews).
     */
    public static function aggregateRating(?float $rating, int $count): ?array
    {
        if (! $rating || $count < 1) {
            return null;
        }

        return [
            '@type' => 'AggregateRating',
            'ratingValue' => round($rating, 1),
            'reviewCount' => $count,
            'bestRating' => 5,
            'worstRating' => 1,
        ];
    }

    public static function absolute(string $url): string
    {
        return Str::startsWith($url, ['http://', 'https://']) ? $url : url($url);
    }
}
