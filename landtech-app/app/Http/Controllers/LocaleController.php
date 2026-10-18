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

        cookie()->queue(cookie()->forever('locale', $validated['locale']));

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

/**
 * Look up a dot-notation translation key.
 *
 * Returns the KEY ITSELF when a translation is missing, which makes gaps
 * obvious in the UI and in tests rather than silently rendering blank.
 *
 * @param {string} key          e.g. 'risk-scoring.band_high'
 * @param {object} [replacements] e.g. { count: 3 } replaces ':count'
 * @returns {string}
 */
window.__t = function (key, replacements) {
    var value = window.__tRaw(key);
    if (typeof value !== 'string') return key;
    return window.__tInterpolate(value, replacements);
};

/** Raw lookup without interpolation. Returns null when missing. */
window.__tRaw = function (key) {
    if (typeof key !== 'string' || key === '') return null;

    var dict = window.LandTech && window.LandTech.i18n ? window.LandTech.i18n : {};

    function lookup(k) {
        var parts = k.split('.');
        var node = dict;
        for (var i = 0; i < parts.length; i++) {
            if (node === null || typeof node !== 'object' ||
                !Object.prototype.hasOwnProperty.call(node, parts[i])) {
                return null;
            }
            node = node[parts[i]];
        }
        return typeof node === 'string' ? node : null;
    }

    // The emitted dictionary is rooted at the page-group level
    // ('dashboard.x'), but views also translate keys written for Blade
    // ('pages.dashboard.x'). Accept both spellings.
    var value = lookup(key);
    if (value === null && key.indexOf('pages.') === 0) {
        value = lookup(key.slice(6));
    }
    return value;
};

/** Substitute :placeholders. Values are coerced to string, never evaluated. */
window.__tInterpolate = function (value, replacements) {
    if (!replacements) return value;
    for (var k in replacements) {
        if (Object.prototype.hasOwnProperty.call(replacements, k)) {
            var v = replacements[k] === null || replacements[k] === undefined
                ? '' : String(replacements[k]);
            value = value.split(':' + k).join(v);
        }
    }
    return value;
};

/**
 * HTML-escape a value before interpolating it into markup.
 *
 * SECURITY: any translated string or dynamic value that is concatenated into
 * innerHTML MUST pass through here (or __tHtml). Translations are trusted
 * content, but :replacements may carry user/OCR/record data — escaping them
 * prevents XSS through template injection.
 */
window.__esc = function (value) {
    if (value === null || value === undefined) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
};

/**
 * Translate AND escape every interpolated replacement.
 *
 * Use this instead of __t() whenever the result is assigned to innerHTML:
 *
 *     el.innerHTML = __tHtml('risk-scoring.report_title', { plotNo: p.plotNo });
 *
 * The translation template itself is trusted (it ships in lang/*.php), but the
 * replacement values are escaped, so record data can never inject markup.
 */
window.__tHtml = function (key, replacements) {
    var safe = null;
    if (replacements) {
        safe = {};
        for (var k in replacements) {
            if (Object.prototype.hasOwnProperty.call(replacements, k)) {
                safe[k] = window.__esc(replacements[k]);
            }
        }
    }
    return window.__t(key, safe);
};
JS,
            var_export(app()->getLocale(), true),
            json_encode($strings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
        );

        return response($script)
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Cache-Control', 'private, no-cache');
    }
}
