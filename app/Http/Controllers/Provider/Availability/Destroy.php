<?php

namespace App\Http\Controllers\Provider\Availability;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityBlock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Destroy extends Controller
{
    public function __invoke(Request $request, AvailabilityBlock $availabilityBlock): RedirectResponse
    {
        abort_unless($availabilityBlock->provider_profile_id === $request->user()->providerProfile->id, 404);

        $availabilityBlock->delete();

        return back()->with('success', ['message' => 'Ziua a fost deblocată.']);
    }
}
