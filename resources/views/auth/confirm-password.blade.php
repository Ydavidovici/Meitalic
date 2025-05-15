<x-guest-layout>
    <div class="auth-page">

        <p class="auth-instructions">
            {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
        </p>

        <x-form
            method="POST"
            action="{{ route('password.confirm') }}"
            class="auth-form"
        >
            <!-- Password -->
            <div class="auth-form-group">
                <x-input-label for="password" :value="__('Password')" class="auth-label" />
                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="auth-input"
                />
                <x-input-error
                    :messages="$errors->get('password')"
                    class="auth-error"
                />
            </div>

            <div class="auth-submit-container">
                <x-primary-button class="auth-submit-btn">
                    {{ __('Confirm') }}
                </x-primary-button>
            </div>
        </x-form>

    </div>
</x-guest-layout>
