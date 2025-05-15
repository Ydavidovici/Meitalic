{{-- resources/views/emails/newsletter/standard.blade.php --}}
@extends('emails.layouts.newsletter')

@section('newsletter-header')
    <h1>{{ $header_text }}</h1>
@endsection

@section('newsletter-body')
    <p>{!! nl2br(e($body_text)) !!}</p>
@endsection

@section('newsletter-footer')
    @if(!empty($promo_code))
        <div class="promo">
            Use <strong>{{ $promo_code }}</strong> for an exclusive discount!
        </div>
    @endif
@endsection
