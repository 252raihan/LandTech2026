{{--
    Inline locale bootstrap.

    Defines window.LandTech.i18n, window.__t, window.__tRaw, window.__esc and
    window.__tHtml BEFORE any page module executes, so client-side code can
    translate without a second network request and without depending on script
    ordering.

    The dictionary is emitted with JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS |
    JSON_HEX_QUOT, so no value can terminate the <script> element or inject
    markup — the payload is safe to inline even though it is dynamic.
--}}
@php
    $landTechStrings = trans('js');
    if (! is_array($landTechStrings)) {
        $landTechStrings = [];
    }
@endphp
<script>
window.LandTech = window.LandTech || {};
window.LandTech.locale = @json(app()->getLocale());
window.LandTech.i18n = @json($landTechStrings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

/** Raw dot-notation lookup. Returns null when the key is missing. */
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
 * Translate a dot-notation key.
 * Returns the KEY ITSELF when missing, so gaps are visible rather than blank.
 */
window.__t = function (key, replacements) {
    var value = window.__tRaw(key);
    if (typeof value !== 'string') return key;
    return window.__tInterpolate(value, replacements);
};

/** HTML-escape a value before inserting it into markup. */
window.__esc = function (value) {
    if (value === null || value === undefined) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
};

/** Translate AND escape every replacement — use this for innerHTML. */
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
</script>
