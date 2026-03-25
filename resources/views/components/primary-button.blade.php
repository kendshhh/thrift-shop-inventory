<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-primary px-4']) }}>
    {{ $slot }}
</button>
