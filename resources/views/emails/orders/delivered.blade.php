@extends('emails.layouts.email')

@section('subject', 'Order Delivered — #'.$order->id)

@section('content')
    <p>Hi {{ $order->user->name }},</p>
    <p>We’re pleased to let you know your order has been delivered.</p>

    <p>
        <a href="{{ route('order.show', $order) }}">View Order</a>
    </p>
@endsection
