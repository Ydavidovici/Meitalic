@extends('layouts.app')

@section('content')
    <h1>Order #{{ $order->id }}</h1>
    <p>Status: {{ ucfirst($order->status) }}</p>
    <p>Total: ${{ number_format($order->total,2) }}</p>

    {{-- Show “Request Return” button if not already in process or completed --}}
    @if(! in_array($order->status, ['pending_return','returned']))
        <form action="{{ route('order.return', $order) }}" method="POST" class="mt-4">
            @csrf
            <button type="submit"
                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                Request Return
            </button>
        </form>
    @endif
@endsection
