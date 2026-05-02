<x-app-layout>
    @php
        $brandName = old('brand_name', data_get($branding, 'brand_name'));
        $brandTagline = old('brand_tagline', data_get($branding, 'brand_tagline')) ?: 'Shape the tone customers see first.';
        $primaryColor = old('primary_color', data_get($branding, 'primary_color'));
        $secondaryColor = old('secondary_color', data_get($branding, 'secondary_color'));
        $logoUrl = data_get($branding, 'logo_url');
    @endphp

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
            <div class="card glass-card surface-section">
                <div class="card-body form-shell">
                    <p class="text-muted small mb-4">Changes on this screen apply globally to public and authenticated pages.</p>

                    <div class="brand-preview-card mb-4">
                        <span class="brand-preview-badge mb-3"><i class="bi bi-stars"></i> Live brand direction</span>
                        <div class="d-flex align-items-start gap-3 mb-3">
                            @if ($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $brandName }} logo" class="brand-preview-logo">
                            @else
                                <div class="brand-preview-logo brand-preview-fallback">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($brandName ?: 'B', 0, 1)) }}</div>
                            @endif
                            <div>
                                <h4 class="mb-1 fw-bold">{{ $brandName }}</h4>
                                <p class="mb-0 text-muted">{{ $brandTagline }}</p>
                            </div>
                        </div>
                        <div class="brand-swatch-grid">
                            <div class="brand-swatch-card">
                                <div class="brand-swatch" style="background: {{ $primaryColor }};"></div>
                                <div class="small fw-semibold">Primary color</div>
                                <div class="small text-muted">{{ $primaryColor }}</div>
                            </div>
                            <div class="brand-swatch-card">
                                <div class="brand-swatch" style="background: {{ $secondaryColor }};"></div>
                                <div class="small fw-semibold">Secondary color</div>
                                <div class="small text-muted">{{ $secondaryColor }}</div>
                            </div>
                        </div>
                    </div>

                    <ul class="brand-guidance-list mb-4">
                        <li class="brand-guidance-item">Primary color should carry most emphasis across buttons, chips, and accent glow.</li>
                        <li class="brand-guidance-item">Secondary color works best when it complements the primary instead of competing with it.</li>
                        <li class="brand-guidance-item">Wide logos with clean padding render best in the navbar and page footer.</li>
                    </ul>

                    <form method="POST" action="{{ route('admin.branding.update') }}" enctype="multipart/form-data" data-confirm-action data-confirm-message='Save the branding changes?' data-confirm-variant='primary' data-confirm-label='Save'>
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label form-label-modern">Brand Name</label>
                            <input
                                type="text"
                                name="brand_name"
                                class="form-control form-control-modern @error('brand_name') is-invalid @enderror"
                                value="{{ $brandName }}"
                                maxlength="80"
                                data-live-validate="name-only"
                                data-warning-target="brand-name-warning"
                                data-warning-message-invalid="Brand name must contain letters only. Spaces, apostrophes, periods, and hyphens are allowed."
                                required
                            >
                            <div id="brand-name-warning" class="text-danger small mt-2" style="display: none;"></div>
                            @error('brand_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label form-label-modern">Brand Tagline <span class="text-muted fw-normal small">(optional)</span></label>
                            <input
                                type="text"
                                name="brand_tagline"
                                class="form-control form-control-modern @error('brand_tagline') is-invalid @enderror"
                                value="{{ $brandTagline === 'Shape the tone customers see first.' ? old('brand_tagline', data_get($branding, 'brand_tagline')) : $brandTagline }}"
                                maxlength="180"
                                placeholder="Example: Fresh finds for every budget"
                            >
                            @error('brand_tagline') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label form-label-modern">Primary Color</label>
                                <input
                                    type="color"
                                    name="primary_color"
                                    class="form-control form-control-modern form-control-color w-100 @error('primary_color') is-invalid @enderror"
                                    value="{{ $primaryColor }}"
                                    title="Choose primary color"
                                    required
                                >
                                @error('primary_color') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-label-modern">Secondary Color</label>
                                <input
                                    type="color"
                                    name="secondary_color"
                                    class="form-control form-control-modern form-control-color w-100 @error('secondary_color') is-invalid @enderror"
                                    value="{{ $secondaryColor }}"
                                    title="Choose secondary color"
                                    required
                                >
                                @error('secondary_color') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label form-label-modern">Logo Image <span class="text-muted fw-normal small">(optional)</span></label>
                            <input
                                type="file"
                                name="logo"
                                accept=".jpg,.jpeg,.png,.webp"
                                class="form-control form-control-modern @error('logo') is-invalid @enderror"
                            >
                            <div class="form-text">Accepted formats: JPG, JPEG, PNG, WEBP. Max size: 3 MB.</div>
                            @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        @if ($logoUrl)
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ $logoUrl }}" alt="{{ $brandName }} logo" class="inventory-thumb-preview">
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
                            <button type="submit" class="btn btn-primary rounded-pill">Save Branding</button>
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-custom rounded-pill">Back to Dashboard</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
