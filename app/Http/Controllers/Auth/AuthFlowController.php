<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\DefaultAccountManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthFlowController extends Controller
{
    public function show(Request $request, DefaultAccountManager $defaultAccountManager): View|RedirectResponse
    {
        $defaultAccountManager->ensureDefaultAccounts();

        $intent = $this->normalizeIntent($request->query('intent'));

        if ($intent === 'register') {
            return redirect()->route('register');
        }

        return view('auth.role-selection', [
            'intent' => $intent,
            'cards' => $this->cardsFor($intent),
        ]);
    }

    private function normalizeIntent(?string $intent): string
    {
        return in_array($intent, ['login', 'register'], true) ? $intent : 'login';
    }

    /**
     * @return array<int, array<string, string|bool>>
     */
    private function cardsFor(string $intent): array
    {
        return [
            [
                'role' => 'customer',
                'title' => 'Customer',
                'description' => 'Browse finds and manage reservations.',
                'icon' => 'bi-bag-heart',
                'href' => route('login', ['role' => 'customer']),
            ],
            [
                'role' => 'admin',
                'title' => 'Admin',
                'description' => 'Manage inventory, reservations, and users.',
                'icon' => 'bi-shield-lock',
                'href' => route('login', ['role' => 'admin']),
            ],
        ];
    }
}
