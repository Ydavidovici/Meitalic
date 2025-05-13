@extends('emails.layouts.email')

@section('subject', 'Order Shipped — #'.$order->id)

@section('content')
    <p>Hi {{ $order->user->name }},</p>
    <p>Good news—your order is on its way!</p>

    <p>
        <a href="{{ route('order.show', $order) }}">Track Your Order</a>
    </p>
@endsection
