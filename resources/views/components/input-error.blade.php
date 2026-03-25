@props(['messages'])
@if ($messages)
    @foreach ((array) $messages as $message)
        <div {{ $attributes->merge(['class' => 'invalid-feedback d-block']) }}>{{ $message }}</div>
    @endforeach
@endif
