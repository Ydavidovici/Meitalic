@extends('layouts.app')

@section('content')
    <h1>Order #{{ $order->id }}</h1>
    <p>Status: {{ ucfirst($order->status) }}</p>
    <p>Total: ${{ number_format($order->total, 2) }}</p>

    {{-- Show “Request Return” button if not already in process or completed --}}
    @if(! in_array($order->status, ['pending_return','returned']))
        <x-form
            action="{{ route('order.return', $order) }}"
            class="mt-4"
        >
            <button type="submit" class="btn-danger">
                Request Return
            </button>
        </x-form>
    @endif
@endsection
