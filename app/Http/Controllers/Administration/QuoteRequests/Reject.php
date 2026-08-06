<?php

namespace App\Http\Controllers\Administration\QuoteRequests;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use App\Notifications\QuoteRequestRejected;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Reject extends Controller
{
    public function __invoke(Request $request, QuoteRequest $quoteRequest): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $quoteRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $data['reason'],
            'rejected_at' => now(),
        ]);

        $quoteRequest->user->notify(new QuoteRequestRejected($quoteRequest, $data['reason']));

        return redirect()
            ->back()
            ->with('success', ['message' => 'Cererea a fost respinsă.']);
    }
}
