<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RequiresConfirmedAction;
use App\Http\Requests\ProfileUpdateRequest;
use App\Support\DefaultAccountManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use RequiresConfirmedAction;

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, DefaultAccountManager $defaultAccountManager): RedirectResponse
    {
        $this->requireConfirmedAction($request);

        $email = strtolower(trim((string) $request->input('email', '')));

        if (
            $defaultAccountManager->isDefaultAdmin($request->user())
            && $email !== $defaultAccountManager->defaultAdminEmail()
        ) {
            return Redirect::route('profile.edit')->withErrors([
                'app' => 'The default system admin email address is fixed and cannot be changed.',
            ]);
        }

        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request, DefaultAccountManager $defaultAccountManager): RedirectResponse
    {
        $this->requireConfirmedAction($request);

        if ($defaultAccountManager->isDefaultAdmin($request->user())) {
            return Redirect::route('profile.edit')->withErrors([
                'app' => 'The default system admin account cannot be deleted.',
            ]);
        }

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
