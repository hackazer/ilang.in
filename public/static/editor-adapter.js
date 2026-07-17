(function (global) {
    'use strict';

    var records = [];
    var boundForms = [];
    var requiredButtons = [
        'source', '|',
        'bold', 'italic', 'underline', 'strikethrough', '|',
        'ul', 'ol', '|',
        'font', 'fontsize', 'brush', 'paragraph', '|',
        'image', 'link', 'table', '|',
        'align', 'undo', 'redo', 'hr', 'eraser', 'fullsize'
    ];
    var compactButtons = [
        'bold', 'italic', 'underline', '|',
        'ul', 'ol', '|',
        'image', 'link', 'table', 'source', 'fullsize'
    ];
    var defaultOptions = {
        buttons: requiredButtons,
        buttonsMD: requiredButtons,
        buttonsSM: compactButtons,
        buttonsXS: compactButtons,
        minHeight: 100,
        sourceEditor: 'area',
        askBeforePasteHTML: false,
        askBeforePasteFromWord: false,
        defaultActionOnPaste: 'insert_as_html',
        cleanHTML: {
            allowTags: false,
            denyTags: false,
            fillEmptyParagraph: false,
            removeEmptyElements: false
        },
        uploader: {
            insertImageAsBase64URI: true
        }
    };

    function copyObject(source) {
        var result = {};
        var key;

        for (key in source) {
            if (Object.prototype.hasOwnProperty.call(source, key)) {
                result[key] = source[key];
            }
        }

        return result;
    }

    function optionsWithDefaults(options) {
        var merged = copyObject(defaultOptions);
        var key;

        merged.buttons = requiredButtons.slice();
        merged.buttonsMD = requiredButtons.slice();
        merged.buttonsSM = compactButtons.slice();
        merged.buttonsXS = compactButtons.slice();
        merged.cleanHTML = copyObject(defaultOptions.cleanHTML);
        merged.uploader = copyObject(defaultOptions.uploader);

        if (!options) {
            return merged;
        }

        for (key in options) {
            if (Object.prototype.hasOwnProperty.call(options, key)) {
                if (key === 'cleanHTML' || key === 'uploader') {
                    merged[key] = copyObject(merged[key]);
                    Object.assign(merged[key], options[key]);
                } else {
                    merged[key] = options[key];
                }
            }
        }

        return merged;
    }

    function resolveElement(target) {
        if (typeof target !== 'string') {
            return target || null;
        }

        return global.document.getElementById(target) || global.document.querySelector(target);
    }

    function findRecord(target) {
        var element = resolveElement(target);
        var index;

        for (index = 0; index < records.length; index += 1) {
            if (records[index].element === element) {
                return records[index];
            }
        }

        return null;
    }

    function synchronize(record) {
        if (!record) {
            return false;
        }

        if (typeof record.editor.synchronizeValues === 'function') {
            record.editor.synchronizeValues();
        }

        record.element.value = record.editor.value;

        return true;
    }

    function bindForm(form) {
        if (!form || boundForms.indexOf(form) !== -1) {
            return;
        }

        form.addEventListener('submit', function () {
            EditorAdapter.syncForm(form);
        }, true);
        boundForms.push(form);
    }

    var EditorAdapter = {
        create: function (target, options) {
            var element = resolveElement(target);
            var existing;
            var editor;
            var record;
            var form;

            if (!element) {
                throw new Error('Editor target was not found.');
            }

            existing = findRecord(element);
            if (existing) {
                return existing.editor;
            }

            if (!global.Jodit || typeof global.Jodit.make !== 'function') {
                throw new Error('Jodit is not loaded.');
            }

            editor = global.Jodit.make(element, optionsWithDefaults(options));
            record = {
                editor: editor,
                element: element,
                options: options || {}
            };
            records.push(record);

            if (editor.events && typeof editor.events.on === 'function') {
                editor.events.on('change.editorAdapter', function () {
                    synchronize(record);
                });
            }

            form = typeof element.closest === 'function' ? element.closest('form') : null;
            bindForm(form);

            return editor;
        },

        get: function (target) {
            var record = findRecord(target);

            return record ? record.editor : null;
        },

        value: function (target) {
            var record = findRecord(target);
            var element;

            if (record) {
                return record.editor.value;
            }

            element = resolveElement(target);

            return element ? element.value : '';
        },

        sync: function (target) {
            return synchronize(findRecord(target));
        },

        syncForm: function (form) {
            var index;
            var record;

            for (index = 0; index < records.length; index += 1) {
                record = records[index];
                if (!form || (typeof form.contains === 'function' && form.contains(record.element))) {
                    synchronize(record);
                }
            }
        },

        syncAll: function () {
            EditorAdapter.syncForm(null);
        },

        destroy: function (target) {
            var record = findRecord(target);
            var index;

            if (!record) {
                return false;
            }

            synchronize(record);
            record.editor.destruct();
            index = records.indexOf(record);
            records.splice(index, 1);

            return true;
        },

        recreate: function (target, options) {
            var element = resolveElement(target);
            var record = findRecord(element);
            var nextOptions = options || (record ? record.options : {});

            if (record) {
                EditorAdapter.destroy(element);
            }

            return EditorAdapter.create(element, nextOptions);
        }
    };

    global.EditorAdapter = EditorAdapter;
}(window));
