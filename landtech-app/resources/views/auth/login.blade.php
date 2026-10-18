<x-guest-layout>
    <div class="card p-6 sm:p-8">
        <div class="mb-6">
            <h1 class="text-xl font-bold text-navy-900">{{ __('Log in') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('pages.layout.app_tagline') }}</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="mt-4">
                <x-input-label for="password" :value="__('Password')" />

                <x-text-input id="password" class="mt-1 block w-full"
                                type="password"
                                name="password"
                                required autocomplete="current-password" />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Remember Me -->
            <div class="mt-4 block">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500" name="remember">
                    <span class="ms-2 text-sm text-slate-600">{{ __('Remember me') }}</span>
                </label>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                @if (Route::has('password.request'))
                    <a class="rounded-md text-sm font-medium text-brand-700 underline transition hover:text-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif

                <x-primary-button>
                    {{ __('Log in') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
