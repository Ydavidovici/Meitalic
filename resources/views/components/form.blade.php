@props([
  'method' => 'POST',
  'action' => '',
])

@php
    // Normalize the HTTP method for the actual <form> tag
    $method = strtoupper($method);
    $formMethod = in_array($method, ['GET','POST']) ? $method : 'POST';
    // If it’s PUT/PATCH/DELETE, we’ll spoof it
    $spoof = in_array($method, ['PUT','PATCH','DELETE']) ? $method : null;
@endphp

<form
    method="{{ $formMethod }}"
    action="{{ $action }}"
    {{ $attributes->merge(['class' => $attributes->get('class', 'space-y-6')]) }}
>
    @csrf
    @if($spoof)
        @method($spoof)
    @endif

    {{ $slot }}
</form>
