<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-outline-custom rounded-pill']) }}>
    {{ $slot }}
</button>
