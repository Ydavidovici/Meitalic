{{-- resources/views/emails/newsletter/with_image.blade.php --}}
@extends('emails.layouts.newsletter')

@section('newsletter-header')
    <h1>{{ $header_text }}</h1>
@endsection

@section('newsletter-body')
    @if($image_url)
        <img src="{{ $image_url }}" alt="" style="width:100%;margin-bottom:1rem;">
    @endif
    <p>{!! nl2br(e($body_text)) !!}</p>
    @if($cta_url && $cta_text)
        <p><a href="{{ $cta_url }}" class="button">{{ $cta_text }}</a></p>
    @endif
@endsection

@section('newsletter-footer')
    @if($promo_code)
        <div class="promo">
            Promo code: <strong>{{ $promo_code }}</strong>
        </div>
    @endif
@endsection
