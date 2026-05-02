<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait RequiresConfirmedAction
{
    protected function requireConfirmedAction(Request $request, ?string $message = null): void
    {
        // Confirmation prompts were removed from the UI; keep this method as a
        // compatibility no-op for controllers that still call it.
    }
}
