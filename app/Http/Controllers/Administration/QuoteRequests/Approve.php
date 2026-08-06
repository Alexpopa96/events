<?php

namespace App\Http\Controllers\Administration\QuoteRequests;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use App\Notifications\QuoteRequestApproved;
use Illuminate\Http\RedirectResponse;

class Approve extends Controller
{
    public function __invoke(QuoteRequest $quoteRequest): RedirectResponse
    {
        $quoteRequest->update([
            'status' => 'open',
            'approved_at' => now(),
        ]);

        $quoteRequest->user->notify(new QuoteRequestApproved($quoteRequest));

        return redirect()
            ->back()
            ->with('success', ['message' => 'Cererea a fost aprobată.']);
    }
}
