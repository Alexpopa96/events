<?php

namespace App\Http\Controllers\Provider\Availability;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityBlock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Provider marks one or more days as busy by hand (a private event, time off, ...).
 */
class Store extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $profile = $request->user()->providerProfile;

        $data = $request->validate([
            'dates' => ['required', 'array', 'min:1', 'max:60'],
            'dates.*' => ['date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:150'],
        ]);

        foreach ($data['dates'] as $date) {
            AvailabilityBlock::firstOrCreate(
                ['provider_profile_id' => $profile->id, 'date' => $date],
                ['source' => AvailabilityBlock::MANUAL, 'note' => filled($data['note'] ?? null) ? trim($data['note']) : null]
            );
        }

        return back()->with('success', ['message' => count($data['dates']) > 1 ? 'Zilele au fost blocate.' : 'Ziua a fost blocată.']);
    }
}
