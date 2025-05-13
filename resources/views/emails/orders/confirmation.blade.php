@extends('emails.layouts.email')

@section('subject', 'Order Confirmation — #'.$order->id)

@section('content')
    <p>Hi {{ $order->user->name }},</p>
    <p>Thanks for your order! We’re processing it now.</p>

    <p><strong>Order Details:</strong></p>
    <ul>
        @foreach($order->items as $item)
            <li>{{ $item->name }} × {{ $item->quantity }} — ${{ number_format($item->total, 2) }}</li>
        @endforeach
    </ul>

    <p><strong>Total:</strong> ${{ number_format($order->total, 2) }}</p>

    <p>
        <a href="{{ route('order.show', $order) }}">View Order</a>
    </p>
@endsection
