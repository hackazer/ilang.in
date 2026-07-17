(function (root, factory) {
    var api = factory(root);
    if (typeof module === 'object' && module.exports) module.exports = api;
    root.AppMask = api;
}(typeof window !== 'undefined' ? window : globalThis, function (root) {
    'use strict';

    function pattern(value) { return String(value || ''); }

    function init(input, options) {
        options = options || {};
        if (!input || input.__appMask || typeof root.IMask !== 'function') return input && input.__appMask;
        var instance = root.IMask(input, {
            mask: pattern(options.mask || input.getAttribute('data-mask')),
            lazy: options.lazy !== false
        });
        input.__appMask = instance;
        return instance;
    }

    function initAll(scope) {
        if (!root.document) return [];
        return Array.from((scope || root.document).querySelectorAll('[data-mask]')).map(function (input) { return init(input); });
    }

    if (root.document) {
        if (root.document.readyState === 'loading') root.document.addEventListener('DOMContentLoaded', function () { initAll(); });
        else initAll();
    }

    return { init: init, initAll: initAll, pattern: pattern };
}));
