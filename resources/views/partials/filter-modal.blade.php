@php
    $modalId = $modalId ?? 'filterModal-' . uniqid();
    $submitLabel = $submitLabel ?? 'Apply';
    $showReset = $showReset ?? true;
@endphp

<!-- Filter Modal -->
<div class="modal fade filter-modal" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered filter-modal-dialog">
        <div class="modal-content glass-card filter-modal-content">
            <div class="modal-header border-bottom-0 justify-content-center text-center">
                <h5 class="modal-title" id="{{ $modalId }}-title">
                    <i class="bi bi-funnel me-2"></i>Filters
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="GET" action="{{ $action }}" id="{{ $modalId }}-form" class="filter-modal-form mx-auto" data-filter-form>
                    @foreach ($fields as $field)
                        @php
                            $fieldId = $field['id'] ?? $field['name'];
                            $fieldType = $field['type'] ?? 'text';
                            $fieldClass = $field['class'] ?? 'form-control form-control-modern';
                            $fieldValue = $field['value'] ?? '';
                            $labelClass = $field['labelClass'] ?? 'form-label form-label-modern';
                            $selectedOption = collect($field['options'] ?? [])->first(
                                fn ($option) => (string) ($option['value'] ?? '') === (string) $fieldValue
                            );
                            $selectedOptionLabel = $selectedOption['label'] ?? ($field['placeholder'] ?? 'Select option');
                        @endphp

                        <div class="mb-3 text-center">
                            <label for="{{ $fieldId }}" class="{{ $labelClass }}">{{ $field['label'] }}</label>

                            @if ($fieldType === 'select')
                                <div class="dropdown" data-option-dropdown>
                                    <input
                                        id="{{ $fieldId }}"
                                        type="hidden"
                                        name="{{ $field['name'] }}"
                                        value="{{ $fieldValue }}"
                                        data-option-input
                                    >
                                    <button
                                        class="btn btn-outline-custom w-100 rounded-pill dropdown-toggle filter-modal-select"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                    >
                                        <span data-option-label>{{ $selectedOptionLabel }}</span>
                                    </button>
                                    <ul class="dropdown-menu w-100 mt-2 border-0 shadow-lg glass-dropdown-menu filter-modal-dropdown-menu">
                                        @foreach ($field['options'] ?? [] as $option)
                                            <li>
                                                <button
                                                    type="button"
                                                    class="dropdown-item"
                                                    data-option-value="{{ $option['value'] }}"
                                                    data-option-text="{{ $option['label'] }}"
                                                >{{ $option['label'] }}</button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @else
                                <input
                                    id="{{ $fieldId }}"
                                    type="{{ $fieldType }}"
                                    name="{{ $field['name'] }}"
                                    class="{{ $fieldClass }}"
                                    value="{{ $fieldValue }}"
                                    placeholder="{{ $field['placeholder'] ?? '' }}"
                                    @if ($field['name'] === 'search') data-live-search="true" data-live-search-delay="350" autocomplete="off" @endif
                                >
                            @endif
                        </div>
                    @endforeach
                </form>
            </div>
            <div class="modal-footer border-top-0 d-flex gap-2 justify-content-center">
                @if ($showReset && ($hasFilters ?? false))
                    <a href="{{ $resetUrl ?? $action }}" class="btn btn-outline-secondary flex-fill rounded-pill">Reset</a>
                @endif
                <button type="submit" form="{{ $modalId }}-form" class="btn btn-primary flex-fill rounded-pill">
                    <i class="bi bi-funnel me-1"></i>{{ $submitLabel }}
                </button>
            </div>
        </div>
    </div>
</div>
