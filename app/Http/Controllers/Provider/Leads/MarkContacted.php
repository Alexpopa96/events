<?php

namespace App\Http\Controllers\Provider\Leads;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MarkContacted extends Controller
{
    public function __invoke(Request $request, QuoteRequest $lead): RedirectResponse
    {
        $profile = $request->user()->providerProfile;

        $profile->events()->firstOrCreate([
            'quote_request_id' => $lead->id,
            'type' => 'quote_request_view',
        ]);

        return back()->with('success', ['message' => 'Marcat ca și contactat.']);
    }
}
