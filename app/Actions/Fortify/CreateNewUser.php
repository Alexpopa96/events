<?php

namespace App\Actions\Fortify;

use App\Models\ProviderProfile;
use App\Models\User;
use App\Notifications\NewProviderPendingApproval;
use App\Notifications\ProviderRegistrationReceived;
use App\Services\AnafLookupService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'cui' => ['required', 'string', 'max:20', 'unique:provider_profiles,cui'],
            'company_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'county_id' => ['required', 'integer', 'exists:counties,id'],
            'locality_id' => ['required', 'integer', 'exists:localities,id'],
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        // Authoritative re-check: the client-side lookup is UX only, the CUI
        // must resolve against ANAF again here before an account is created.
        $company = app(AnafLookupService::class)->lookup($input['cui']);

        if (! $company) {
            throw ValidationException::withMessages([
                'cui' => 'Nu am putut valida CUI-ul la ANAF. Verifică-l și încearcă din nou.',
            ]);
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        $user->assignRole('furnizor');

        $profile = ProviderProfile::create([
            'user_id' => $user->id,
            'company_name' => $input['company_name'],
            'cui' => $company['cui'],
            'reg_com' => $company['reg_com'],
            'slug' => Str::slug($input['company_name']).'-'.Str::lower(Str::random(5)),
            'address' => $input['address'] ?? $company['address'],
            'county_id' => $input['county_id'],
            'locality_id' => $input['locality_id'],
            'status' => 'pending',
        ]);

        $user->notify(new ProviderRegistrationReceived($profile));
        User::role('admin')->get()->each->notify(new NewProviderPendingApproval($profile));

        return $user;
    }
}
