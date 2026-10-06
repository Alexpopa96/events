<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\Listing;
use App\Models\ProviderProfile;
use App\Models\QuoteRequest;
use App\Models\Review;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DashboardMetrics
{
    public const RANGES = [7, 30, 90];

    private CarbonImmutable $to;

    private CarbonImmutable $from;

    public function __construct(private int $range = 30)
    {
        $this->range = in_array($range, self::RANGES, true) ? $range : 30;
        $this->to = CarbonImmutable::today();
        $this->from = $this->to->subDays($this->range - 1);
    }

    public function range(): int
    {
        return $this->range;
    }

    /**
     * Daily counts of created rows over the current range, zero-filled.
     *
     * @param  class-string<Model>  $model
     * @return array<int, array{date: string, value: int}>
     */
    public function dailyCounts(string $model, ?callable $scope = null, string $column = 'created_at', ?CarbonImmutable $from = null, ?CarbonImmutable $to = null): array
    {
        $from ??= $this->from;
        $to ??= $this->to;

        $query = $model::query()->whereBetween($column, [$from->startOfDay(), $to->endOfDay()]);
        if ($scope) {
            $scope($query);
        }

        $counts = $query->pluck($column)
            ->countBy(fn ($date) => CarbonImmutable::parse($date)->toDateString());

        $series = [];
        for ($day = $from; $day <= $to; $day = $day->addDay()) {
            $key = $day->toDateString();
            $series[] = ['date' => $key, 'value' => (int) ($counts[$key] ?? 0)];
        }

        return $series;
    }

    /**
     * Total for the current range, plus the change against the previous range of equal length.
     *
     * @return array{value: int, previous: int, change: float|null, spark: array<int, int>}
     */
    public function trend(string $model, ?callable $scope = null, string $column = 'created_at'): array
    {
        $current = $this->dailyCounts($model, $scope, $column);
        $previous = $this->dailyCounts($model, $scope, $column, $this->from->subDays($this->range), $this->from->subDay());

        $value = array_sum(array_column($current, 'value'));
        $prev = array_sum(array_column($previous, 'value'));

        return [
            'value' => $value,
            'previous' => $prev,
            'change' => $prev > 0 ? round((($value - $prev) / $prev) * 100, 1) : null,
            'spark' => array_column($current, 'value'),
        ];
    }

    /**
     * Combined daily series for the growth chart.
     *
     * @param  array<string, array<int, array{date: string, value: int}>>  $series
     */
    public function growth(array $series): array
    {
        return [
            'dates' => array_column(reset($series), 'date'),
            'series' => collect($series)->map(fn ($points, $key) => [
                'key' => $key,
                'values' => array_column($points, 'value'),
            ])->values()->all(),
        ];
    }

    /**
     * @param  class-string<Model>  $model
     * @param  array<string, string>  $labels  status => label
     * @return array<int, array{key: string, label: string, value: int}>
     */
    public function statusBreakdown(string $model, array $labels): array
    {
        $counts = $model::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect($labels)
            ->map(fn ($label, $status) => ['key' => $status, 'label' => $label, 'value' => (int) ($counts[$status] ?? 0)])
            ->values()
            ->all();
    }

    /** @return array<int, array{label: string, value: int}> */
    public function topCategories(int $limit = 6): array
    {
        return Category::query()
            ->withCount(['quoteRequests as total' => fn ($query) => $query->where('created_at', '>=', $this->from->startOfDay())])
            ->get()
            ->filter(fn ($category) => $category->total > 0)
            ->sortByDesc('total')
            ->take($limit)
            ->map(fn ($category) => ['label' => $category->name, 'value' => (int) $category->total])
            ->values()
            ->all();
    }

    /** @return array{months: array<int, array{label: string, value: float}>, total: float, currency: string}|null */
    public function revenue(): ?array
    {
        $start = $this->to->startOfMonth()->subMonths(5);

        $invoices = Invoice::query()
            ->where('status', 'paid')
            ->where('paid_at', '>=', $start)
            ->get(['amount', 'currency', 'paid_at']);

        if ($invoices->isEmpty()) {
            return null;
        }

        $byMonth = $invoices->groupBy(fn ($invoice) => $invoice->paid_at->format('Y-m'));

        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $month = $start->addMonths($i);
            $months[] = [
                'label' => $month->format('Y-m'),
                'value' => round((float) ($byMonth[$month->format('Y-m')] ?? collect())->sum('amount'), 2),
            ];
        }

        return [
            'months' => $months,
            'total' => array_sum(array_column($months, 'value')),
            'currency' => $invoices->first()->currency ?? 'RON',
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    public function activity(int $limit = 12, bool $providers = true, bool $quoteRequests = true, bool $users = true): Collection
    {
        $items = collect();

        if ($users) {
            $items = $items->merge(
                User::latest()->take($limit)->get(['id', 'name', 'created_at'])->map(fn ($user) => [
                    'type' => 'user',
                    'title' => 'Utilizator nou',
                    'subject' => $user->name,
                    'href' => '/administration/users',
                    'at' => $user->created_at,
                ])
            );
        }

        if ($providers) {
            $items = $items->merge(
                ProviderProfile::latest()->take($limit)->get(['id', 'company_name', 'status', 'created_at'])->map(fn ($provider) => [
                    'type' => 'provider',
                    'title' => $provider->status === 'pending' ? 'Furnizor în așteptare' : 'Furnizor înregistrat',
                    'subject' => $provider->company_name,
                    'href' => "/administration/providers/{$provider->id}",
                    'at' => $provider->created_at,
                ])
            );
        }

        if ($quoteRequests) {
            $items = $items->merge(
                QuoteRequest::latest()->take($limit)->get(['id', 'title', 'event_type', 'status', 'created_at'])->map(fn ($request) => [
                    'type' => 'quote_request',
                    'title' => $request->status === 'pending_review' ? 'Cerere de ofertă de moderat' : 'Cerere de ofertă nouă',
                    'subject' => $request->title ?: ($request->event_type ?: 'Cerere de ofertă'),
                    'href' => "/administration/quote-requests/{$request->id}",
                    'at' => $request->created_at,
                ])
            );
        }

        $items = $items->merge(
            Listing::where('status', 'published')->latest('published_at')->take($limit)->get(['id', 'title', 'published_at', 'created_at'])->map(fn ($listing) => [
                'type' => 'listing',
                'title' => 'Anunț publicat',
                'subject' => $listing->title,
                'href' => null,
                'at' => $listing->published_at ?? $listing->created_at,
            ])
        );

        $items = $items->merge(
            Review::latest()->take($limit)->get(['id', 'rating', 'created_at'])->map(fn ($review) => [
                'type' => 'review',
                'title' => 'Recenzie nouă',
                'subject' => "{$review->rating} din 5 stele",
                'href' => null,
                'at' => $review->created_at,
            ])
        );

        return $items
            ->filter(fn ($item) => $item['at'])
            ->sortByDesc('at')
            ->take($limit)
            ->map(fn ($item) => [...$item, 'at' => $item['at']->toIso8601String()])
            ->values();
    }
}
