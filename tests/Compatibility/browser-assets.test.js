const test = require('node:test');
const assert = require('node:assert/strict');
const { existsSync, readFileSync } = require('node:fs');
const { resolve } = require('node:path');

const root = resolve(__dirname, '../..');

function json(path) {
  return JSON.parse(readFileSync(resolve(root, path), 'utf8'));
}

test('browser runtime dependencies use the current framework and icon packages', () => {
  const packageJson = json('package.json');
  const packageLock = json('package-lock.json');
  const dependencies = packageJson.dependencies;
  const holds = packageJson.browserCompatibility.holds;
  const compatibilityHolds = packageJson.dependencyReleasePolicy.compatibilityHolds;

  assert.equal(dependencies.bootstrap, '5.3.8');
  assert.equal(dependencies.jquery, '4.0.0');
  assert.equal(dependencies['@fortawesome/fontawesome-free'], '7.3.1');
  assert.equal(dependencies['air-datepicker'], '3.6.0');
  assert.equal(dependencies['@melloware/coloris'], '0.25.0');
  assert.equal(dependencies.imask, '7.6.1');
  assert.equal(dependencies.jodit, '4.13.5');
  assert.equal(dependencies['popper.js'], undefined);
  assert.equal(dependencies['fontawesome-iconpicker'], undefined);
  assert.equal(dependencies['@chenfengyuan/datepicker'], undefined);
  assert.equal(dependencies.moment, undefined);
  assert.equal(dependencies.daterangepicker, undefined);
  assert.equal(dependencies['spectrum-colorpicker'], undefined);
  assert.equal(dependencies['jquery-mask-plugin'], undefined);
  assert.equal(dependencies['tom-select'], undefined);
  assert.equal(dependencies['@adminkit/core'], undefined);
  assert.equal(dependencies['fontselect-jquery-plugin'], undefined);
  assert.equal(packageLock.packages['node_modules/@popperjs/core'].version, '2.11.8');
  assert.equal(packageLock.packages['node_modules/bootstrap'].peerDependencies['@popperjs/core'], '^2.11.8');

  for (const name of ['jquery', 'publicBootstrap', 'publicPopper', 'fontawesomeIconpicker', 'fontselectPackageLayout', 'dashboardBundle']) {
    assert.equal(holds[name], undefined, `${name} must not remain as a browser compatibility hold`);
  }

  for (const name of [
    'browser:bootstrap',
    'browser:jquery',
    'browser:popper.js',
    'browser:fontawesome-iconpicker',
    'browser:fontselect-jquery-plugin',
    'admin-shell:@adminkit/core',
    'admin-shell:bootstrap',
    'admin-shell:chart.js',
    'admin-shell:core-js',
    'admin-shell:feather-icons',
    'admin-shell:jsvectormap'
  ]) {
    assert.equal(compatibilityHolds[name], undefined, `${name} must not remain as a release-policy hold`);
  }

  assert.equal(compatibilityHolds['composer:phpunit/phpunit'], undefined);
  assert.match(packageJson.dependencyReleasePolicy.runtimeMatrix.phpunit, /actively supported/i);
});

