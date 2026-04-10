@props([
    'type' => 'submit',
    'variant' => 'primary',
    'id' => null,
])

@php
    $classes = $variant === 'primary' ? 'btn-primary w-full' : 'btn-secondary w-full';
@endphp

<button
    type="{{ $type }}"
    @if($id) id="{{ $id }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{ $slot }}
</button>
