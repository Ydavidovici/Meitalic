@extends('layouts.app')

@section('title', 'Contact Us')

@section('content')
    <div class="contact-container">
        <h2 class="page-heading">Contact Us</h2>

        @if (session('success'))
            <div class="alert--success">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST"
              action="{{ route('contact.submit') }}"
              enctype="multipart/form-data"
              class="form--stacked">
            @csrf

            <div class="form-field">
                <label for="name" class="form-label">Your Name</label>
                <input type="text"
                       id="name"
                       name="name"
                       required
                       class="form-input">
            </div>

            <div class="form-field">
                <label for="email" class="form-label">Your Email</label>
                <input type="email"
                       id="email"
                       name="email"
                       required
                       class="form-input">
            </div>

            <div class="form-field">
                <label for="photo" class="form-label">Upload Photo (optional)</label>
                <input type="file"
                       id="photo"
                       name="photo"
                       class="form-input">
            </div>

            <div class="form-field">
                <label for="message" class="form-label">Message</label>
                <textarea id="message"
                          name="message"
                          rows="4"
                          required
                          class="form-textarea"></textarea>
            </div>

            <button type="submit" class="btn-primary">Send Message</button>
        </form>
    </div>
@endsection
