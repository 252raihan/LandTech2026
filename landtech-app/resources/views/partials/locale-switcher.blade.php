{{--
    Language switcher.

    Kept as a native <select> inside a POST form: it is keyboard accessible by
    default, works without JavaScript (the <noscript> submit button covers that)
    and the LocaleSwitchingTest asserts that #locale-select exists.
--}}
<form method="POST" action="{{ route('locale.switch') }}" class="flex items-center gap-2">
    @csrf
    <label for="locale-select" class="flex items-center gap-1.5 text-xs font-semibold text-slate-500">
        <x-icon name="language" class="hidden h-4 w-4 sm:block" />
        <span class="hidden sm:inline">{{ __('pages.layout.language') }}</span>
        <span class="sr-only sm:hidden">{{ __('pages.layout.language') }}</span>
    </label>
    <select id="locale-select" name="locale" onchange="this.form.submit()"
            class="input !w-auto !px-2.5 !py-1.5 text-xs font-medium"
            aria-label="{{ __('pages.layout.switch_language') }}">
        @foreach (config('app.available_locales', []) as $code => $label)
            <option value="{{ $code }}" lang="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $label }}</option>
        @endforeach
    </select>
    <noscript>
        <button type="submit" class="btn-primary btn-sm">{{ __('pages.layout.switch_language') }}</button>
    </noscript>
</form>
