<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
            <h5 class="mb-0 fw-bold">{{ $isEditing ? 'Edit Inventory Item' : 'Add Inventory Item' }}</h5>
        </div>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ $isEditing ? route('admin.inventory.update', $item) : route('admin.inventory.store') }}" enctype="multipart/form-data">
                        @csrf
                        @if ($isEditing) @method('PUT') @endif

                        <div class="mb-3">
                            <label class="form-label fw-medium">Name</label>
                            <input name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $item->name) }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">&#8369;</span>
                                    <input name="price" type="number" step="0.01" min="0" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $item->price) }}" required>
                                    @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Quantity</label>
                                <input name="quantity" type="number" min="0" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', $item->quantity ?? 0) }}" required>
                                @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">None</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('category_id', $item->category_id) === (string) $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Condition</label>
                                <select name="condition" class="form-select @error('condition') is-invalid @enderror" required>
                                    @foreach ($conditions as $condition)
                                        <option value="{{ $condition->value }}" @selected(old('condition', $item->condition?->value ?? \App\Enums\ItemCondition::GENTLY_USED->value) === $condition->value)>{{ $condition->label() }}</option>
                                    @endforeach
                                </select>
                                @error('condition') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Status</label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status->value }}" @selected(old('status', $item->status?->value ?? \App\Enums\ItemStatus::ACTIVE->value) === $status->value)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Restock Date & Time <span class="text-muted fw-normal small">(optional)</span></label>
                            <input
                                name="restock_at"
                                type="datetime-local"
                                class="form-control @error('restock_at') is-invalid @enderror"
                                value="{{ old('restock_at', $item->restock_at?->format('Y-m-d\\TH:i')) }}"
                            >
                            <div class="form-text">Set this when an out-of-stock item is expected back so the countdown can be shown to users.</div>
                            @error('restock_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Tags <span class="text-muted fw-normal small">(comma separated)</span></label>
                            <input name="tags" class="form-control" value="{{ old('tags', implode(', ', $item->tags ?? [])) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Item Thumbnail</label>
                            <input
                                type="file"
                                name="image"
                                id="image-input"
                                accept="image/png,image/jpeg,image/webp"
                                class="form-control @error('image') is-invalid @enderror"
                            >
                            <div class="form-text">JPG, PNG, or WebP up to 3MB.</div>
                            @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror

                            <div id="image-preview" class="mt-3" style="display: none;">
                                <img id="preview-img" src="" alt="Preview" class="inventory-thumb-preview">
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

                        <div class="mb-4">
                            <label class="form-label fw-medium">Description</label>
                            <textarea name="description" class="form-control" rows="4">{{ old('description', $item->description) }}</textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">{{ $isEditing ? 'Update Item' : 'Create Item' }}</button>
                            <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
