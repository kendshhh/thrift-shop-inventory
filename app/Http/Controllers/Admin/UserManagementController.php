<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RequiresConfirmedAction;
use App\Models\User;
use App\Support\DefaultAccountManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    use RequiresConfirmedAction;

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::in(['admin', 'customer'])],
            'state' => ['nullable', Rule::in(['active', 'suspended'])],
            'sort' => ['nullable', Rule::in(['latest', 'name_asc', 'name_desc', 'email_asc', 'email_desc'])],
        ]);

        $query = User::query()->with('roles');

        if (!empty($validated['search'])) {
            $search = (string) $validated['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($validated['role'])) {
            $query->role((string) $validated['role']);
        }

        if (!empty($validated['state'])) {
            $state = (string) $validated['state'];

            if ($state === 'active') {
                $query->where('is_active', true);
            }

            if ($state === 'suspended') {
                $query->where('is_active', false);
            }
        }

        match ($validated['sort'] ?? 'latest') {
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'email_asc' => $query->orderBy('email'),
            'email_desc' => $query->orderByDesc('email'),
            default => $query->latest(),
        };

        return view('admin.users.index', [
            'users' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['search', 'role', 'state', 'sort']),
        ]);
    }

    public function update(Request $request, User $user, DefaultAccountManager $defaultAccountManager): RedirectResponse
    {
        $this->requireConfirmedAction($request);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
            'suspended_at' => ['nullable', 'date'],
        ]);

        if ($defaultAccountManager->isDefaultAdmin($user) && ! (bool) $validated['is_active']) {
            return back()->withErrors([
                'app' => 'The default system admin account must remain active.',
            ]);
        }

        $user->is_active = (bool) $validated['is_active'];
        $user->suspended_at = $validated['is_active'] ? null : ($validated['suspended_at'] ?? now());
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User profile updated.');
    }
}
