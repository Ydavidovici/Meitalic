@extends('emails.layouts.email')

@section('subject', 'Order Cancelled — #'.$order->id)

@section('content')
    <p>Hi {{ $order->user->name }},</p>
    <p>Your order has been cancelled. If you have any questions, simply reply to this email.</p>
@endsection
