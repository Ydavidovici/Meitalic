@extends('emails.layouts.email')

@section('subject', 'Return Request Received — #'.$order->id)

@section('content')
    <p>Hi {{ $order->user->name }},</p>
    <p>We’ve received your return request and will process it shortly.</p>

    <p>
        <a href="{{ route('order.show', $order) }}">View Return Status</a>
    </p>
@endsection
