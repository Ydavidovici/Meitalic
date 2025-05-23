<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <x-form
        method="PUT"
        action="{{ route('password.update') }}"
        class="mt-6 space-y-6"
    >
        <div class="form-group">
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <input
                id="update_password_current_password"
                name="current_password"
                type="password"
                autocomplete="current-password"
                class="form-input"
            />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div class="form-group">
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <input
                id="update_password_password"
                name="password"
                type="password"
                autocomplete="new-password"
                class="form-input"
            />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div class="form-group">
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <input
                id="update_password_password_confirmation"
                name="password_confirmation"
                type="password"
                autocomplete="new-password"
                class="form-input"
            />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button class="btn-primary">{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >
                    {{ __('Saved.') }}
                </p>
            @endif
        </div>
    </x-form>
</section>