test('generated assets expose the current runtimes and compact icon-picker metadata', () => {
  const manifest = json('public/static/vendor-manifest.json');
  const iconMetadata = json('public/static/frontend/libs/fontawesome-free/metadata/icons.json');

  assert.equal(manifest.versions.bootstrap, '5.3.8');
  assert.equal(manifest.versions.jquery, '4.0.0');
  assert.equal(manifest.versions['@fortawesome/fontawesome-free'], '7.3.1');
  assert.equal(manifest.versions['air-datepicker'], '3.6.0');
  assert.equal(manifest.versions['@melloware/coloris'], '0.25.0');
  assert.equal(manifest.versions.imask, '7.6.1');
  assert.equal(manifest.versions.jodit, '4.13.5');
  assert.equal(manifest.versions['fontawesome-iconpicker'], undefined);
  assert.equal(manifest.versions['popper.js'], undefined);
  assert.equal(manifest.versions['@chenfengyuan/datepicker'], undefined);
  assert.equal(manifest.versions.moment, undefined);
  assert.equal(manifest.versions.daterangepicker, undefined);
  assert.equal(manifest.versions['spectrum-colorpicker'], undefined);
  assert.equal(manifest.versions['jquery-mask-plugin'], undefined);
  assert.equal(manifest.versions['@adminkit/core'], undefined);

  for (const path of [
    'backend/css/app.css',
    'frontend/libs/air-datepicker/air-datepicker.css',
    'frontend/libs/air-datepicker/air-datepicker.js',
    'frontend/libs/coloris/coloris.min.css',
    'frontend/libs/coloris/coloris.min.js',
    'frontend/libs/fontawesome-free/css/all.min.css',
    'frontend/libs/fontawesome-free/metadata/icons.json',
    'frontend/libs/fontawesome-free/webfonts/fa-brands-400.woff2',
    'frontend/libs/fontawesome-free/webfonts/fa-regular-400.woff2',
    'frontend/libs/fontawesome-free/webfonts/fa-solid-900.woff2',
    'frontend/libs/fontawesome-free/webfonts/fa-v4compatibility.woff2',
    'frontend/libs/imask/imask.min.js',
    'vendor/jodit/LICENSE.txt',
    'vendor/jodit/jodit.min.css',
    'vendor/jodit/jodit.min.js'
  ]) {
    assert.ok(manifest.files[path], `${path} must be generated and checksummed`);
  }

  for (const path of [
    'backend/js/app.js',
    'backend/js/app.js.LICENSE.txt',
    'frontend/libs/fontawesome-picker/dist/css/fontawesome-iconpicker.min.css',
    'frontend/libs/fontawesome-picker/dist/js/fontawesome-iconpicker.min.js',
    'frontend/libs/datepicker/datepicker.min.css',
    'frontend/libs/datepicker/datepicker.min.js',
    'frontend/libs/daterangepicker/daterangepicker.min.css',
    'frontend/libs/daterangepicker/daterangepicker.min.js',
    'frontend/libs/jquery-mask-plugin/dist/jquery.mask.min.js',
    'frontend/libs/moment/moment.min.js',
    'frontend/libs/spectrum/spectrum.min.css',
    'frontend/libs/spectrum/spectrum.min.js'
  ]) {
    assert.equal(manifest.files[path], undefined, `${path} must not be generated`);
    assert.equal(existsSync(resolve(root, 'public/static', path)), false, `${path} must be retired`);
  }

  assert.equal(iconMetadata.version, '7.3.1');
  assert.deepEqual(iconMetadata.icons.github.styles, ['brands']);
  assert.deepEqual(iconMetadata.icons['address-book'].styles, ['regular', 'solid']);
  assert.equal(iconMetadata.icons['address-book'].label, 'Address Book');

  assert.deepEqual(manifest.embedded['backend/css/app.css'], {
    '@adminkit/core': '3.4.0',
    bootstrap: '5.3.8'
  });
  assert.deepEqual(manifest.embedded['bundle.pack.js'], {
    '@popperjs/core': '2.11.8',
    bootstrap: '5.3.8',
    clipboard: '2.0.11',
    'devbridge-autocomplete': '2.0.4',
    'feather-icons': '4.29.2',
    jquery: '4.0.0',
    select2: '4.1.0'
  });
  assert.deepEqual(manifest.embedded['frontend/libs/bootstrap/dist/js/bootstrap.bundle.min.js'], {
    '@popperjs/core': '2.11.8',
    bootstrap: '5.3.8'
  });

  const adminShellCss = readFileSync(resolve(root, 'public/static/backend/css/app.css'), 'utf8');
  const bootstrapCss = readFileSync(resolve(root, 'public/static/frontend/libs/bootstrap/dist/css/bootstrap.min.css'), 'utf8');
  const bootstrapBundle = readFileSync(resolve(root, 'public/static/frontend/libs/bootstrap/dist/js/bootstrap.bundle.min.js'), 'utf8');
  const jodit = readFileSync(resolve(root, 'public/static/vendor/jodit/jodit.min.js'), 'utf8');
  const jquery = readFileSync(resolve(root, 'public/static/frontend/libs/jquery/dist/jquery.min.js'), 'utf8');
  assert.match(adminShellCss, /AdminKit 3\.4\.0 shell styles compiled with Bootstrap 5\.3\.8/);
  assert.doesNotMatch(adminShellCss, /flatpickr|simplebar/);
  assert.match(bootstrapCss, /Bootstrap\s+v5\.3\.8/);
  assert.match(bootstrapBundle, /Bootstrap\s+v5\.3\.8/);
  assert.match(jodit, /Version: v4\.13\.5/);
  assert.match(jquery, /jQuery v4\.0\.0/);

  for (const file of [
    'public/static/frontend/libs/feather-icons/dist/feather.min.js',
    'public/static/bundle.pack.js'
  ]) {
    const source = readFileSync(resolve(root, file), 'utf8');
    assert.doesNotMatch(source, /core-js_shared|version:\"3\.1\.3\"/, file);
  }
  assert.deepEqual(manifest.provenance['feather-icons'], {
    source: 'feather-icons@4.29.2 dist/icons.json',
    generator: 'scripts/sync-browser-assets.mjs',
    excluded: ['published core-js browser polyfill']
  });
});

test('first-party consumers use the maintained icon assets without the AdminKit runtime', () => {
  for (const layout of [
    'storage/themes/default/layouts/dashboard.php',
    'storage/themes/default/admin/layouts/main.php',
    'storage/themes/ilangin-child/layouts/dashboard.php',
    'storage/themes/ilangin-child/admin/layouts/main.php'
  ]) {
    const source = readFileSync(resolve(root, layout), 'utf8');
    assert.doesNotMatch(source, /backend\/js\/app\.js/, layout);
    assert.doesNotMatch(source, /cdnjs\.cloudflare\.com\/ajax\/libs\/font-awesome/, layout);
    assert.match(source, /frontend\/libs\/fontawesome-free\/css\/all\.min\.css/, layout);
  }

  for (const consumer of [
    'app/controllers/admin/PlansController.php',
    'app/controllers/user/BioController.php',
    'public/static/bio.js'
  ]) {
    const source = readFileSync(resolve(root, consumer), 'utf8');
    assert.doesNotMatch(source, /fontawesome-picker|fontawesome-iconpicker|\.iconpicker\s*\(/, consumer);
  }
});
