@extends('emails.layouts.email')

@section('subject', 'Please Review Your Order — #'.$order->id)

@section('content')
    <p>Hi {{ $order->user->name }},</p>
    <p>Thanks again for your purchase! We’d love to hear your feedback.</p>

    <p>
        <a href="{{ route('dashboard') }}">Leave a Review</a>
    </p>
@endsection
