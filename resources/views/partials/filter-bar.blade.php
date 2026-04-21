@php
    $cardClass = $cardClass ?? 'card glass-card customer-filter-card mb-4';
    $formClass = $formClass ?? 'row g-3 align-items-end';
    $actionsColClass = $actionsColClass ?? 'col-12 col-lg-2 d-flex gap-2';
    $submitLabel = $submitLabel ?? 'Apply';
    $showReset = $showReset ?? true;
@endphp

<div class="{{ $cardClass }}">
    <div class="card-body form-shell">
        <form method="GET" action="{{ $action }}" class="{{ $formClass }}">
            @foreach ($fields as $field)
                @php
                    $fieldId = $field['id'] ?? $field['name'];
                    $fieldType = $field['type'] ?? 'text';
                    $fieldClass = $field['class'] ?? 'form-control form-control-modern';
                    $fieldValue = $field['value'] ?? '';
                    $labelClass = $field['labelClass'] ?? 'form-label form-label-modern';
                @endphp

                <div class="{{ $field['colClass'] ?? 'col-12 col-lg-3' }}">
                    <label for="{{ $fieldId }}" class="{{ $labelClass }}">{{ $field['label'] }}</label>

                    @if ($fieldType === 'select')
                        <select id="{{ $fieldId }}" name="{{ $field['name'] }}" class="{{ $field['class'] ?? 'form-select form-control-modern' }}">
                            @foreach ($field['options'] ?? [] as $option)
                                <option value="{{ $option['value'] }}" @selected((string) $fieldValue === (string) $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    @else
                        <input
                            id="{{ $fieldId }}"
                            type="{{ $fieldType }}"
                            name="{{ $field['name'] }}"
                            class="{{ $fieldClass }}"
                            value="{{ $fieldValue }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}"
                        >
                    @endif
                </div>
            @endforeach

            <div class="{{ $actionsColClass }}">
                <button type="submit" class="btn btn-primary flex-fill rounded-pill"><i class="bi bi-funnel me-1"></i>{{ $submitLabel }}</button>
                @if ($showReset && ($hasFilters ?? false))
                    <a href="{{ $resetUrl ?? $action }}" class="btn btn-outline-secondary rounded-pill">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>