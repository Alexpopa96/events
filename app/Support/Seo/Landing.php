<?php

namespace App\Support\Seo;

use App\Models\Category;
use App\Models\County;
use App\Models\Locality;
use App\Support\EventTypes;
use Symfony\Component\HttpFoundation\Response;

/**
 * One browsable category page: a category, optionally narrowed to an event type
 * (/nunta/fotograf), a county (/categorii/fotograf/cluj) or a locality — or both
 * (/nunta/fotograf/cluj/cluj-napoca).
 */
final class Landing
{
    public function __construct(
        public readonly Category $category,
        public readonly ?County $county = null,
        public readonly ?Locality $locality = null,
        public readonly ?string $eventType = null,
    ) {}

    /**
     * Build a landing from route parameters, 404ing on an inactive category or unknown place.
     * Locality slugs repeat across counties (e.g. "valea-mare"), so they resolve within the county.
     */
    public static function resolve(Category $category, ?County $county = null, ?string $localitySlug = null, ?string $eventType = null): self
    {
        abort_unless($category->is_active, Response::HTTP_NOT_FOUND);

        $locality = $localitySlug !== null
            ? Locality::where('county_id', $county->id)->where('slug', $localitySlug)->firstOrFail()
            : null;

        return new self($category, $county, $locality, $eventType);
    }

    public function url(): string
    {
        $params = array_values(array_filter([
            $this->eventType,
            $this->category->slug,
            $this->county?->slug,
            $this->locality?->slug,
        ]));

        $level = $this->locality ? 'locality' : ($this->county ? 'county' : 'show');

        return route(($this->eventType ? 'events.categories.' : 'categories.').$level, $params);
    }

    public function withCategory(Category $category): self
    {
        return new self($category, $this->county, $this->locality, $this->eventType);
    }

    public function withPlace(?County $county, ?Locality $locality = null): self
    {
        return new self($this->category, $county, $locality, $this->eventType);
    }

    public function withEventType(?string $eventType): self
    {
        return new self($this->category, $this->county, $this->locality, $eventType);
    }

    public function hasPlace(): bool
    {
        return $this->county !== null;
    }

    public function placeName(): ?string
    {
        return $this->locality?->name ?? $this->county?->displayName();
    }

    /**
     * "Cluj-Napoca, județul Cluj" / "județul Cluj".
     */
    public function placeLabel(): ?string
    {
        if (! $this->county) {
            return null;
        }

        return $this->locality ? "{$this->locality->name}, {$this->county->regionLabel()}" : $this->county->regionLabel();
    }

    public function eventTerm(): ?string
    {
        return $this->eventType ? EventTypes::term($this->eventType) : null;
    }

    /**
     * What people type into Google: "Fotograf" / "Fotograf nuntă".
     */
    public function subject(): string
    {
        return trim("{$this->category->name} {$this->eventTerm()}");
    }

    /**
     * "Fotograf pentru nuntă în Cluj-Napoca".
     */
    public function heading(): string
    {
        return $this->category->name
            .($this->eventType ? " pentru {$this->eventTerm()}" : '')
            .($this->hasPlace() ? " în {$this->placeName()}" : '');
    }

    /**
     * @return array<int, array{name: string, url: ?string}>
     */
    public function breadcrumbs(): array
    {
        $crumbs = [
            ['name' => 'Acasă', 'url' => route('home')],
            ['name' => 'Categorii', 'url' => route('categories.index')],
            ['name' => $this->category->name, 'url' => (new self($this->category))->url()],
        ];

        if ($this->eventType) {
            $crumbs[] = ['name' => EventTypes::LABELS[$this->eventType], 'url' => (new self($this->category, eventType: $this->eventType))->url()];
        }

        if ($this->county) {
            $crumbs[] = ['name' => $this->county->displayName(), 'url' => $this->withPlace($this->county)->url()];
        }

        if ($this->locality) {
            $crumbs[] = ['name' => $this->locality->name, 'url' => $this->url()];
        }

        return $crumbs;
    }
}
