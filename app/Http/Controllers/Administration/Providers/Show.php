<?php

namespace App\Http\Controllers\Administration\Providers;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use Inertia\Inertia;
use Inertia\Response;

class Show extends Controller
{
    public function __invoke(ProviderProfile $provider): Response
    {
        $provider->load([
            'user:id,name,email,created_at',
            'county:id,name',
            'locality:id,name',
            'currentSubscription.plan',
        ]);

        $listings = $provider->listings()
            ->latest()
            ->take(8)
            ->get(['id', 'title', 'status', 'price_from', 'price_to', 'currency', 'views_count', 'published_at', 'created_at']);

        $listingCounts = $provider->listings()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $reviews = $provider->reviews()
            ->where('status', 'approved')
            ->latest()
            ->take(5)
            ->get(['id', 'rating', 'comment', 'user_id', 'created_at'])
            ->load('user:id,name');

        $invoices = $provider->invoices()
            ->latest('issued_at')
            ->take(5)
            ->get(['id', 'number', 'amount', 'currency', 'status', 'issued_at', 'pdf_url']);

        return Inertia::render('Administration/Providers/Show', [
            'provider' => [
                'id' => $provider->id,
                'company_name' => $provider->company_name,
                'cui' => $provider->cui,
                'reg_com' => $provider->reg_com,
                'slug' => $provider->slug,
                'description' => $provider->description,
                'phone' => $provider->phone,
                'whatsapp' => $provider->whatsapp,
                'email' => $provider->email,
                'website' => $provider->website,
                'address' => $provider->address,
                'county' => $provider->county?->only(['id', 'name']),
                'locality' => $provider->locality?->only(['id', 'name']),
                'logo_url' => $provider->logoUrl(),
                'cover_url' => $provider->coverUrl(),
                'social_links' => $provider->social_links,
                'profile_completion_score' => $provider->profile_completion_score,
                'status' => $provider->status,
                'created_at' => $provider->created_at->format('d.m.Y H:i'),
                'approved_at' => $provider->approved_at?->format('d.m.Y H:i'),
                'rejected_at' => $provider->rejected_at?->format('d.m.Y H:i'),
                'rejection_reason' => $provider->rejection_reason,
                'suspended_at' => $provider->suspended_at?->format('d.m.Y H:i'),
                'suspension_reason' => $provider->suspension_reason,
                'average_rating' => $provider->averageRating(),
                'reviews_count' => $provider->reviews()->where('status', 'approved')->count(),
                'user' => $provider->user?->only(['id', 'name', 'email']),
                'subscription' => $provider->currentSubscription ? [
                    'plan_name' => $provider->currentSubscription->plan?->name,
                    'status' => $provider->currentSubscription->status,
                    'starts_at' => $provider->currentSubscription->starts_at?->format('d.m.Y'),
                    'ends_at' => $provider->currentSubscription->ends_at?->format('d.m.Y'),
                ] : null,
            ],
            'listings' => $listings,
            'listingCounts' => $listingCounts,
            'reviews' => $reviews,
            'invoices' => $invoices,
        ]);
    }
}
