'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

const root = path.resolve(__dirname, '../..');

function source(relativePath) {
    return fs.readFileSync(path.join(root, relativePath), 'utf8');
}

const firstPartyScripts = [
    'public/static/frontend/js/app.js',
    'public/static/custom.js',
    'public/static/server.js',
    'public/static/bio.js'
];

test('first-party scripts use Bootstrap 5 native component APIs', () => {
    const frontend = source('public/static/frontend/js/app.js');
    const custom = source('public/static/custom.js');
    const combined = frontend + custom;

    assert.doesNotMatch(frontend, /data-toggle=["'](?:tooltip|dropdown)["']/);
    assert.doesNotMatch(combined, /\.(?:modal|tooltip|popover|collapse|dropdown|tab|toast|button|carousel|alert)\s*\(/);
    assert.match(frontend, /bootstrap\.Tooltip\.getOrCreateInstance/);
    assert.match(custom, /bootstrap\.Collapse\.getOrCreateInstance/);
});

test('first-party scripts avoid jQuery 4 removed and undocumented APIs', () => {
    const combined = firstPartyScripts.map(source).join('\n');

    assert.doesNotMatch(combined, /\$\._data\b/);
    assert.doesNotMatch(combined, /\$\.(?:camelCase|isArray|isFunction|isNumeric|isWindow|nodeName|now|parseJSON|trim|type|unique)\b/);
    assert.doesNotMatch(combined, /:(?:eq|even|odd|gt|lt)\s*(?:\(|\b)/);
    assert.doesNotMatch(combined, /\$\.notify\s*\(/);
});

test('first-party notification calls use the maintained native toast adapter', () => {
    const server = source('public/static/server.js');
    const bio = source('public/static/bio.js');

    assert.match(server, /AppNotify\.(?:show|error)\(/);
    assert.match(bio, /AppNotify\.(?:show|error)\(/);
    assert.doesNotMatch(server + bio, /bootstrap-notify|\$\.notify/);
});

test('generated first-party bundles contain no legacy widget or Bootstrap plugin calls', () => {
    const generated = [
        'public/static/bio.min.js',
        'public/static/charts.min.js',
        'public/static/custom.min.js',
        'public/static/server.min.js',
        'public/static/frontend/js/app.min.js'
    ].map(source).join('\n');

    assert.doesNotMatch(generated, /\.(?:spectrum|datepicker|daterangepicker|fontselect|iconpicker)\s*\(/);
    assert.doesNotMatch(generated, /apply\.daterangepicker|\bmoment\s*\(|\$\._data|\$\.notify|:eq\s*\(/);
    assert.doesNotMatch(generated, /\.(?:modal|tooltip|popover|collapse|dropdown|tab|toast|carousel|alert)\s*\(/);
});
