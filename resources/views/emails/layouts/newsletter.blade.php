@extends('emails.layouts.email')

@section('subject', $subject ?? config('app.name'))

@push('styles')
    <style>
        /* newsletter‑specific overrides */
        .newsletter-header {
            background: #222;
            color: #fff;
            padding: 40px 20px;
            text-align: center;
        }
        .newsletter-content {
            padding: 30px 20px;
        }
        .newsletter-footer {
            background: #f0f0f0;
            color: #555;
            padding: 20px;
            text-align: center;
            font-size: .9em;
        }
    </style>
@endpush

@section('content')
    {{--
      In your newsletter views, wrap the main area in these sections:
      -- <div class="newsletter-header">@yield('newsletter-header')</div>
      -- <div class="newsletter-content">@yield('newsletter-body')</div>
      -- <div class="newsletter-footer">@yield('newsletter-footer')</div>
    --}}
    @yield('newsletter-header')
    @yield('newsletter-body')
    @yield('newsletter-footer')
@endsection
