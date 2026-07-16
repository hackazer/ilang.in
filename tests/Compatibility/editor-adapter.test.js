'use strict';

const assert = require('node:assert/strict');
const path = require('node:path');
const test = require('node:test');

const adapterPath = path.resolve(__dirname, '../../public/static/editor-adapter.js');

function loadAdapter() {
    const elements = new Map();
    const editors = [];

    class FakeForm {
        constructor() {
            this.listeners = {};
            this.elements = new Set();
        }

        addEventListener(name, listener) {
            this.listeners[name] = listener;
        }

        contains(element) {
            return this.elements.has(element);
        }

        submit() {
            this.listeners.submit({ target: this });
        }
    }

    function addTextarea(id, value, form) {
        const textarea = {
            id,
            value,
            closest(selector) {
                return selector === 'form' ? form : null;
            }
        };

        form.elements.add(textarea);
        elements.set(id, textarea);

        return textarea;
    }

    const document = {
        getElementById(id) {
            return elements.get(id) || null;
        },
        querySelector(selector) {
            return selector.startsWith('#') ? elements.get(selector.slice(1)) || null : null;
        }
    };

    const window = {
        document,
        Jodit: {
            make(element, options) {
                const editor = {
                    element,
                    options,
                    value: element.value,
                    destructed: false,
                    destruct() {
                        this.destructed = true;
                    }
                };

                editors.push(editor);

                return editor;
            }
        }
    };

    global.window = window;
    global.document = document;
    global.Jodit = window.Jodit;
    delete require.cache[adapterPath];
    require(adapterPath);

    return {
        EditorAdapter: window.EditorAdapter,
        FakeForm,
        addTextarea,
        editors
    };
}

test.afterEach(() => {
    delete global.window;
    delete global.document;
    delete global.Jodit;
    delete require.cache[adapterPath];
});

test('creates one Jodit instance with required rich text features and preserves HTML', () => {
    const { EditorAdapter, FakeForm, addTextarea, editors } = loadAdapter();
    const form = new FakeForm();
    addTextarea('editor', '<section><table><tr><td>Saved</td></tr></table></section>', form);

    const editor = EditorAdapter.create('editor');

    assert.equal(editor, editors[0]);
    assert.equal(EditorAdapter.value('editor'), '<section><table><tr><td>Saved</td></tr></table></section>');
    for (const feature of ['source', 'table', 'link', 'image']) {
        assert.ok(editors[0].options.buttons.includes(feature), `missing ${feature} button`);
    }
});

test('recreates a dynamic editor after synchronizing and destroying the prior instance', () => {
    const { EditorAdapter, FakeForm, addTextarea, editors } = loadAdapter();
    const form = new FakeForm();
    const textarea = addTextarea('card_editor', '<p>Initial</p>', form);
    const first = EditorAdapter.create('card_editor', { height: 100 });
    first.value = '<p>Changed before reorder</p>';

    const second = EditorAdapter.recreate('card_editor', { height: 100 });

    assert.equal(first.destructed, true);
    assert.equal(textarea.value, '<p>Changed before reorder</p>');
    assert.equal(second.value, '<p>Changed before reorder</p>');
    assert.equal(editors.length, 2);
    assert.equal(second.options.height, 100);
});

test('destroy synchronizes the textarea and removes the instance', () => {
    const { EditorAdapter, FakeForm, addTextarea } = loadAdapter();
    const form = new FakeForm();
    const textarea = addTextarea('editor', '<p>Initial</p>', form);
    const editor = EditorAdapter.create('editor');
    editor.value = '<p>Final</p>';

    assert.equal(EditorAdapter.destroy('editor'), true);
    assert.equal(textarea.value, '<p>Final</p>');
    assert.equal(editor.destructed, true);
    assert.equal(EditorAdapter.get('editor'), null);
});

test('synchronizes every editor in a form before native and AJAX submission', () => {
    const { EditorAdapter, FakeForm, addTextarea } = loadAdapter();
    const firstForm = new FakeForm();
    const secondForm = new FakeForm();
    const firstTextarea = addTextarea('first', '<p>Old first</p>', firstForm);
    const secondTextarea = addTextarea('second', '<p>Old second</p>', secondForm);
    const firstEditor = EditorAdapter.create('first');
    const secondEditor = EditorAdapter.create('second');
    firstEditor.value = '<p>Native submit value</p>';
    secondEditor.value = '<p>AJAX submit value</p>';

    firstForm.submit();
    assert.equal(firstTextarea.value, '<p>Native submit value</p>');
    assert.equal(secondTextarea.value, '<p>Old second</p>');

    EditorAdapter.syncForm(secondForm);
    assert.equal(secondTextarea.value, '<p>AJAX submit value</p>');
});
