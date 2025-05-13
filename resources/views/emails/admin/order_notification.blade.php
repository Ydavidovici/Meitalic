@extends('emails.layouts.email')

@section('subject', 'New Order Placed — #'.$order->id)

@section('content')
    <p>A new order has been placed by <strong>{{ $order->user->name }}</strong> ({{ $order->user->email }}).</p>
    <p><strong>Order Total:</strong> ${{ number_format($order->total, 2) }}</p>
    <p>
        <a href="{{ route('admin.orders.show', $order) }}">View in Admin Panel</a>
    </p>
@endsection
