'use strict';

const assert = require('node:assert/strict');
const path = require('node:path');
const test = require('node:test');

const adapterPath = path.resolve(__dirname, '../../public/static/icon-picker.js');

class FakeClassList {
    constructor(element) {
        this.element = element;
    }

    add(...classes) {
        const values = new Set(this.element.className.split(/\s+/).filter(Boolean));
        classes.forEach((value) => values.add(value));
        this.element.className = Array.from(values).join(' ');
    }

    remove(...classes) {
        const removed = new Set(classes);
        this.element.className = this.element.className
            .split(/\s+/)
            .filter((value) => value && !removed.has(value))
            .join(' ');
    }

    contains(value) {
        return this.element.className.split(/\s+/).includes(value);
    }
}

class FakeElement {
    constructor(tagName, ownerDocument) {
        this.tagName = tagName.toUpperCase();
        this.ownerDocument = ownerDocument;
        this.parentNode = null;
        this.children = [];
        this.attributes = new Map();
        this.listeners = new Map();
        this.className = '';
        this.classList = new FakeClassList(this);
        this.hidden = false;
        this.textContent = '';
        this.value = '';
        this.id = '';
        this.type = '';
    }

    appendChild(child) {
        child.parentNode = this;
        this.children.push(child);
        return child;
    }

    insertBefore(child, reference) {
        child.parentNode = this;
        const index = this.children.indexOf(reference);
        if (index === -1) {
            this.children.push(child);
        } else {
            this.children.splice(index, 0, child);
        }
        return child;
    }

    setAttribute(name, value) {
        const text = String(value);
        this.attributes.set(name, text);
        if (name === 'id') this.id = text;
        if (name === 'class') this.className = text;
        if (name === 'type') this.type = text;
    }

    getAttribute(name) {
        return this.attributes.has(name) ? this.attributes.get(name) : null;
    }

    hasAttribute(name) {
        return this.attributes.has(name);
    }

    addEventListener(type, listener) {
        const listeners = this.listeners.get(type) || [];
        listeners.push(listener);
        this.listeners.set(type, listeners);
    }

    dispatchEvent(event) {
        if (!event.target) event.target = this;
        event.currentTarget = this;
        for (const listener of this.listeners.get(event.type) || []) {
            listener.call(this, event);
        }
        return !event.defaultPrevented;
    }

    focus() {
        this.ownerDocument.activeElement = this;
    }

    contains(element) {
        return element === this || this.children.some((child) => child.contains(element));
    }

    matches(selector) {
        if (selector.startsWith('.')) return this.classList.contains(selector.slice(1));
        const attribute = selector.match(/^\[([^=]+)="([^"]+)"\]$/);
        if (attribute) return this.getAttribute(attribute[1]) === attribute[2];
        const typed = selector.match(/^([a-z]+)\[type="([^"]+)"\]$/i);
        if (typed) return this.tagName === typed[1].toUpperCase() && this.type === typed[2];
        return this.tagName === selector.toUpperCase();
    }

    querySelectorAll(selector) {
        const matches = [];
        for (const child of this.children) {
            if (child.matches(selector)) matches.push(child);
            matches.push(...child.querySelectorAll(selector));
        }
        return matches;
    }

    querySelector(selector) {
        return this.querySelectorAll(selector)[0] || null;
    }
}

class FakeDocument extends FakeElement {
    constructor() {
        super('#document', null);
        this.ownerDocument = this;
        this.activeElement = null;
        this.styleSheets = [];
        this.body = this.createElement('body');
        this.appendChild(this.body);
    }

    createElement(tagName) {
        return new FakeElement(tagName, this);
    }

    getElementById(id) {
        return this.querySelectorAll('[id="' + id + '"]')[0] || null;
    }
}

class FakeEvent {
    constructor(type, options = {}) {
        this.type = type;
        this.key = options.key;
        this.target = options.target || null;
        this.defaultPrevented = false;
    }

    preventDefault() {
        this.defaultPrevented = true;
    }

