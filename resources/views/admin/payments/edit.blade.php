<x-app-layout>
    @php
        $savedPaymentDetails = $savedPaymentDetails ?? data_get($branding, 'payment_details', []);
        $oldEditId = old('edit_id');
        $editingPaymentId = $oldEditId !== null && $oldEditId !== '' ? $oldEditId : $editingPaymentId;
        $editingPaymentDetail = $editingPaymentDetail ?? [];
        $currentQrPaths = old('existing_qr_paths', data_get($editingPaymentDetail, 'qr_image_paths', []));
    @endphp

    <x-slot name="header">
        <div>
            <h5 class="mb-1 fw-bold"><i class="bi bi-qr-code me-2"></i>Payment Settings</h5>
            <p class="text-muted small mb-0">Manage the payment details and QR images customers can use for manual settlement.</p>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success mb-4" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('status') }}
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="card glass-card surface-section">
                <div class="card-body form-shell">
                    <div class="alert alert-info mb-4 py-2" role="alert">
                        <i class="bi bi-info-circle me-2"></i>Saved payment details automatically appear on customer reservation details.
                    </div>
                    <p class="text-muted small mb-4">Customers will see these payment details on the public site, item pages, and reservation pages.</p>

                    <form method="POST" action="{{ route('admin.payments.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="edit_id" value="{{ $editingPaymentId }}">

                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h6 class="fw-semibold mb-1">{{ $editingPaymentId !== null && $editingPaymentId !== '' ? 'Edit Payment Detail' : 'Add Payment Detail' }}</h6>
                                <p class="text-muted small mb-0">{{ $editingPaymentId !== null && $editingPaymentId !== '' ? 'Update the selected payment detail and save to apply the changes.' : 'Add one payment detail at a time. After saving, this form resets so you can add a new one.' }}</p>
                            </div>
                            @if ($editingPaymentId !== null && $editingPaymentId !== '')
                                <a href="{{ route('admin.payments.edit') }}" class="btn btn-outline-secondary btn-sm rounded-pill">
                                    <i class="bi bi-plus-circle me-1"></i>New Payment Detail
                                </a>
                            @endif
                        </div>

                        @error('payment_details')
                            <div class="alert alert-danger py-2 small mb-3">{{ $message }}</div>
                        @enderror

                        <div class="payment-editor-card">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label form-label-modern">Name</label>
                                    <input
                                        type="text"
                                        name="name"
                                        class="form-control form-control-modern @error('name') is-invalid @enderror"
                                        value="{{ old('name', data_get($editingPaymentDetail, 'name')) }}"
                                        maxlength="100"
                                        placeholder="Example: Maria Santos"
                                    >
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label form-label-modern">Bank</label>
                                    <input
                                        type="text"
                                        name="bank_name"
                                        class="form-control form-control-modern @error('bank_name') is-invalid @enderror"
                                        value="{{ old('bank_name', data_get($editingPaymentDetail, 'bank_name')) }}"
                                        maxlength="120"
                                        placeholder="Example: BDO"
                                    >
                                    @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label form-label-modern">Bank Number</label>
                                    <input
                                        type="text"
                                        name="bank_number"
                                        class="form-control form-control-modern @error('bank_number') is-invalid @enderror"
                                        value="{{ old('bank_number', data_get($editingPaymentDetail, 'bank_number')) }}"
                                        maxlength="120"
                                        placeholder="Example: 012345678901"
                                    >
                                    @error('bank_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="form-label form-label-modern">QR Code Images</label>
                                <input
                                    type="file"
                                    name="qr_codes[]"
                                    accept=".jpg,.jpeg,.png,.webp"
                                    multiple
                                    class="form-control form-control-modern @error('qr_codes') is-invalid @enderror"
                                >
                                <div class="form-text">Accepted formats: JPG, JPEG, PNG, WEBP. Max size: 3 MB each.</div>
                                @error('qr_codes') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                @error('qr_codes.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            @if ($currentQrPaths !== [])
                                <div class="mt-3">
                                    <div class="fw-medium small mb-2">Current QR Images</div>
                                    <div class="row g-3">
                                        @foreach ($currentQrPaths as $qrPath)
                                            <div class="col-sm-6 col-lg-4">
                                                <input type="hidden" name="existing_qr_paths[]" value="{{ $qrPath }}">
                                                <div class="payment-editor-qr-card">
                                                    <img
                                                        src="{{ \App\Support\Branding::publicDiskUrl($qrPath) }}"
                                                        alt="Payment QR code {{ $loop->iteration }}"
                                                        class="payment-qr-thumb"
                                                        data-lightbox-image
                                                        tabindex="0"
                                                    >
                                                    <div class="form-check mt-2">
                                                        <input
                                                            class="form-check-input"
                                                            type="checkbox"
                                                            name="remove_qr_paths[]"
                                                            value="{{ $qrPath }}"
                                                            id="remove_qr_{{ $loop->index }}"
                                                            @checked(in_array($qrPath, (array) old('remove_qr_paths', []), true))
                                                        >
                                                        <label class="form-check-label small" for="remove_qr_{{ $loop->index }}">Remove this QR image</label>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary rounded-pill">{{ $editingPaymentId !== null && $editingPaymentId !== '' ? 'Update Payment Detail' : 'Add Payment Detail' }}</button>
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-custom rounded-pill">Back to Dashboard</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-4">
                <div class="mb-3">
                    <h6 class="fw-semibold mb-1">Saved Payment Details</h6>
                    <p class="text-muted small mb-0">Edit or delete each saved payment detail entry below.</p>
                </div>

                @if ($savedPaymentDetails !== [])
                    <div class="d-flex flex-column gap-3">
                        @foreach ($savedPaymentDetails as $savedEntry)
                            <div class="card glass-card surface-section saved-payment-card">
                                <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                    <strong>Payment Detail {{ $loop->iteration }}</strong>
                                    <div class="d-flex gap-2">
                                        @if (!empty($savedEntry['id']))
                                            <a href="{{ route('admin.payments.edit', ['edit' => $savedEntry['id']]) }}" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-pencil me-1"></i>Edit</a>
                                            <form method="POST" action="{{ route('admin.payments.destroy', $savedEntry['id']) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill"><i class="bi bi-trash me-1"></i>Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                                <div class="card-body">
                                    @include('partials.payment-details', [
                                        'paymentDetails' => [$savedEntry],
                                        'showTitle' => false,
                                        'compact' => true,
                                        'emptyMessage' => null,
                                    ])
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="card glass-card surface-section saved-payment-card">
                        <div class="card-body">
                            @include('partials.payment-details', [
                                'paymentDetails' => [],
                                'showTitle' => false,
                                'compact' => true,
                                'emptyMessage' => 'No saved payment details yet.',
                            ])
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>