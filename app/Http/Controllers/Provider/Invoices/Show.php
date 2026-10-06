<?php

namespace App\Http\Controllers\Provider\Invoices;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A print-ready invoice page (Ctrl/Cmd+P -> "Save as PDF" downloads it). If a real
 * PDF already exists (e.g. once a payment processor is wired in), that's used instead.
 */
class Show extends Controller
{
    public function __invoke(Request $request, Invoice $invoice): View|RedirectResponse
    {
        abort_unless($invoice->provider_profile_id === $request->user()->providerProfile?->id, 403);

        if ($invoice->pdf_url) {
            return redirect()->away($invoice->pdf_url);
        }

        $invoice->load(['providerProfile', 'subscription.plan']);

        return view('invoices.show', ['invoice' => $invoice]);
    }
}
