<?php

namespace App\Http\Middleware;

use App\Models\Offer;
use App\Models\ProviderProfile;
use App\Models\QuoteRequest;
use App\Models\Review;
use App\Support\Inbox;
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
                'isProvider' => $user?->isProvider() ?? false,
                'permissions' => $user ? $user->permissionList() : [],
                'can' => [
                    'viewDashboard' => Auth::user() ? Auth::user()->can('view dashboard') : false,
                    'viewAdministration' => Auth::user() ? Auth::user()->can('view administration') : false,
                    'viewUsers' => Auth::user() ? Auth::user()->can('view users') : false,
                    'viewRoles' => Auth::user() ? Auth::user()->can('view roles') : false,
                    'viewPermissions' => Auth::user() ? Auth::user()->can('view permissions') : false,
                    'moderateProviders' => Auth::user() ? Auth::user()->can('moderate providers') : false,
                    'moderateQuoteRequests' => Auth::user() ? Auth::user()->can('moderate quote requests') : false,
                    'moderateReviews' => Auth::user() ? Auth::user()->can('moderate reviews') : false,
                    'submitQuoteRequest' => Auth::user() ? Auth::user()->can('submit quote request') : false,
                    'manageOwnFavorites' => Auth::user() ? Auth::user()->can('manage own favorites') : false,
                    'saveSearch' => Auth::user() ? Auth::user()->can('save search') : false,
                ],
            ],
            'vapidPublicKey' => config('webpush.vapid.public_key'),
            'toast' => function () {
                return [
                    'error' => Session::get('error'),
                    'success' => Session::get('success'),
                ];
            },
            'impersonate' => Session::get('impersonate'),
            'role_id' => Auth::user() ? Auth::user()->roles()->first()?->id : null,
            'emailTwoFactor' => fn () => $user ? [
                'enabled' => $user->hasEmailTwoFactor(),
                'email' => $user->email,
            ] : null,
            'unreadMessages' => fn () => $user ? Inbox::unreadCount($user) : 0,
            'unreadNotifications' => fn () => $user ? $user->unreadNotifications()->count() : 0,
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

                if ($user->can('moderate reviews')) {
                    $badges['pendingReviews'] = Review::where('status', 'pending')->count();
                }

                return $badges ?: null;
            },
            'providerNav' => function () use ($user) {
                $profile = $user?->providerProfile;

                if (! $profile || $profile->status !== 'active') {
                    return null;
                }

                $categoryIds = $profile->listings()->distinct()->pluck('category_id');
                $contactedIds = $profile->events()
                    ->where('type', 'quote_request_view')
                    ->pluck('quote_request_id');

                $subscription = $profile->currentSubscription()->with('plan')->first();

                return [
                    'company_name' => $profile->company_name,
                    'slug' => $profile->slug,
                    'logo_url' => $profile->logoUrl(),
                    'new_leads' => QuoteRequest::whereIn('category_id', $categoryIds)
                        ->where('status', 'open')
                        ->whereNotIn('id', $contactedIds)
                        ->count(),
                    'unanswered_reviews' => $profile->reviews()->approved()->whereNull('provider_reply')->count(),
                    'awaiting_offers' => $profile->offers()->whereIn('status', Offer::OPEN_STATUSES)->count(),
                    'plan_name' => $subscription?->plan?->name,
                    'plan_status' => $subscription?->status,
                ];
            },
        ];
    }
}
