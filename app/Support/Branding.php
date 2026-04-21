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

            $settings = BrandingSetting::query()->find(1) ?? BrandingSetting::query()->first();

            if (! $settings) {
                return self::normalize($defaults);
            }

            return self::normalize([
                'brand_name' => $settings->brand_name,
                'brand_tagline' => $settings->brand_tagline,
                'primary_color' => $settings->primary_color,
                'secondary_color' => $settings->secondary_color,
                'logo_path' => $settings->logo_path,
                'payment_details' => $settings->payment_details,
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
            'payment_details' => [],
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
            'primary_color' => $primaryColor = self::normalizeHexColor($values['primary_color'] ?? null, $defaults['primary_color']),
            'primary_rgb' => self::hexToRgbString($primaryColor),
            'secondary_color' => $secondaryColor = self::normalizeHexColor($values['secondary_color'] ?? null, $defaults['secondary_color']),
            'secondary_rgb' => self::hexToRgbString($secondaryColor),
            'logo_path' => $logoPath,
            'logo_url' => self::publicDiskUrl($logoPath),
            'payment_details' => self::normalizePaymentDetails($values['payment_details'] ?? []),
        ];
    }

    public static function normalizePaymentDetails(mixed $entries): array
    {
        if (! is_array($entries)) {
            return [];
        }

        $normalized = [];

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $id = trim((string) ($entry['id'] ?? ''));
            $name = trim((string) ($entry['name'] ?? ''));
            $bankName = trim((string) ($entry['bank_name'] ?? ''));
            $bankNumber = trim((string) ($entry['bank_number'] ?? ''));

            $paths = array_values(array_unique(array_filter(
                array_map(static fn ($path) => trim((string) $path), (array) ($entry['qr_image_paths'] ?? [])),
                static fn ($path) => $path !== ''
            )));

            if ($name === '' && $bankName === '' && $bankNumber === '' && $paths === []) {
                continue;
            }

            $normalized[] = [
                'id' => $id !== '' ? $id : null,
                'name' => $name,
                'bank_name' => $bankName,
                'bank_number' => $bankNumber,
                'qr_image_paths' => $paths,
                'qr_images' => array_values(array_filter(array_map(
                    static function (string $path): ?array {
                        $url = self::publicDiskUrl($path);

                        if ($url === null) {
                            return null;
                        }

                        return [
                            'path' => $path,
                            'url' => $url,
                        ];
                    },
                    $paths
                ))),
            ];
        }

        return $normalized;
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

    private static function hexToRgbString(string $hexColor): string
    {
        $hexColor = ltrim(self::normalizeHexColor($hexColor, self::DEFAULT_PRIMARY_COLOR), '#');

        return implode(', ', [
            hexdec(substr($hexColor, 0, 2)),
            hexdec(substr($hexColor, 2, 2)),
            hexdec(substr($hexColor, 4, 2)),
        ]);
    }

    public static function publicDiskUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        try {
            return '/storage/'.ltrim($path, '/');
        } catch (Throwable) {
            return null;
        }
    }
}