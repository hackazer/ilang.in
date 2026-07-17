(function (root, factory) {
    var api = factory(root);
    if (typeof module === 'object' && module.exports) module.exports = api;
    root.FontSelector = api;
}(typeof window !== 'undefined' ? window : globalThis, function (root) {
    'use strict';

    var defaults = [
        'Arial', 'Georgia', 'Helvetica', 'Tahoma', 'Times+New+Roman', 'Verdana',
        'Inter', 'Lato', 'Montserrat', 'Nunito', 'Open+Sans', 'Oswald', 'Poppins',
        'Raleway', 'Roboto', 'Roboto+Slab', 'Source+Sans+3', 'Ubuntu'
    ];
    var weights = ['400', '500', '600', '700'];
    var sequence = 0;

    function parse(value) {
        var stored = String(value || '').trim();
        var parts = stored.split(':');
        return {
            value: stored,
            family: (parts[0] || '').replace(/\+/g, ' '),
            weight: parts[1] || '400'
        };
    }

    function format(family, weight) {
        var encoded = String(family || '').trim().replace(/\s+/g, '+');
        return String(weight || '400') === '400' ? encoded : encoded + ':' + weight;
    }

    function loadFont(selection) {
        if (!selection.family || !root.document || ['Arial', 'Georgia', 'Helvetica', 'Tahoma', 'Times New Roman', 'Verdana'].includes(selection.family)) return;
        var id = 'app-font-' + selection.family.toLowerCase().replace(/[^a-z0-9]+/g, '-');
        if (root.document.getElementById(id)) return;
        var link = root.document.createElement('link');
        link.id = id;
        link.rel = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(selection.family).replace(/%20/g, '+') + ':wght@' + selection.weight + '&display=swap';
        root.document.head.appendChild(link);
    }

    function init(target, options) {
        options = options || {};
        var inputs = typeof target === 'string' ? Array.from(root.document.querySelectorAll(target)) : [target && target.jquery ? target[0] : target];
        return inputs.filter(Boolean).map(function (input) {
            if (input.__fontSelector) return input.__fontSelector;
            var list = root.document.createElement('datalist');
            list.id = 'app-font-list-' + (++sequence);
            var values = (options.fonts || defaults).slice();
            if (input.value && !values.includes(input.value)) values.unshift(input.value);
            values.forEach(function (configured) {
                var selection = parse(configured);
                var configuredWeights = configured.includes(':') ? [selection.weight] : weights;
                configuredWeights.forEach(function (weight) {
                    var option = root.document.createElement('option');
                    option.value = format(selection.family, weight);
                    option.label = parse(option.value).family + (weight === '400' ? '' : ' ' + weight);
                    list.appendChild(option);
                });
            });
            input.parentNode.insertBefore(list, input.nextSibling);
            input.setAttribute('list', list.id);
            input.setAttribute('autocomplete', 'off');
            input.setAttribute('aria-autocomplete', 'list');

            function update() {
                var selection = parse(input.value);
                input.style.fontFamily = selection.family;
                input.style.fontWeight = selection.weight;
                loadFont(selection);
                if (typeof options.onChange === 'function') options.onChange.call(input, selection);
            }
            input.addEventListener('change', update);
            update();
            input.__fontSelector = { input: input, list: list, update: update };
            return input.__fontSelector;
        });
    }

    return { init: init, parse: parse, format: format };
}));
