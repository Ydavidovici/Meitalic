{{-- resources/views/components/nav-link.blade.php --}}
@props(['active'=>false])

@php
    $base    = 'inline-flex items-center px-3 py-2 border-b-2 text-sm font-medium leading-5 transition';
    $on      = 'border-accent text-text';
    $off     = 'border-transparent text-gray-500 hover:text-accent hover:border-accent';
    $classes = $base.' '.($active ? $on : $off);
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
