<?php

namespace App\Http\Middleware;

use App\Models\ProviderProfile;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = auth()->user();

        return [
            ...parent::share($request),
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'auth' => [
                'user' => $user,
                'permissions' => $user ? $user->permissionList() : [],
                'can' => [
                    'viewDashboard' => Auth::user() ? Auth::user()->can('view dashboard') : false,
                    'viewAdministration' => Auth::user() ? Auth::user()->can('view administration') : false,
                    'viewUsers' => Auth::user() ? Auth::user()->can('view users') : false,
                    'viewRoles' => Auth::user() ? Auth::user()->can('view roles') : false,
                    'viewPermissions' => Auth::user() ? Auth::user()->can('view permissions') : false,
                    'moderateProviders' => Auth::user() ? Auth::user()->can('moderate providers') : false,
                    'moderateQuoteRequests' => Auth::user() ? Auth::user()->can('moderate quote requests') : false,
                    'submitQuoteRequest' => Auth::user() ? Auth::user()->can('submit quote request') : false,
                    'manageOwnFavorites' => Auth::user() ? Auth::user()->can('manage own favorites') : false,
                ],
            ],
            'toast' => function () {
                return [
                    'error' => Session::get('error'),
                    'success' => Session::get('success'),
                ];
            },
            'impersonate' => Session::get('impersonate'),
            'role_id' => Auth::user() ? Auth::user()->roles()->first()->id : null,
            'adminBadges' => function () use ($user) {
                if (! $user) {
                    return null;
                }

                $badges = [];

                if ($user->can('moderate providers')) {
                    $badges['pendingProviders'] = ProviderProfile::where('status', 'pending')->count();
                }

                if ($user->can('moderate quote requests')) {
                    $badges['pendingQuoteRequests'] = QuoteRequest::where('status', 'pending_review')->count();
                }

                return $badges ?: null;
            },
        ];
    }
}
