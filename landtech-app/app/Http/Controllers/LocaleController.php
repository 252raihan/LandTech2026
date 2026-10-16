<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function switch(Request $request)
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys(config('app.available_locales', ['en'])))],
        ]);

        $request->session()->put('locale', $validated['locale']);

        if ($request->expectsJson()) {
            return response()->json([
                'locale' => $validated['locale'],
                'message' => __('app.locale_switched', ['locale' => $validated['locale']]),
            ]);
        }

        return back()->with('locale_switched', $validated['locale']);
    }

    public function script()
    {
        $strings = trans('js');
        if (! is_array($strings)) {
            $strings = [];
        }

        $script = sprintf(
            <<<'JS'
window.LandTech = window.LandTech || {};
window.LandTech.locale = %s;
window.LandTech.i18n = %s;
window.__t = function (key, replace) {
    var keys = key.split('.');
    var value = window.LandTech && window.LandTech.i18n ? window.LandTech.i18n : {};
    for (var i = 0; i < keys.length; i++) {
        if (value === null || typeof value !== 'object' || !Object.prototype.hasOwnProperty.call(value, keys[i])) {
            return key;
        }
        value = value[keys[i]];
    }
    if (typeof value !== 'string') {
        return key;
    }
    if (replace) {
        for (var k in replace) {
            if (Object.prototype.hasOwnProperty.call(replace, k)) {
                value = value.replace(new RegExp(':' + k, 'g'), replace[k]);
            }
        }
    }
    return value;
};
JS,
            var_export(app()->getLocale(), true),
            json_encode($strings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return response($script)
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Cache-Control', 'private, no-cache');
    }
}
