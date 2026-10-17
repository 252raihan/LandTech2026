<form method="POST" action="{{ route('locale.switch') }}" class="flex items-center gap-1.5" aria-label="{{ __('app.switch_language') }}">
    @csrf
    <label for="locale-select" class="text-xs text-slate-500">{{ __('app.language') }}</label>
    <select id="locale-select" name="locale" onchange="this.form.submit()"
            class="text-xs border border-slate-300 rounded px-1.5 py-1 bg-white text-slate-700">
        @foreach (config('app.available_locales', []) as $code => $label)
            <option value="{{ $code }}" @selected(app()->getLocale() === $code)
                    aria-current="{{ app()->getLocale() === $code ? 'true' : 'false' }}">{{ $label }}</option>
        @endforeach
    </select>
    <noscript><button type="submit" class="text-xs underline text-slate-600">{{ __('app.switch_language') }}</button></noscript>
</form>
