@extends('layouts.app')

@section('title', 'Upload Face Photo')

@section('content')
    <div class="max-w-xl mx-auto py-10 px-4">
        <h2 class="text-2xl font-bold mb-6">Face Consultation</h2>

        <x-form
            method="POST"
            action="{{ route('consultations.store') }}"
            enctype="multipart/form-data"
            class="space-y-4"
        >
            <div class="form-group">
                <label class="block mb-1 font-medium" for="image">
                    Upload a clear photo of your face
                </label>
                <input
                    id="image"
                    type="file"
                    name="image"
                    accept="image/*"
                    required
                    class="form-input w-full"
                />
            </div>

            <div class="form-group">
                <label class="block mb-1 font-medium" for="notes">
                    Optional Notes
                </label>
                <textarea
                    id="notes"
                    name="notes"
                    rows="4"
                    class="form-textarea w-full"
                ></textarea>
            </div>

            <button type="submit" class="btn-primary">
                Submit
            </button>
        </x-form>
    </div>
@endsection
