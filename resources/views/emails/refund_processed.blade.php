@extends('emails.layouts.email')

@section('subject', 'Refund Completed — #'.$order->id)

@section('content')
    <p>Hi {{ $order->user->name }},</p>
    <p>Your refund has been processed. Funds should appear in 3–5 business days.</p>
@endsection
