<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-danger px-4 rounded-pill']) }}>
    {{ $slot }}
</button>
