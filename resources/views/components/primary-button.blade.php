<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-primary px-4 rounded-pill']) }}>
    {{ $slot }}
</button>
