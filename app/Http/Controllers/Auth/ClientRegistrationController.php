<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\CreateNewClientUser;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientRegistrationController extends Controller
{
    public function __construct(private readonly StatefulGuard $guard)
    {
    }

    public function create(): Response
    {
        return Inertia::render('Auth/RegisterClient');
    }

    public function store(Request $request, CreateNewClientUser $creator): RedirectResponse
    {
        event(new Registered($user = $creator->create($request->all())));

        $this->guard->login($user);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
