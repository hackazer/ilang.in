(function (root, factory) {
    var api = factory(root);
    if (typeof module === 'object' && module.exports) module.exports = api;
    root.AppColorPicker = api;
}(typeof window !== 'undefined' ? window : globalThis, function (root) {
    'use strict';

    var sequence = 0;
    var initialized = false;

    function clamp(value, maximum) {
        return Math.min(maximum, Math.max(0, Number(value) || 0));
    }

    function parse(value) {
        var source = String(value == null ? '' : value).trim();
        if (!source) return null;

        var hex = source.match(/^#([\da-f]{3,8})$/i);
        if (hex) {
            var digits = hex[1];
            if (digits.length === 3 || digits.length === 4) {
                digits = digits.split('').map(function (digit) { return digit + digit; }).join('');
            }
            if (digits.length === 6 || digits.length === 8) {
                return {
                    r: parseInt(digits.slice(0, 2), 16),
                    g: parseInt(digits.slice(2, 4), 16),
                    b: parseInt(digits.slice(4, 6), 16),
                    a: digits.length === 8 ? parseInt(digits.slice(6, 8), 16) / 255 : 1
                };
            }
        }

        var rgb = source.match(/^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)(?:\s*,\s*([\d.]+))?\s*\)$/i);
        if (rgb) {
            return {
                r: clamp(rgb[1], 255),
                g: clamp(rgb[2], 255),
                b: clamp(rgb[3], 255),
                a: rgb[4] == null ? 1 : clamp(rgb[4], 1)
            };
        }

        return null;
    }

    function hexByte(value) {
        return Math.round(value).toString(16).padStart(2, '0');
    }

    function alpha(value) {
        return String(Math.round(value * 1000) / 1000);
    }

    function color(value) {
        var original = String(value == null ? '' : value).trim();
        var parsed = parse(original);
        return {
            toHexString: function () {
                if (!parsed) return original;
                return '#' + hexByte(parsed.r) + hexByte(parsed.g) + hexByte(parsed.b) + (parsed.a < 1 ? hexByte(parsed.a * 255) : '');
            },
            toRgbString: function () {
                if (!parsed) return original;
                var channels = Math.round(parsed.r) + ', ' + Math.round(parsed.g) + ', ' + Math.round(parsed.b);
                return parsed.a < 1 ? 'rgba(' + channels + ', ' + alpha(parsed.a) + ')' : 'rgb(' + channels + ')';
            },
            toString: function () { return original; }
        };
    }

    function normalize(value, format, allowEmpty) {
        if (allowEmpty && String(value == null ? '' : value).trim() === '') return '';
        var parsedColor = color(value);
        return format === 'rgb' ? parsedColor.toRgbString() : parsedColor.toHexString();
    }

    function elements(target) {
        if (!target) return [];
        if (typeof target === 'string') return Array.from(root.document.querySelectorAll(target));
        if (target.jquery) return target.toArray();
        if (target.nodeType === 1) return [target];
        return Array.from(target);
    }

    function configureColoris() {
        if (initialized || typeof root.Coloris !== 'function') return;
        root.Coloris({
            themeMode: 'auto',
            alpha: false,
            clearButton: true,
            closeButton: true
        });
        initialized = true;
    }

    function init(target, options) {
        options = options || {};
        configureColoris();

        return elements(target).map(function (input) {
            if (input.__appColorPicker) {
                Object.assign(input.__appColorPicker.options, options);
                if (options.color != null) input.value = normalize(options.color, input.__appColorPicker.options.preferredFormat, input.__appColorPicker.options.allowEmpty);
                return input.__appColorPicker;
            }
            var settings = Object.assign({}, options);
            if (!input.id) input.id = 'app-color-picker-' + (++sequence);
            input.setAttribute('data-coloris', '');
            input.setAttribute('autocomplete', 'off');
            if (settings.allowEmpty) input.setAttribute('data-coloris-clear', 'true');
            if (settings.color != null) input.value = normalize(settings.color, settings.preferredFormat, settings.allowEmpty);

            if (root.Coloris && typeof root.Coloris.setInstance === 'function') {
                root.Coloris.setInstance('#' + input.id, {
                    format: settings.preferredFormat === 'rgb' ? 'rgb' : 'hex',
                    alpha: settings.alpha === true || settings.showAlpha === true,
                    clearButton: Boolean(settings.allowEmpty),
                    closeButton: true
                });
            }

            function emit(callback) {
                var next = normalize(input.value, settings.preferredFormat, settings.allowEmpty);
                if (next !== input.value) input.value = next;
                if (typeof callback === 'function') callback.call(input, color(next));
            }

            input.addEventListener('input', function () { emit(settings.move); });
            input.addEventListener('change', function () { emit(settings.hide); });
            input.__appColorPicker = { input: input, options: settings, destroy: function () { delete input.__appColorPicker; } };
            return input.__appColorPicker;
        });
    }

    var api = { init: init, color: color, normalize: normalize };
    if (root.jQuery && root.jQuery.fn) {
        root.jQuery.fn.appColorPicker = function (options) {
            init(this, options);
            return this;
        };
    }
    return api;
}));
