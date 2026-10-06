<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Support\EmailTwoFactor;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * Changing the email address needs a code that was emailed to the CURRENT address
     * for that exact new address (see EmailChangeController).
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        $changingEmail = strcasecmp(trim((string) ($input['email'] ?? '')), (string) $user->email) !== 0;

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email_change_code' => [Rule::requiredIf($changingEmail), 'nullable', 'digits:6'],
        ], [
            'email_change_code.required' => 'Introdu codul primit pe adresa curentă de email.',
            'email_change_code.digits' => 'Codul are 6 cifre.',
        ])->validateWithBag('updateProfileInformation');

        $email = $user->email;

        if ($changingEmail) {
            $email = Str::lower(trim($input['email']));

            if (! EmailTwoFactor::check(EmailTwoFactor::SCOPE_EMAIL_CHANGE, $user, $input['email_change_code'], ['email' => $email])) {
                throw ValidationException::withMessages([
                    'email_change_code' => 'Codul este invalid sau a expirat.',
                ])->errorBag('updateProfileInformation');
            }
        }

        $user->forceFill(['name' => $input['name'], 'email' => $email])->save();
    }
}
