<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\CreateNewClientUser;
use App\Http\Controllers\Controller;
use App\Services\EmailVerificationCodeService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientRegistrationController extends Controller
{
    public function __construct(
        private readonly StatefulGuard $guard,
        private readonly EmailVerificationCodeService $verificationCodes,
    )
    {
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Auth/RegisterClient', [
            'prefill' => [
                'email' => (string) $request->query('email', ''),
                'phone' => (string) $request->query('phone', ''),
            ],
        ]);
    }

    public function store(Request $request, CreateNewClientUser $creator): RedirectResponse
    {
        event(new Registered($user = $creator->create($request->all())));

        $this->guard->login($user);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $this->verificationCodes->send($user);

        return redirect()->route('verification.notice');
    }
}
