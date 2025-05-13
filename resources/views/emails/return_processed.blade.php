@extends('emails.layouts.email')

@section('subject', 'Return Processed — #'.$order->id)

@section('content')
    <p>Hi {{ $order->user->name }},</p>
    <p>Your return has been approved and processed. You’ll see the credit on your original payment method soon.</p>
@endsection
