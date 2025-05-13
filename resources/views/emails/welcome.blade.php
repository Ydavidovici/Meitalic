@extends('emails.layouts.email')

@section('subject', 'Welcome to '.config('app.name'))

@section('content')
    <p>Hi {{ $user->name }},</p>
    <p>Welcome aboard! We’re thrilled to have you with us.</p>
@endsection
