<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RequiresConfirmedAction;
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
    use RequiresConfirmedAction;

    public function edit(Request $request): View
    {
        $settings = BrandingSetting::query()->find(1) ?? BrandingSetting::query()->first();
        $storedPaymentDetails = is_array($settings?->payment_details) ? array_values($settings->payment_details) : [];
        [$storedPaymentDetails, $hasAssignedIds] = $this->ensurePaymentDetailIds($storedPaymentDetails);

        if ($settings !== null && $hasAssignedIds) {
            $settings->forceFill([
                'payment_details' => $storedPaymentDetails,
            ])->save();

            Branding::flushCache();
        }

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
        $this->requireConfirmedAction($request);

        $settings = BrandingSetting::query()->find(1) ?? BrandingSetting::query()->first() ?? tap(new BrandingSetting(), static function (BrandingSetting $settings): void {
            $settings->id = 1;
        });
        $branding = Branding::current();

        $request->validate([
            'edit_id' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:100', "regex:/^[\p{L}\p{M}][\p{L}\p{M}\s'.-]*$/u"],
            'bank_name' => ['required', 'string', 'max:120', "regex:/^[\p{L}\p{M}][\p{L}\p{M}\s'.-]*$/u"],
            'bank_number' => ['required', 'string', 'max:120', 'regex:/^[0-9]+$/'],
            'existing_qr_paths' => ['nullable', 'array'],
            'existing_qr_paths.*' => ['nullable', 'string'],
            'remove_qr_paths' => ['nullable', 'array'],
            'remove_qr_paths.*' => ['nullable', 'string'],
            'qr_codes' => ['nullable', 'array'],
            'qr_codes.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ], [
            'name.regex' => 'Name must contain letters only. Spaces, apostrophes, periods, and hyphens are allowed.',
            'bank_name.regex' => 'Bank name must contain letters only. Spaces, apostrophes, periods, and hyphens are allowed.',
            'bank_number.regex' => 'Bank number must contain numbers only. Letters, spaces, symbols, and minus signs are not allowed.',
        ]);

        $disk = Storage::disk('public');
        $existingPaymentDetails = is_array($settings->payment_details) ? array_values($settings->payment_details) : [];
        [$existingPaymentDetails] = $this->ensurePaymentDetailIds($existingPaymentDetails);
        $existingQrPaths = $this->collectQrImagePaths($existingPaymentDetails);
        $paymentDetail = $this->preparePaymentDetail($request, $existingPaymentDetails);
        $editId = $request->filled('edit_id') ? $request->input('edit_id') : null;
        $storedQrPaths = [];

        if ($editId !== null && ! collect($existingPaymentDetails)->contains(static fn (array $detail): bool => ($detail['id'] ?? null) === $editId)) {
            return redirect()
                ->route('admin.payments.edit')
                ->withErrors(['payment_details' => 'The selected payment detail no longer exists.'])
                ->withInput();
        }

        try {
            $storedPaymentDetails = $this->storePaymentDetail($paymentDetail, $storedQrPaths, $editId, $existingPaymentDetails);

            if ($editId !== null) {
                $wasUpdated = false;
                foreach ($existingPaymentDetails as $i => $detail) {
                    if (isset($detail['id']) && $detail['id'] === $editId) {
                        $existingPaymentDetails[$i] = $storedPaymentDetails;
                        $wasUpdated = true;
                        break;
                    }
                }

                if (! $wasUpdated) {
                    throw ValidationException::withMessages([
                        'payment_details' => 'The selected payment detail no longer exists.',
                    ]);
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

    public function destroy(Request $request, string $paymentId): RedirectResponse
    {
        $this->requireConfirmedAction($request);

        $settings = BrandingSetting::query()->find(1) ?? BrandingSetting::query()->first();

        if (! $settings) {
            return redirect()
                ->route('admin.payments.edit')
                ->withErrors(['payment_details' => 'No payment details were found to delete.']);
        }

        $paymentDetails = is_array($settings->payment_details) ? array_values($settings->payment_details) : [];
        [$paymentDetails, $hasAssignedIds] = $this->ensurePaymentDetailIds($paymentDetails);

        if ($hasAssignedIds) {
            $settings->forceFill([
                'payment_details' => $paymentDetails,
            ])->save();
        }

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
            'updated_by' => $request->user()?->id,
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
     * @param  array<int, array<string, mixed>>  $paymentDetails
     * @return array{0: array<int, array<string, mixed>>, 1: bool}
     */
    private function ensurePaymentDetailIds(array $paymentDetails): array
    {
        $hasAssignedIds = false;
        $knownIds = collect($paymentDetails)
            ->map(static fn (array $detail): string => trim((string) ($detail['id'] ?? '')))
            ->filter()
            ->values();

        foreach ($paymentDetails as $index => $detail) {
            $id = trim((string) ($detail['id'] ?? ''));

            if ($id !== '') {
                continue;
            }

            do {
                $id = (string) Str::uuid();
            } while ($knownIds->contains($id));

            $paymentDetails[$index]['id'] = $id;
            $knownIds->push($id);
            $hasAssignedIds = true;
        }

        return [array_values($paymentDetails), $hasAssignedIds];
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
