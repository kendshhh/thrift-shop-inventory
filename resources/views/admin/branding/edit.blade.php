<x-app-layout>
    <x-slot name="header">
        <div>
            <h5 class="mb-1 fw-bold"><i class="bi bi-palette me-2"></i>Branding Settings</h5>
            <p class="text-muted small mb-0">Customize your brand identity, colors, and logo for all pages.</p>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success mb-4" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('status') }}
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted small mb-4">Changes on this screen apply globally to public and authenticated pages.</p>

                    <form method="POST" action="{{ route('admin.branding.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-medium">Brand Name</label>
                            <input
                                type="text"
                                name="brand_name"
                                class="form-control @error('brand_name') is-invalid @enderror"
                                value="{{ old('brand_name', data_get($branding, 'brand_name')) }}"
                                maxlength="80"
                                required
                            >
                            @error('brand_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Brand Tagline <span class="text-muted fw-normal small">(optional)</span></label>
                            <input
                                type="text"
                                name="brand_tagline"
                                class="form-control @error('brand_tagline') is-invalid @enderror"
                                value="{{ old('brand_tagline', data_get($branding, 'brand_tagline')) }}"
                                maxlength="180"
                                placeholder="Example: Fresh finds for every budget"
                            >
                            @error('brand_tagline') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Primary Color</label>
                                <input
                                    type="color"
                                    name="primary_color"
                                    class="form-control form-control-color w-100 @error('primary_color') is-invalid @enderror"
                                    value="{{ old('primary_color', data_get($branding, 'primary_color')) }}"
                                    title="Choose primary color"
                                    required
                                >
                                @error('primary_color') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Secondary Color</label>
                                <input
                                    type="color"
                                    name="secondary_color"
                                    class="form-control form-control-color w-100 @error('secondary_color') is-invalid @enderror"
                                    value="{{ old('secondary_color', data_get($branding, 'secondary_color')) }}"
                                    title="Choose secondary color"
                                    required
                                >
                                @error('secondary_color') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Logo Image <span class="text-muted fw-normal small">(optional)</span></label>
                            <input
                                type="file"
                                name="logo"
                                accept=".jpg,.jpeg,.png,.webp"
                                class="form-control @error('logo') is-invalid @enderror"
                            >
                            <div class="form-text">Accepted formats: JPG, JPEG, PNG, WEBP. Max size: 3 MB.</div>
                            @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        @if (data_get($branding, 'logo_url'))
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ data_get($branding, 'logo_url') }}" alt="{{ data_get($branding, 'brand_name') }} logo" class="inventory-thumb-preview">
                                    <div>
                                        <div class="fw-medium">Current Logo</div>
                                        <div class="small text-muted">Uploading a new image will replace this logo.</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="form-check mb-4">
                            <input
                                type="checkbox"
                                name="remove_logo"
                                id="remove_logo"
                                value="1"
                                class="form-check-input"
                                @checked(old('remove_logo'))
                            >
                            <label for="remove_logo" class="form-check-label">Remove current logo</label>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Save Branding</button>
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-custom">Back to Dashboard</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>