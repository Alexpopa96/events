<?php

namespace Database\Seeders\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

trait SeedsLeadClients
{
    /**
     * The client account behind a seeded quote request. Real requests are always
     * submitted by a logged-in client, so seeded ones need a matching account or
     * the provider can't message them in the app.
     */
    public function leadClient(array $lead): User
    {
        $phone = isset($lead['phone']) ? User::normalizePhone($lead['phone']) : null;

        $client = User::firstOrCreate(
            ['email' => $lead['email']],
            [
                'name' => $lead['name'],
                'password' => Hash::make('client2026'),
                // Phones are unique across users; skip it if another account already has this one.
                'phone' => $phone && ! User::where('phone', $phone)->exists() ? $phone : null,
                'status' => true,
                'email_verified_at' => now(),
            ]
        );

        $client->syncRoles(['client']);

        return $client;
    }
}
