<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-outline-custom']) }}>
    {{ $slot }}
</button>
