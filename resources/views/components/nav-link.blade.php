@props(['active'=>false])

@php
    $base    = 'inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition no-underline';
    $on      = 'text-text';             // active = dark gray
    $off     = 'text-gray-500 hover:text-accent'; // inactive = light gray → pink on hover
    $classes = $base . ' ' . ($active ? $on : $off);
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
