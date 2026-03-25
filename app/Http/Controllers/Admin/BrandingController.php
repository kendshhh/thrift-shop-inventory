<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandingSetting;
use App\Support\Branding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BrandingController extends Controller
{
    public function edit(): View
    {
        return view('admin.branding.edit', [
            'settings' => BrandingSetting::query()->find(1),
            'branding' => Branding::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'brand_name' => ['required', 'string', 'max:80'],
            'brand_tagline' => ['nullable', 'string', 'max:180'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $settings = BrandingSetting::query()->firstOrNew(['id' => 1]);
        $removeLogo = $request->boolean('remove_logo');
        $hasNewLogo = $request->hasFile('logo');
        $disk = Storage::disk('public');

        if (($removeLogo || $hasNewLogo) && $settings->logo_path !== null && $settings->logo_path !== '' && $disk->exists($settings->logo_path)) {
            $disk->delete($settings->logo_path);
        }

        if ($hasNewLogo) {
            $settings->logo_path = $request->file('logo')->store('branding', 'public');
        } elseif ($removeLogo) {
            $settings->logo_path = null;
        }

        $settings->fill([
            'brand_name' => trim($validated['brand_name']),
            'brand_tagline' => $this->normalizeTagline($validated['brand_tagline'] ?? null),
            'primary_color' => strtoupper($validated['primary_color']),
            'secondary_color' => strtoupper($validated['secondary_color']),
            'updated_by' => $request->user()?->id,
        ]);

        $settings->save();

        Branding::flushCache();

        return redirect()
            ->route('admin.branding.edit')
            ->with('status', 'Branding settings updated successfully.');
    }

    private function normalizeTagline(?string $tagline): ?string
    {
        if ($tagline === null) {
            return null;
        }

        $tagline = trim($tagline);

        return $tagline !== '' ? $tagline : null;
    }
}