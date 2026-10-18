<x-guest-layout>
    <div class="card p-6 sm:p-8">
        <div class="mb-6">
            <h1 class="text-xl font-bold text-navy-900">{{ __('Verify Email') }}</h1>
            <p class="mt-1 text-sm leading-relaxed text-slate-500">
                {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
            </p>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success mb-4">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </div>
        @endif

        <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf

                <x-primary-button>
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit" class="rounded-md text-sm font-medium text-brand-700 underline transition hover:text-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    {{ __('Log Out') }}
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
