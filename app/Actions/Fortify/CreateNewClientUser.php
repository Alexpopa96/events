<?php

namespace App\Actions\Fortify;

use App\Rules\RomanianPhone;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateNewClientUser
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered client account.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        if (filled($input['phone'] ?? null)) {
            $input['phone'] = User::normalizePhone($input['phone']) ?? $input['phone'];
        } else {
            $input['phone'] = null;
        }

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', new RomanianPhone, 'unique:users,phone'],
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'phone' => $input['phone'],
            'password' => Hash::make($input['password']),
        ]);

        $user->assignRole('client');

        return $user;
    }
}
