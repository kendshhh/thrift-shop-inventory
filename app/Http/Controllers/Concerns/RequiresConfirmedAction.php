<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait RequiresConfirmedAction
{
    protected function requireConfirmedAction(Request $request, ?string $message = null): void
    {
        $confirmation = Str::lower(trim((string) $request->input('confirmation_text', '')));

        if ($confirmation === 'confirm') {
            return;
        }

        throw ValidationException::withMessages([
            'app' => $message ?? 'Type "confirm" to continue with this action.',
        ]);
    }
}