    stopPropagation() {}
}

function loadAdapter() {
    const document = new FakeDocument();
    const window = { document, Event: FakeEvent };
    global.window = window;
    global.document = document;
    global.Event = FakeEvent;
    delete require.cache[adapterPath];
    const IconPicker = require(adapterPath);

    return { document, IconPicker };
}

function createInput(document, value = '') {
    const holder = document.createElement('div');
    const input = document.createElement('input');
    input.value = value;
    holder.appendChild(input);
    document.body.appendChild(holder);
    return input;
}

test.afterEach(() => {
    delete global.window;
    delete global.document;
    delete global.Event;
    delete require.cache[adapterPath];
});

test('normalizes legacy Font Awesome classes without changing icon identity', () => {
    const { IconPicker } = loadAdapter();

    assert.equal(IconPicker.normalizeValue('fas fa-user'), 'fa-solid fa-user');
    assert.equal(IconPicker.normalizeValue('far fa-address-card'), 'fa-regular fa-address-card');
    assert.equal(IconPicker.normalizeValue('fab fa-github'), 'fa-brands fa-github');
    assert.equal(IconPicker.normalizeValue('fa-solid fa-link'), 'fa-solid fa-link');
});

test('builds an accessible searchable picker and synchronizes selection', () => {
    const { document, IconPicker } = loadAdapter();
    const input = createInput(document, 'fas fa-user');
    let changes = 0;
    input.addEventListener('change', () => changes++);

    const picker = IconPicker.init(input, {
        icons: [
            { name: 'user', label: 'User', styles: ['solid', 'regular'] },
            { name: 'github', label: 'GitHub', styles: ['brands'] },
            { name: 'link', label: 'Link', styles: ['solid'] }
        ]
    });

    assert.equal(picker.trigger.getAttribute('aria-haspopup'), 'dialog');
    assert.equal(picker.trigger.getAttribute('aria-expanded'), 'false');
    assert.equal(picker.panel.getAttribute('role'), 'dialog');
    assert.equal(picker.grid.getAttribute('role'), 'listbox');
    assert.equal(picker.search.getAttribute('aria-label'), 'Search icons');

    picker.trigger.dispatchEvent(new FakeEvent('click'));
    assert.equal(picker.panel.hidden, false);
    assert.equal(picker.trigger.getAttribute('aria-expanded'), 'true');
    assert.equal(document.activeElement, picker.search);

    picker.search.value = 'github';
    picker.search.dispatchEvent(new FakeEvent('input'));
    const options = picker.grid.querySelectorAll('[role="option"]');
    assert.equal(options.length, 1);
    assert.equal(options[0].getAttribute('aria-label'), 'GitHub');

    options[0].dispatchEvent(new FakeEvent('click'));
    assert.equal(input.value, 'fa-brands fa-github');
    assert.equal(changes, 1);
    assert.equal(picker.panel.hidden, true);
    assert.equal(document.activeElement, picker.trigger);
});

test('supports keyboard selection and Escape without trapping focus', () => {
    const { document, IconPicker } = loadAdapter();
    const input = createInput(document);
    const picker = IconPicker.init(input, {
        icons: [
            { name: 'user', label: 'User', styles: ['solid'] },
            { name: 'link', label: 'Link', styles: ['solid'] }
        ]
    });

    picker.open();
    const options = picker.grid.querySelectorAll('[role="option"]');
    options[0].focus();
    options[0].dispatchEvent(new FakeEvent('keydown', { key: 'ArrowRight' }));
    assert.equal(document.activeElement, options[1]);

    options[1].dispatchEvent(new FakeEvent('keydown', { key: 'Enter' }));
    assert.equal(input.value, 'fa-solid fa-link');

    picker.open();
    picker.panel.dispatchEvent(new FakeEvent('keydown', { key: 'Escape' }));
    assert.equal(picker.panel.hidden, true);
    assert.equal(document.activeElement, picker.trigger);
});
