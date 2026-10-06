<?php

namespace App\Http\Controllers\Provider\Availability;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityBlock;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Index extends Controller
{
    public function __invoke(Request $request): Response
    {
        $profile = $request->user()->providerProfile;

        $blocks = $profile->availabilityBlocks()
            ->where('date', '>=', now()->subDays(7)->toDateString())
            ->with('offer.quoteRequest:id,title')
            ->orderBy('date')
            ->get()
            ->map(fn (AvailabilityBlock $block) => [
                'id' => $block->id,
                'date' => $block->date->toDateString(),
                'source' => $block->source,
                'note' => $block->note,
                'lead_title' => $block->offer?->quoteRequest?->title,
            ]);

        return Inertia::render('Provider/Availability/Index', [
            'blocks' => $blocks,
        ]);
    }
}
