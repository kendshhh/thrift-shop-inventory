@php
    $messages = collect($errors->getBags())
        ->flatMap(function ($bag) {
            return collect($bag->messages())
                ->except(['app'])
                ->flatMap(fn (array $items) => $items);
        })
        ->filter()
        ->unique()
        ->values();
@endphp

@if ($messages->isNotEmpty())
    <div class="alert alert-danger alert-dismissible fade show validation-summary mb-4" role="alert">
        <div class="fw-semibold mb-2">Please check the highlighted fields and try again.</div>
        <ul class="mb-0 ps-3 small">
            @foreach ($messages->take(5) as $message)
                <li>{{ $message }}</li>
            @endforeach

            @if ($messages->count() > 5)
                <li>{{ $messages->count() - 5 }} more issue(s) are listed below.</li>
            @endif
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
