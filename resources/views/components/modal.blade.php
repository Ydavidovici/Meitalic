{{-- resources/views/components/modal.blade.php --}}
@props([
  'name',
  'show'     => false,
  'maxWidth' => 'md',
])

@php
    $maxWidthClasses = [
      'sm'  => 'max-w-sm',
      'md'  => 'max-w-md',
      'lg'  => 'max-w-lg',
      'xl'  => 'max-w-xl',
      '2xl' => 'max-w-2xl',
    ][$maxWidth];
@endphp

<div
    x-data="{ show: @js($show) }"
    x-init="$watch('show', val => document.body.classList.toggle('overflow-y-hidden', val))"
    @open-modal.window="if ($event.detail==='{{ $name }}') show = true"
    @close-modal.window="if ($event.detail==='{{ $name }}') show = false"
    @keydown.window.escape="show = false"
    x-show="show" x-cloak
    class="modal-wrapper"
>
    <div
        @click.away="show = false"
        class="modal-panel {{ $maxWidthClasses }}"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        {{-- Close button --}}
        <button @click="show = false" class="modal-close">&times;</button>

        {{-- Title slot --}}
        @isset($title)
            <h2 class="modal-title">{{ $title }}</h2>
        @endisset

        {{-- Body slot --}}
        <div class="modal-body">
            {{ $slot }}
        </div>

        {{-- Footer slot --}}
        @isset($footer)
            <div class="modal-footer">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
