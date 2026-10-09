<?php

namespace App\Http\Controllers\Subscriptions;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\SubscriptionPlan;
use App\Support\Seo\Seo;
use Inertia\Inertia;
use Inertia\Response;

class Index extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Subscriptions/Index', [
            'seo' => Seo::make(
                'Abonamente pentru furnizori de evenimente',
                'Listează-ți serviciile pe Invita și primește cereri de ofertă de la clienți din zona ta. Vezi planurile și prețurile pentru furnizori.',
                route('subscriptions.index'),
            )->toArray(),
            'plans' => SubscriptionPlan::where('is_active', true)
                ->orderBy('position')
                ->get()
                ->map(fn (SubscriptionPlan $plan) => $plan->toPublicArray()),
            'stats' => [
                'providers' => ProviderProfile::where('status', 'active')->count(),
            ],
        ]);
    }
}
