@props(['value' => ''])
<label {{ $attributes->merge(['class' => 'form-label form-label-modern']) }}>
    {{ $value ?: $slot }}
</label>
