<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthFlowController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
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
                'description' => 'Browse items and manage your reservations.',
                'icon' => 'bi-bag-heart',
                'href' => route('login', ['role' => 'customer']),
            ],
            [
                'role' => 'admin',
                'title' => 'Admin',
                'description' => 'Access inventory, reservations, and store controls.',
                'icon' => 'bi-shield-lock',
                'href' => route('login', ['role' => 'admin']),
            ],
        ];
    }
}
