<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RequiresConfirmedAction;
use App\Support\DefaultAccountManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    use RequiresConfirmedAction;

    /**
     * Update the user's password.
     */
    public function update(Request $request, DefaultAccountManager $defaultAccountManager): RedirectResponse
    {
        $this->requireConfirmedAction($request);

        if ($defaultAccountManager->isDefaultAdmin($request->user())) {
            return back()->withErrors([
                'app' => 'The default system admin password is fixed and cannot be changed here.',
            ]);
        }

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }
}
