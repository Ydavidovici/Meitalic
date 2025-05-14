@extends('layouts.app')

@push('scripts')
    @vite('resources/js/admin-dashboard.js')
@endpush


@section('title', 'Edit Product')

@section('content')
    <div class="py-12 container px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold mb-6">Edit Product: {{ $product->name }}</h1>

        <form
            method="POST"
            action="{{ route('admin.products.update', $product) }}"
            enctype="multipart/form-data"
            class="bg-white p-6 rounded-lg shadow space-y-6"
        >
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Name --}}
                <div>
                    <x-input-label for="name" value="Name"/>
                    <x-text-input
                        id="name"
                        name="name"
                        value="{{ old('name',$product->name) }}"
                        class="mt-1 block w-full"
                    />
                    <x-input-error :messages="$errors->get('name')" class="mt-1"/>
                </div>

                {{-- Brand --}}
                <div>
                    <x-input-label for="brand" value="Brand"/>
                    <x-text-input
                        id="brand"
                        name="brand"
                        value="{{ old('brand',$product->brand) }}"
                        class="mt-1 block w-full"
                    />
                    <x-input-error :messages="$errors->get('brand')" class="mt-1"/>
                </div>

                {{-- Category --}}
                <div>
                    <x-input-label for="category" value="Category"/>
                    <x-text-input
                        id="category"
                        name="category"
                        value="{{ old('category',$product->category) }}"
                        class="mt-1 block w-full"
                    />
                    <x-input-error :messages="$errors->get('category')" class="mt-1"/>
                </div>

                {{-- Price --}}
                <div>
                    <x-input-label for="price" value="Price"/>
                    <x-text-input
                        id="price"
                        name="price"
                        type="number"
                        step="0.01"
                        value="{{ old('price',$product->price) }}"
                        class="mt-1 block w-full"
                    />
                    <x-input-error :messages="$errors->get('price')" class="mt-1"/>
                </div>

                {{-- Inventory --}}
                <div>
                    <x-input-label for="inventory" value="Inventory"/>
                    <x-text-input
                        id="inventory"
                        name="inventory"
                        type="number"
                        value="{{ old('inventory',$product->inventory) }}"
                        class="mt-1 block w-full"
                    />
                    <x-input-error :messages="$errors->get('inventory')" class="mt-1"/>
                </div>

                {{-- Description --}}
                <div class="md:col-span-2">
                    <x-input-label for="description" value="Description"/>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        class="mt-1 block w-full border rounded p-2"
                    >{{ old('description',$product->description) }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-1"/>
                </div>

                {{-- Image upload --}}
                <div>
                    <x-input-label for="image" value="Product Image"/>
                    @if($product->image)
                        <img
                            src="{{ asset('storage/'.$product->image) }}"
                            alt="Current image"
                            class="w-32 h-32 object-cover rounded mb-2"
                        >
                    @endif
                    <input
                        id="image"
                        name="image"
                        type="file"
                        accept="image/*"
                        class="mt-1 block w-full"
                    />
                    <x-input-error :messages="$errors->get('image')" class="mt-1"/>
                </div>
            </div>

            <div class="flex justify-end space-x-4">
                <x-secondary-button
                    type="button"
                    @click="$dispatch('close-modal','product-edit-{{ $product->id }}')"
                >
                    Cancel
                </x-secondary-button>
                <x-primary-button>Save Changes</x-primary-button>
            </div>
        </form>
    </div>
@endsection
