<?php

namespace App\Http\Controllers\Provider\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class Update extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $profile = $request->user()->providerProfile;

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'county_id' => ['nullable', 'integer', 'exists:counties,id'],
            'locality_id' => ['nullable', 'integer', 'exists:localities,id'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'tiktok' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'cover' => ['nullable', 'image', 'max:4096'],
        ]);

        $updates = [
            'company_name' => $data['company_name'],
            'description' => $data['description'] ?? null,
            'phone' => $data['phone'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'email' => $data['email'] ?? null,
            'website' => $data['website'] ?? null,
            'address' => $data['address'] ?? null,
            'county_id' => $data['county_id'] ?? null,
            'locality_id' => $data['locality_id'] ?? null,
            'social_links' => array_filter([
                'facebook' => $data['facebook'] ?? null,
                'instagram' => $data['instagram'] ?? null,
                'tiktok' => $data['tiktok'] ?? null,
            ]),
        ];

        if ($request->hasFile('logo')) {
            if ($profile->logo_path) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $updates['logo_path'] = $request->file('logo')->store('providers/logos', 'public');
        }

        if ($request->hasFile('cover')) {
            if ($profile->cover_path) {
                Storage::disk('public')->delete($profile->cover_path);
            }
            $updates['cover_path'] = $request->file('cover')->store('providers/covers', 'public');
        }

        $profile->update($updates);
        $profile->update(['profile_completion_score' => $profile->calculateProfileCompletionScore()]);

        return redirect()
            ->route('provider.profile.edit')
            ->with('success', ['message' => 'Profilul a fost actualizat.']);
    }
}
