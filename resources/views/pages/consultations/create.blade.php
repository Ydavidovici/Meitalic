@extends('layouts.app')

@section('title', 'Upload Face Photo')

@section('content')
    <div class="max-w-xl mx-auto py-10 px-4">
        <h2 class="text-2xl font-bold mb-6">Face Consultation</h2>
        <form method="POST" action="{{ route('consultations.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block mb-1 font-medium">Upload a clear photo of your face</label>
                <input type="file" name="image" accept="image/*" required class="w-full border px-4 py-2 rounded">
            </div>
            <div>
                <label class="block mb-1 font-medium">Optional Notes</label>
                <textarea name="notes" rows="4" class="w-full border px-4 py-2 rounded"></textarea>
            </div>
            <button type="submit" class="bg-black text-white px-6 py-2 rounded hover:bg-gray-900">Submit</button>
        </form>
    </div>
@endsection
