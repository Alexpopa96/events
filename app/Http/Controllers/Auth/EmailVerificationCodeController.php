<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationCodeController extends Controller
{
    public function __construct(private readonly EmailVerificationCodeService $codes)
    {
    }

    public function show(Request $request): Response|RedirectResponse
    {
        if ($request->user()->email_verified_at !== null) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return Inertia::render('Auth/VerifyEmailCode', [
            'email' => $request->user()->email,
            'validForMinutes' => EmailVerificationCodeService::VALID_FOR_MINUTES,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Introdu codul primit pe email.',
            'code.digits' => 'Codul are 6 cifre.',
        ]);

        if ($request->user()->email_verified_at === null && ! $this->codes->verify($request->user(), $data['code'])) {
            throw ValidationException::withMessages([
                'code' => 'Codul este incorect sau a expirat. Solicită unul nou.',
            ]);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->email_verified_at !== null) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $this->codes->send($request->user());

        return back()->with('status', 'verification-code-sent');
    }
}
