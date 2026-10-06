<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RomanianPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || User::normalizePhone($value) === null) {
            $fail('Introdu un număr de telefon românesc valid (ex. 0722 123 456).');
        }
    }
}
