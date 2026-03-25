<?php

namespace App\Support;

use App\Models\BrandingSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class Branding
{
    private const CACHE_KEY = 'branding.settings.current';

    private const CACHE_TTL_SECONDS = 900;

    private const DEFAULT_PRIMARY_COLOR = '#0EA5E9';

    private const DEFAULT_SECONDARY_COLOR = '#2563EB';

    public static function current(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, static function (): array {
            return self::loadCurrent();
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function loadCurrent(): array
    {
        $defaults = self::defaults();

        try {
            if (! Schema::hasTable('branding_settings')) {
                return self::normalize($defaults);
            }

            $settings = BrandingSetting::query()->find(1);

            if (! $settings) {
                return self::normalize($defaults);
            }

            return self::normalize([
                'brand_name' => $settings->brand_name,
                'brand_tagline' => $settings->brand_tagline,
                'primary_color' => $settings->primary_color,
                'secondary_color' => $settings->secondary_color,
                'logo_path' => $settings->logo_path,
            ]);
        } catch (Throwable) {
            return self::normalize($defaults);
        }
    }

    private static function defaults(): array
    {
        return [
            'brand_name' => (string) config('app.name', 'Thrift Shop'),
            'brand_tagline' => null,
            'primary_color' => self::DEFAULT_PRIMARY_COLOR,
            'secondary_color' => self::DEFAULT_SECONDARY_COLOR,
            'logo_path' => null,
        ];
    }

    private static function normalize(array $values): array
    {
        $defaults = self::defaults();

        $brandName = trim((string) ($values['brand_name'] ?? ''));

        if ($brandName === '') {
            $brandName = $defaults['brand_name'];
        }

        $brandTagline = isset($values['brand_tagline']) ? trim((string) $values['brand_tagline']) : '';
        $brandTagline = $brandTagline !== '' ? $brandTagline : null;

        $logoPath = trim((string) ($values['logo_path'] ?? ''));
        $logoPath = $logoPath !== '' ? $logoPath : null;

        return [
            'brand_name' => $brandName,
            'brand_tagline' => $brandTagline,
            'primary_color' => self::normalizeHexColor($values['primary_color'] ?? null, $defaults['primary_color']),
            'secondary_color' => self::normalizeHexColor($values['secondary_color'] ?? null, $defaults['secondary_color']),
            'logo_path' => $logoPath,
            'logo_url' => self::logoUrl($logoPath),
        ];
    }

    private static function normalizeHexColor(mixed $value, string $fallback): string
    {
        $candidate = strtoupper(trim((string) $value));
        $fallbackColor = strtoupper(trim($fallback));

        if (! preg_match('/^#[0-9A-F]{6}$/', $fallbackColor)) {
            $fallbackColor = self::DEFAULT_PRIMARY_COLOR;
        }

        if (! preg_match('/^#[0-9A-F]{6}$/', $candidate)) {
            return $fallbackColor;
        }

        return $candidate;
    }

    private static function logoUrl(?string $logoPath): ?string
    {
        if ($logoPath === null || $logoPath === '') {
            return null;
        }

        if (Str::startsWith($logoPath, ['http://', 'https://', '/'])) {
            return $logoPath;
        }

        try {
            return Storage::disk('public')->url($logoPath);
        } catch (Throwable) {
            return null;
        }
    }
}