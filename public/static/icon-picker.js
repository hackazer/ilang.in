(function (root, factory) {
    var api = factory(root);
    if (typeof module === 'object' && module.exports) module.exports = api;
    root.IconPicker = api;
}(typeof window !== 'undefined' ? window : globalThis, function (root) {
    'use strict';

    var sequence = 0;
    var catalogs = new Map();
    var styleClasses = { solid: 'fa-solid', regular: 'fa-regular', brands: 'fa-brands' };

    function normalizeValue(value) {
        return String(value || '').trim()
            .replace(/(^|\s)fas(?=\s|$)/, '$1fa-solid')
            .replace(/(^|\s)far(?=\s|$)/, '$1fa-regular')
            .replace(/(^|\s)fab(?=\s|$)/, '$1fa-brands')
            .replace(/\s+/g, ' ');
    }

    function catalogEntries(payload) {
        var icons = payload && payload.icons ? payload.icons : payload || {};
        if (Array.isArray(icons)) return icons;
        return Object.keys(icons).map(function (name) {
            return Object.assign({ name: name }, icons[name]);
        });
    }

    function loadCatalog(url) {
        if (!catalogs.has(url)) {
            catalogs.set(url, root.fetch(url, { credentials: 'same-origin' }).then(function (response) {
                if (!response.ok) throw new Error('Unable to load icon catalog');
                return response.json();
            }).then(catalogEntries));
        }
        return catalogs.get(url);
    }

    function create(document, tag, className, attributes) {
        var element = document.createElement(tag);
        if (className) element.className = className;
        Object.keys(attributes || {}).forEach(function (name) { element.setAttribute(name, attributes[name]); });
        return element;
    }

    function init(input, options) {
        options = options || {};
        if (typeof input === 'string') input = root.document.querySelector(input);
        if (input && input.jquery) input = input[0];
        if (!input) return null;
        if (input.__iconPicker) return input.__iconPicker;

        var document = input.ownerDocument;
        var id = 'app-icon-picker-' + (++sequence);
        var wrapper = create(document, 'div', 'app-icon-picker');
        var trigger = create(document, 'button', 'app-icon-picker__trigger', {
            type: 'button', 'aria-haspopup': 'dialog', 'aria-expanded': 'false', 'aria-controls': id
        });
        var preview = create(document, 'i', normalizeValue(input.value) || 'fa-solid fa-icons', { 'aria-hidden': 'true' });
        var triggerText = create(document, 'span', 'visually-hidden');
        triggerText.textContent = options.chooseLabel || 'Choose icon';
        trigger.appendChild(preview);
        trigger.appendChild(triggerText);
        var panel = create(document, 'div', 'app-icon-picker__panel', { id: id, role: 'dialog', 'aria-label': options.dialogLabel || 'Choose icon' });
        panel.hidden = true;
        var search = create(document, 'input', 'app-icon-picker__search form-control', { type: 'search', 'aria-label': options.searchLabel || 'Search icons', placeholder: options.searchLabel || 'Search icons' });
        var status = create(document, 'div', 'app-icon-picker__status', { role: 'status', 'aria-live': 'polite' });
        var grid = create(document, 'div', 'app-icon-picker__grid', { role: 'listbox' });
        panel.appendChild(search);
        panel.appendChild(status);
        panel.appendChild(grid);
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        wrapper.appendChild(trigger);
        wrapper.appendChild(panel);

        var icons = catalogEntries(options.icons || []);

        function close() {
            panel.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            trigger.focus();
        }

        function select(value) {
            input.value = normalizeValue(value);
            preview.className = input.value;
            var EventConstructor = root.Event || Event;
            input.dispatchEvent(new EventConstructor('change', { bubbles: true }));
            close();
        }

        function render() {
            var query = search.value.trim().toLowerCase();
            if (Array.isArray(grid.children)) grid.children.length = 0;
            else while (grid.firstChild) grid.removeChild(grid.firstChild);
            grid.textContent = '';
            var count = 0;
            icons.forEach(function (icon) {
                var terms = [icon.name, icon.label].concat(icon.searchTerms || []).join(' ').toLowerCase();
                if (query && !terms.includes(query)) return;
                (icon.styles || ['solid']).forEach(function (style) {
                    var styleClass = styleClasses[style];
                    if (!styleClass) return;
                    var button = create(document, 'button', 'app-icon-picker__option', {
                        type: 'button', role: 'option', 'aria-label': icon.label || icon.name,
                        'aria-selected': normalizeValue(input.value) === styleClass + ' fa-' + icon.name ? 'true' : 'false'
                    });
                    var glyph = create(document, 'i', styleClass + ' fa-' + icon.name, { 'aria-hidden': 'true' });
                    button.appendChild(glyph);
                    button.addEventListener('click', function () { select(styleClass + ' fa-' + icon.name); });
                    button.addEventListener('keydown', function (event) {
                        var options = grid.querySelectorAll('[role="option"]');
                        var index = Array.from(options).indexOf(button);
                        if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
                            event.preventDefault(); options[Math.min(index + 1, options.length - 1)].focus();
                        } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                            event.preventDefault(); options[Math.max(index - 1, 0)].focus();
                        } else if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault(); select(styleClass + ' fa-' + icon.name);
                        }
                    });
                    grid.appendChild(button);
                    count++;
                });
            });
            status.textContent = count ? count + ' icons' : 'No icons found';
        }

        function open() {
            panel.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            search.focus();
            if (!icons.length && options.catalogUrl) {
                status.textContent = options.loadingLabel || 'Loading icons';
                loadCatalog(options.catalogUrl).then(function (loaded) { icons = loaded; render(); }).catch(function () { status.textContent = options.errorLabel || 'Unable to load icons'; });
            } else render();
        }

        input.value = normalizeValue(input.value);
        trigger.addEventListener('click', function () { panel.hidden ? open() : close(); });
        search.addEventListener('input', render);
        panel.addEventListener('keydown', function (event) { if (event.key === 'Escape') { event.preventDefault(); close(); } });
        var picker = { input: input, trigger: trigger, panel: panel, grid: grid, search: search, open: open, close: close };
        input.__iconPicker = picker;
        return picker;
    }

    return { init: init, normalizeValue: normalizeValue, catalogEntries: catalogEntries };
}));
