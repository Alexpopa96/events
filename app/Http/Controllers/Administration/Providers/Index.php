<?php

namespace App\Http\Controllers\Administration\Providers;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Index extends Controller
{
    public function __invoke(Request $request): Response
    {
        $status = $request->string('status', 'all')->toString();
        $search = $request->string('search')->toString();

        $providers = ProviderProfile::query()
            ->with(['user:id,name,email', 'county:id,name', 'locality:id,name'])
            ->withCount('listings')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('company_name', 'like', "%{$search}%")
                        ->orWhere('cui', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ProviderProfile $provider) => [
                'id' => $provider->id,
                'company_name' => $provider->company_name,
                'cui' => $provider->cui,
                'status' => $provider->status,
                'logo_url' => $provider->logoUrl(),
                'listings_count' => $provider->listings_count,
                'user' => $provider->user?->only(['name', 'email']),
                'county' => $provider->county?->only(['name']),
                'locality' => $provider->locality?->only(['name']),
                'created_at' => $provider->created_at->format('d.m.Y H:i'),
            ]);

        $counts = ProviderProfile::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return Inertia::render('Administration/Providers/Index', [
            'providers' => $providers,
            'filters' => ['status' => $status, 'search' => $search],
            'counts' => [
                'all' => $counts->sum(),
                'pending' => $counts->get('pending', 0),
                'active' => $counts->get('active', 0),
                'rejected' => $counts->get('rejected', 0),
                'suspended' => $counts->get('suspended', 0),
            ],
        ]);
    }
}
