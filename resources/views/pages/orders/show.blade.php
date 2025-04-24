@extends('layouts.app')

@section('content')
    <h1>Order #{{ $order->id }}</h1>
    <p>Status: {{ $order->status }}</p>
    <p>Total: ${{ $order->total }}</p>
@endsection
