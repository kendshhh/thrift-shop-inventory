<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
            <h5 class="mb-0 fw-bold">{{ $isEditing ? 'Edit Inventory Item' : 'Add Inventory Item' }}</h5>
        </div>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card glass-card surface-section">
                <div class="card-body form-shell">
                    <form method="POST" action="{{ $isEditing ? route('admin.inventory.update', $item) : route('admin.inventory.store') }}" enctype="multipart/form-data" data-confirm-action data-confirm-message='Save this inventory item?' data-confirm-variant='primary' data-confirm-label='Save'>
                        @csrf
                        @if ($isEditing) @method('PUT') @endif

                        <div class="form-section-title">Core details</div>
                        <div class="mb-3">
                            <label class="form-label form-label-modern">Name</label>
                            <input name="name" class="form-control form-control-modern @error('name') is-invalid @enderror" value="{{ old('name', $item->name) }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label form-label-modern">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">&#8369;</span>
                                    <input
                                        name="price"
                                        type="text"
                                        inputmode="decimal"
                                        pattern="[0-9]+(\.[0-9]{1,2})?"
                                        class="form-control form-control-modern @error('price') is-invalid @enderror"
                                        value="{{ old('price', $item->price) }}"
                                        data-live-validate="nonnegative-number"
                                        data-warning-target="inventory-price-warning"
                                        data-warning-message-invalid="Price must use numbers only."
                                        data-warning-message-negative="Price cannot be negative. Enter 0 or a higher amount."
                                        required
                                    >
                                    @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-text">Numbers only. Negative values like "-1" are not allowed.</div>
                                <div id="inventory-price-warning" class="text-danger small mt-2" style="display: none;"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-label-modern">Quantity</label>
                                <input
                                    name="quantity"
                                    type="text"
                                    inputmode="numeric"
                                    pattern="[0-9]+"
                                    class="form-control form-control-modern @error('quantity') is-invalid @enderror"
                                    value="{{ old('quantity', $item->quantity ?? 0) }}"
                                    data-live-validate="nonnegative-integer"
                                    data-warning-target="inventory-quantity-warning"
                                    data-warning-message-invalid="Quantity must be a whole number."
                                    data-warning-message-negative="Quantity cannot be negative. Enter 0 or a higher value."
                                    required
                                >
                                <div class="form-text">Whole numbers only. Negative values like "-1" are not allowed.</div>
                                <div id="inventory-quantity-warning" class="text-danger small mt-2" style="display: none;"></div>
                                @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label form-label-modern">Category</label>
                            <select name="category_id" class="form-select form-control-modern">
                                <option value="">None</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('category_id', $item->category_id) === (string) $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label form-label-modern">Condition</label>
                                <select name="condition" class="form-select form-control-modern @error('condition') is-invalid @enderror" required>
                                    @foreach ($conditions as $condition)
                                        <option value="{{ $condition->value }}" @selected(old('condition', $item->condition?->value ?? \App\Enums\ItemCondition::GENTLY_USED->value) === $condition->value)>{{ $condition->label() }}</option>
                                    @endforeach
                                </select>
                                @error('condition') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-label-modern">Status</label>
                                <select name="status" class="form-select form-control-modern @error('status') is-invalid @enderror" required>
                                    @foreach (collect($statuses)->filter(fn ($status) => $status->value !== \App\Enums\ItemStatus::OUT_OF_STOCK->value) as $status)
                                        <option value="{{ $status->value }}" @selected(old('status', $item->status?->value ?? \App\Enums\ItemStatus::ACTIVE->value) === $status->value)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Available items switch to Out of Stock automatically when quantity or available stock reaches 0. Use Archived only to hide an item intentionally.</div>
                                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="form-section-title">Availability and media</div>
                        <div class="mb-3">
                            <label class="form-label form-label-modern">Restock Date & Time <span class="text-muted fw-normal small">(optional)</span></label>
                            <input
                                name="restock_at"
                                type="datetime-local"
                                class="form-control form-control-modern @error('restock_at') is-invalid @enderror"
                                value="{{ old('restock_at', $item->restock_at?->format('Y-m-d\\TH:i')) }}"
                            >
                            <div class="form-text">Set this when an out-of-stock item is expected back so the countdown can be shown to users.</div>
                            @error('restock_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label form-label-modern">Tags <span class="text-muted fw-normal small">(comma separated)</span></label>
                            <input name="tags" class="form-control form-control-modern" value="{{ old('tags', implode(', ', $item->tags ?? [])) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label form-label-modern">Item Thumbnail</label>
                            <input
                                type="file"
                                name="image"
                                id="image-input"
                                accept="image/png,image/jpeg,image/webp"
                                class="form-control form-control-modern @error('image') is-invalid @enderror"
                            >
                            <div class="alert alert-info py-2 px-3 mt-2 mb-2 small">
                                16:9 format only. Capture image with white background. JPG, PNG, or WebP up to 3MB.
                            </div>
                            <div id="image-ratio-hint" class="form-text">Recommended example: 1600x900, 1280x720, or 1920x1080.</div>
                            <div id="image-ratio-warning" class="text-danger small mt-2" style="display: none;">
                                Selected image is not 16:9 and will be rejected when you save.
                            </div>
                            @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror

                            <div id="image-preview" class="mt-3" style="display: none;">
                                <div class="ratio ratio-16x9 border rounded-3 overflow-hidden bg-light">
                                    <img id="preview-img" src="" alt="Preview" class="w-100 h-100" style="object-fit: contain; background: #fff;">
                                </div>
                            </div>

                            @if ($isEditing && $item->imageUrl())
                                <div class="d-flex align-items-center gap-3 mt-3">
                                    <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" class="inventory-thumb-preview">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" value="1" id="remove_image" name="remove_image" @checked(old('remove_image'))>
                                        <label class="form-check-label" for="remove_image">Remove current image</label>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="form-section-title">Descriptions and seller info</div>
                        <div class="mb-4">
                            <label class="form-label form-label-modern">Description</label>
                            <textarea name="description" class="form-control form-control-modern" rows="4">{{ old('description', $item->description) }}</textarea>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label form-label-modern">Seller Name</label>
                                <input
                                    name="seller_name"
                                    class="form-control form-control-modern @error('seller_name') is-invalid @enderror"
                                    value="{{ old('seller_name', $item->seller_name) }}"
                                    data-live-validate="name-only"
                                    data-warning-target="inventory-seller-name-warning"
                                    data-warning-message-invalid="Seller name must contain letters only. Spaces, apostrophes, periods, and hyphens are allowed."
                                    required
                                >
                                <div id="inventory-seller-name-warning" class="text-danger small mt-2" style="display: none;"></div>
                                @error('seller_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-label-modern">Contact Number</label>
                                <input
                                    name="seller_contact_number"
                                    type="text"
                                    inputmode="numeric"
                                    pattern="[0-9]+"
                                    class="form-control form-control-modern @error('seller_contact_number') is-invalid @enderror"
                                    value="{{ old('seller_contact_number', $item->seller_contact_number) }}"
                                    data-live-validate="digits-only"
                                    data-warning-target="inventory-contact-warning"
                                    data-warning-message-invalid="Contact number must contain numbers only."
                                    required
                                >
                                <div class="form-text">Numbers only. Do not enter letters, spaces, or special characters.</div>
                                <div id="inventory-contact-warning" class="text-danger small mt-2" style="display: none;"></div>
                                @error('seller_contact_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="action-row">
                            <button type="submit" class="btn btn-primary rounded-pill">{{ $isEditing ? 'Update Item' : 'Create Item' }}</button>
                            <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary rounded-pill">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
