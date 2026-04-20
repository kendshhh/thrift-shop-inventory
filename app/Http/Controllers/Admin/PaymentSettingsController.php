<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandingSetting;
use App\Support\Branding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PaymentSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $settings = BrandingSetting::query()->find(1) ?? BrandingSetting::query()->first();
        $storedPaymentDetails = is_array($settings?->payment_details) ? array_values($settings->payment_details) : [];
        // Normalize for robust QR preview
        $normalizedPaymentDetails = \App\Support\Branding::normalizePaymentDetails($storedPaymentDetails);
        $editingPaymentId = $request->filled('edit') ? $request->query('edit') : null;
        $editingPaymentDetail = null;

        if ($editingPaymentId !== null) {
            foreach ($storedPaymentDetails as $detail) {
                if (isset($detail['id']) && $detail['id'] === $editingPaymentId) {
                    $editingPaymentDetail = $detail;
                    break;
                }
            }
        }

        return view('admin.payments.edit', [
            'settings' => $settings,
            'branding' => Branding::current(),
            'editingPaymentId' => $editingPaymentId,
            'editingPaymentDetail' => $editingPaymentDetail,
            'savedPaymentDetails' => $normalizedPaymentDetails,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = BrandingSetting::query()->find(1) ?? BrandingSetting::query()->first() ?? tap(new BrandingSetting(), static function (BrandingSetting $settings): void {
            $settings->id = 1;
        });
        $branding = Branding::current();

        $request->validate([
            'edit_id' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:100'],
            'bank_name' => ['required', 'string', 'max:120'],
            'bank_number' => ['required', 'string', 'max:120'],
            'existing_qr_paths' => ['nullable', 'array'],
            'existing_qr_paths.*' => ['nullable', 'string'],
            'remove_qr_paths' => ['nullable', 'array'],
            'remove_qr_paths.*' => ['nullable', 'string'],
            'qr_codes' => ['nullable', 'array'],
            'qr_codes.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $disk = Storage::disk('public');
        $existingPaymentDetails = is_array($settings->payment_details) ? array_values($settings->payment_details) : [];
        $existingQrPaths = $this->collectQrImagePaths($existingPaymentDetails);
        $paymentDetail = $this->preparePaymentDetail($request, $existingPaymentDetails);
        $editId = $request->filled('edit_id') ? $request->input('edit_id') : null;
        $storedQrPaths = [];

        try {
            $storedPaymentDetails = $this->storePaymentDetail($paymentDetail, $storedQrPaths, $editId, $existingPaymentDetails);

            if ($editId !== null) {
                foreach ($existingPaymentDetails as $i => $detail) {
                    if (isset($detail['id']) && $detail['id'] === $editId) {
                        $existingPaymentDetails[$i] = $storedPaymentDetails;
                        break;
                    }
                }
            } else {
                $existingPaymentDetails[] = $storedPaymentDetails;
            }

            $settings->fill([
                'brand_name' => $settings->brand_name ?: data_get($branding, 'brand_name', (string) config('app.name', 'Thrift Shop')),
                'brand_tagline' => $settings->brand_tagline ?? data_get($branding, 'brand_tagline'),
                'primary_color' => $settings->primary_color ?: data_get($branding, 'primary_color', '#0EA5E9'),
                'secondary_color' => $settings->secondary_color ?: data_get($branding, 'secondary_color', '#2563EB'),
                'logo_path' => $settings->logo_path ?? data_get($branding, 'logo_path'),
                'payment_details' => array_values($existingPaymentDetails),
                'updated_by' => $request->user()?->id,
            ]);

            $settings->save();
        } catch (Throwable $exception) {
            foreach ($storedQrPaths as $path) {
                if ($disk->exists($path)) {
                    $disk->delete($path);
                }
            }

            throw $exception;
        }

        foreach (array_diff($existingQrPaths, $this->collectQrImagePaths($settings->payment_details ?? [])) as $path) {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }

        Branding::flushCache();

        return redirect()
            ->route('admin.payments.edit')
            ->with('status', 'Payment settings updated successfully.');
    }

    public function destroy(string $paymentId): RedirectResponse
    {
        $settings = BrandingSetting::query()->find(1) ?? BrandingSetting::query()->first();

        if (! $settings) {
            return redirect()
                ->route('admin.payments.edit')
                ->withErrors(['payment_details' => 'No payment details were found to delete.']);
        }

        $paymentDetails = is_array($settings->payment_details) ? array_values($settings->payment_details) : [];

        $found = false;
        foreach ($paymentDetails as $i => $detail) {
            if (isset($detail['id']) && $detail['id'] === $paymentId) {
                $found = true;
                $disk = Storage::disk('public');
                $existingQrPaths = $this->collectQrImagePaths([$detail]);
                unset($paymentDetails[$i]);
                break;
            }
        }

        if (! $found) {
            return redirect()
                ->route('admin.payments.edit')
                ->withErrors(['payment_details' => 'The selected payment detail no longer exists.']);
        }

        $settings->fill([
            'payment_details' => array_values($paymentDetails),
            'updated_by' => request()->user()?->id,
        ]);

        $settings->save();

        if (isset($disk) && isset($existingQrPaths)) {
            foreach ($existingQrPaths as $path) {
                if ($disk->exists($path)) {
                    $disk->delete($path);
                }
            }
        }

        Branding::flushCache();

        return redirect()
            ->route('admin.payments.edit')
            ->with('status', 'Payment detail deleted successfully.');
    }

    /**
     * @return array{name: string, bank_name: string, bank_number: string, retained_qr_paths: array<int, string>, new_qr_files: array<int, UploadedFile>}
     *
     * @throws ValidationException
     */
    private function preparePaymentDetail(Request $request, array $storedPaymentDetails): array
    {
        $knownQrPaths = $this->collectQrImagePaths($storedPaymentDetails);
        $retainedQrPaths = $this->sanitizeKnownPaths($request->input('existing_qr_paths', []), $knownQrPaths);
        $removedQrPaths = $this->sanitizeKnownPaths($request->input('remove_qr_paths', []), $knownQrPaths);
        $retainedQrPaths = array_values(array_diff($retainedQrPaths, $removedQrPaths));

        $newQrFiles = $request->file('qr_codes', []);
        $newQrFiles = is_array($newQrFiles)
            ? array_values(array_filter($newQrFiles, static fn ($file) => $file instanceof UploadedFile))
            : [];

        if (count($retainedQrPaths) + count($newQrFiles) === 0) {
            throw ValidationException::withMessages([
                'qr_codes' => 'Upload at least one QR image for each payment detail entry.',
            ]);
        }

        return [
            'name' => trim((string) $request->input('name', '')),
            'bank_name' => trim((string) $request->input('bank_name', '')),
            'bank_number' => trim((string) $request->input('bank_number', '')),
            'retained_qr_paths' => $retainedQrPaths,
            'new_qr_files' => $newQrFiles,
        ];
    }

    /**
     * @param  array{name: string, bank_name: string, bank_number: string, retained_qr_paths: array<int, string>, new_qr_files: array<int, UploadedFile>}  $paymentDetail
     * @param  array<int, string>  $storedQrPaths
     * @return array{name: string, bank_name: string, bank_number: string, qr_image_paths: array<int, string>}
     */
    private function storePaymentDetail(array $paymentDetail, array &$storedQrPaths, ?string $editId = null, array $existingPaymentDetails = []): array
    {
        $qrImagePaths = $paymentDetail['retained_qr_paths'];

        foreach ($paymentDetail['new_qr_files'] as $file) {
            $path = $file->store('payment-details', 'public');
            $storedQrPaths[] = $path;
            $qrImagePaths[] = $path;
        }

        $id = $editId;
        if (!$id) {
            // Generate a new UUID, ensure it's unique among existing entries
            do {
                $id = (string) Str::uuid();
            } while (collect($existingPaymentDetails)->pluck('id')->contains($id));
        }
        return [
            'id' => $id,
            'name' => $paymentDetail['name'],
            'bank_name' => $paymentDetail['bank_name'],
            'bank_number' => $paymentDetail['bank_number'],
            'qr_image_paths' => array_values(array_unique($qrImagePaths)),
        ];
    }

    /**
     * @param  mixed  $values
     * @param  array<int, string>  $knownQrPaths
     * @return array<int, string>
     */
    private function sanitizeKnownPaths(mixed $values, array $knownQrPaths): array
    {
        if (! is_array($values) || $knownQrPaths === []) {
            return [];
        }

        $paths = array_values(array_unique(array_filter(
            array_map(static fn ($path) => trim((string) $path), $values),
            static fn ($path) => $path !== ''
        )));

        return array_values(array_intersect($paths, $knownQrPaths));
    }

    /**
     * @param  array<int, array<string, mixed>>  $paymentDetails
     * @return array<int, string>
     */
    private function collectQrImagePaths(array $paymentDetails): array
    {
        $paths = [];

        foreach ($paymentDetails as $entry) {
            foreach ((array) data_get($entry, 'qr_image_paths', []) as $path) {
                $path = trim((string) $path);

                if ($path === '') {
                    continue;
                }

                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }
}