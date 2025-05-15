<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </div>

    <x-form
        method="POST"
        action="{{ route('password.confirm') }}"
        class="space-y-4"
    >
        <!-- Password -->
        <div class="form-group">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="current-password"
                class="form-input mt-1 w-full"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button class="btn-primary">
                {{ __('Confirm') }}
            </x-primary-button>
        </div>
    </x-form>
</x-guest-layout>
