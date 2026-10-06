<?php

namespace App\Http\Controllers\Administration\Users;

use App\Rules\RomanianPhone;
use App\Models\User;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;

class Store extends Controller
{
    public function __invoke()
    {
        // Unique checks compare against the stored E.164 form.
        if (filled(Request::get('phone')) && ($phone = User::normalizePhone((string) Request::get('phone')))) {
            Request::merge(['phone' => $phone]);
        }

        Request::validate([
            'name' => ['required'],
            'email' => ['required', 'max:50', Rule::unique('users'),'email:rfc,dns'],
            'role_id' => ['required'],
            'phone' => ['nullable', 'string', new RomanianPhone, Rule::unique('users', 'phone')],
            'obs' => ['nullable'],
        ], [
            'required' => 'Campul este obligatoriu',
            'unique' => 'Valoarea este deja folosită.'
        ]);

        $user =  User::create([
            'name' => Request::get('name'),
            'email' => Request::get('email'),
            'phone'=> Request::get('phone'),
            'password' => Hash::make('12345'),
            'obs'=> Request::get('obs'),
        ]);

        if (request()->get('role_id')) {
            $user->syncRoles(Role::find(request()->get('role_id')));
        }


        return Redirect::to('/administration/users')->with(['success'=> ['message'=> 'Utilizatorul a fost creat cu succes!']]);
    }
}
