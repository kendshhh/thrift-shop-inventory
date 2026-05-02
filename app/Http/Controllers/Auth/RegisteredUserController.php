<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->filled('role')) {
            return redirect()->route('register');
        }

        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email', ''))),
        ]);

        $request->validate([
            'name' => ['required', 'string', 'max:255', "regex:/^[\p{L}\p{M}][\p{L}\p{M}\s'.-]*$/u"],
            'email' => ['required', 'string', 'email:rfc', 'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'legal_consent' => ['accepted'],
        ], [
            'name.regex' => 'Full name must contain letters only. Spaces, apostrophes, periods, and hyphens are allowed.',
            'email.email' => 'Please enter a valid email address.',
            'email.regex' => 'Please enter a valid email address with a domain and top-level domain, such as example@email.com.',
            'legal_consent.accepted' => 'You must agree to the Privacy Policy and Terms & Conditions before creating an account.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'privacy_notice_accepted_at' => now(),
            'terms_accepted_at' => now(),
        ]);

        $user->assignRole(Role::findOrCreate('customer'));

        event(new Registered($user));

        Auth::login($user);

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
