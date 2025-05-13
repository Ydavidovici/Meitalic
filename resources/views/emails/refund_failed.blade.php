@extends('emails.layouts.email')

@section('subject', 'Refund Failed — #'.$order->id)

@section('content')
    <p>Hi {{ $order->user->name }},</p>
    <p>We attempted to process your refund but it failed. Please contact our support team for assistance.</p>
@endsection
