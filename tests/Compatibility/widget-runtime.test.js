'use strict';

const assert = require('node:assert/strict');
const path = require('node:path');
const test = require('node:test');

const root = path.resolve(__dirname, '../..');
const load = (asset) => require(path.join(root, 'public/static', asset));

test('color adapter preserves empty, hex, rgb, and alpha values', () => {
    const AppColorPicker = load('color-picker.js');

    assert.equal(AppColorPicker.color('').toHexString(), '');
    assert.equal(AppColorPicker.color('#0f87ff').toHexString(), '#0f87ff');
    assert.equal(AppColorPicker.color('rgb(15, 135, 255)').toHexString(), '#0f87ff');
    assert.equal(AppColorPicker.color('#0f87ff80').toRgbString(), 'rgba(15, 135, 255, 0.502)');
    assert.equal(AppColorPicker.normalize('rgb(15, 135, 255)', 'hex', false), '#0f87ff');
    assert.equal(AppColorPicker.normalize('', 'rgb', true), '');
});

test('date adapter uses stable submission formats and range presets', () => {
    const AppDatePicker = load('date-picker.js');
    const noon = new Date(2026, 6, 17, 12, 0, 0);

    assert.equal(AppDatePicker.format(noon, 'yyyy-MM-dd'), '2026-07-17');
    assert.equal(AppDatePicker.format(noon, 'MM/dd/yyyy'), '07/17/2026');

    const ranges = AppDatePicker.buildRanges(noon, {
        last7: 'Last 7 Days',
        last30: 'Last 30 Days',
        thisMonth: 'This Month',
        lastMonth: 'Last Month',
        last3Months: 'Last 3 Months'
    });

    assert.equal(AppDatePicker.format(ranges[0].dates[0], 'yyyy-MM-dd'), '2026-07-11');
    assert.equal(AppDatePicker.format(ranges[0].dates[1], 'yyyy-MM-dd'), '2026-07-17');
    assert.equal(AppDatePicker.format(ranges[3].dates[0], 'yyyy-MM-dd'), '2026-06-01');
    assert.equal(AppDatePicker.format(ranges[3].dates[1], 'yyyy-MM-dd'), '2026-06-30');
    assert.equal(AppDatePicker.format(ranges[4].dates[0], 'yyyy-MM-dd'), '2026-05-01');
});

test('font selector parses family and weight without changing stored syntax', () => {
    const FontSelector = load('font-selector.js');

    assert.deepEqual(FontSelector.parse('Open+Sans:600'), {
        value: 'Open+Sans:600',
        family: 'Open Sans',
        weight: '600'
    });
    assert.equal(FontSelector.format('Open Sans', '600'), 'Open+Sans:600');
    assert.equal(FontSelector.format('Roboto', '400'), 'Roboto');
});

test('mask adapter translates numeric mask tokens for IMask', () => {
    const AppMask = load('input-mask.js');

    assert.equal(AppMask.pattern('000 000'), '000 000');
    assert.equal(AppMask.pattern('(000) 000-0000'), '(000) 000-0000');
});
