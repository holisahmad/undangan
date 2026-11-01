<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Analytics\TrackEvent;
use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

final class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, TrackEvent $track): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        // Aggregate only: the funnel counts registrations, it does not record
        // which account was created (docs/PRIVACY_AND_RETENTION.md).
        $track->handle(AnalyticsEvent::REGISTER_COMPLETED);

        Auth::login($user);
        $request->session()->regenerate();

        // The customer area requires a verified address, so a fresh account goes
        // straight to the resend screen instead of the dashboard.
        return redirect()->route('verification.notice');
    }
}
