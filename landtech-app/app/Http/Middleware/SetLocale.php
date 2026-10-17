<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next): mixed
    {
        $locale = $request->session()->get('locale');

        if (! $this->isAvailable($locale)) {
            $locale = $request->cookie('locale');
        }

        if (! $this->isAvailable($locale)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }

    protected function isAvailable(mixed $locale): bool
    {
        return is_string($locale) && array_key_exists($locale, config('app.available_locales', []));
    }
}
