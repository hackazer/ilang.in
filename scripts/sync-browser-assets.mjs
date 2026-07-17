import { createHash } from 'node:crypto';
import { existsSync } from 'node:fs';
import { mkdir, readdir, readFile, rm, writeFile } from 'node:fs/promises';
import { dirname, join, resolve } from 'node:path';
import CleanCSS from 'clean-css';
import * as sass from 'sass';
import { minify } from 'terser';

const root = resolve(import.meta.dirname, '..');
const modules = join(root, 'node_modules');
const staticRoot = join(root, 'public/static');
const check = process.argv.includes('--check');
const outputs = new Map();
const adminKitVersion = '3.4.0';
const adminKitGitHead = '2e0f84ab4acdc913706ce5b0c56badc08441d0cb';

async function source(path) {
  return readFile(join(modules, path));
}

async function copy(modulePath, outputPath) {
  outputs.set(outputPath, await source(modulePath));
}

async function copyNormalized(modulePath, outputPath) {
  const input = (await source(modulePath)).toString();
  const normalized = input.split('\n').map(line => line.trimEnd()).join('\n').trimEnd();
  outputs.set(outputPath, Buffer.from(`${normalized}\n`));
}

async function listFiles(path, prefix = '') {
  if (!existsSync(path)) return [];

  const files = [];
  for (const entry of await readdir(path, { withFileTypes: true })) {
    const relative = join(prefix, entry.name);
    if (entry.isDirectory()) {
      files.push(...await listFiles(join(path, entry.name), relative));
    } else {
      files.push(relative);
    }
  }
  return files;
}

async function fontAwesomeMetadata() {
  const packageJson = JSON.parse(await source('@fortawesome/fontawesome-free/package.json'));
  const iconFamilies = JSON.parse(await source('@fortawesome/fontawesome-free/metadata/icon-families.json'));
  const icons = {};

  for (const [name, metadata] of Object.entries(iconFamilies).sort(([a], [b]) => a.localeCompare(b))) {
    const styles = metadata.familyStylesByLicense.free
      .filter(({ family }) => family === 'classic')
      .map(({ style }) => style)
      .sort();
    if (!styles.length) continue;

    icons[name] = {
      label: metadata.label,
      styles,
      searchTerms: [...new Set(metadata.search.terms)].sort()
    };
  }

  return Buffer.from(`${JSON.stringify({ version: packageJson.version, icons }, null, 2)}\n`);
}

async function adminKitShellCss() {
  const sourcePath = join(root, 'scripts/vendor/adminkit-3.4.0-shell.scss');
  const result = sass.compileString(await readFile(sourcePath, 'utf8'), {
    loadPaths: [modules],
    style: 'compressed',
    quietDeps: true,
    silenceDeprecations: ['color-functions', 'global-builtin', 'if-function', 'import']
  });
  const banner = `/*! AdminKit ${adminKitVersion} shell styles compiled with Bootstrap 5.3.8; MIT source git ${adminKitGitHead} */`;
  return Buffer.from(`${banner}\n${result.css.trimEnd()}\n`);
}

async function featherRuntime() {
  const packageJson = JSON.parse(await source('feather-icons/package.json'));
  const iconContents = JSON.parse(await source('feather-icons/dist/icons.json'));
  const runtime = `(function (global) {
    'use strict';
    const contents = ${JSON.stringify(iconContents)};
    const baseAttributes = {
      xmlns: 'http://www.w3.org/2000/svg', width: 24, height: 24,
      viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor',
      'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round'
    };
    const escapeAttribute = value => String(value)
      .replaceAll('&', '&amp;').replaceAll('"', '&quot;')
      .replaceAll('<', '&lt;').replaceAll('>', '&gt;');
    const svg = (name, attributes = {}) => {
      const merged = { ...baseAttributes, ...attributes };
      merged.class = ['feather', 'feather-' + name, attributes.class].filter(Boolean).join(' ');
      const serialized = Object.entries(merged)
        .map(([key, value]) => key + '=\"' + escapeAttribute(value) + '\"')
        .join(' ');
      return '<svg ' + serialized + '>' + contents[name] + '</svg>';
    };
    const icons = Object.fromEntries(Object.keys(contents).map(name => [name, {
      name, contents: contents[name], attrs: baseAttributes, tags: [],
      toSvg: attributes => svg(name, attributes)
    }]));
    const replace = (attributes = {}) => {
      if (typeof document === 'undefined') throw new Error('feather.replace() only works in a browser environment.');
      document.querySelectorAll('[data-feather]').forEach(element => {
        const name = element.getAttribute('data-feather');
        if (!icons[name]) {
          console.warn(\"feather: '\" + name + \"' is not a valid icon\");
          return;
        }
        const elementAttributes = Object.fromEntries(Array.from(element.attributes).map(attribute => [attribute.name, attribute.value]));
        delete elementAttributes['data-feather'];
        elementAttributes.class = [attributes.class, elementAttributes.class].filter(Boolean).join(' ');
        const template = document.createElement('template');
        template.innerHTML = icons[name].toSvg({ ...attributes, ...elementAttributes });
        element.replaceWith(template.content.firstElementChild);
      });
    };
    global.feather = {
      icons,
      replace,
      toSvg(name, attributes) {
        if (!icons[name]) throw new Error(\"No icon matching '\" + name + \"'.\");
        return icons[name].toSvg(attributes);
      }
    };
  })(typeof globalThis === 'undefined' ? this : globalThis);`;
  const result = await minify(runtime, { compress: true, mangle: true });
  return Buffer.from(`/*! Feather Icons v${packageJson.version}; source rebuild without bundled polyfills; MIT */\n${result.code}\n`);
}

async function minifiedBundle(modulePaths, outputPath) {
  const input = (await Promise.all(modulePaths.map(async path => Buffer.isBuffer(path) ? path.toString() : (await source(path)).toString()))).join(';\n');
  const result = await minify(input, { compress: true, mangle: true, format: { comments: /^!/ } });
  if (!result.code) throw new Error(`Terser produced no output for ${outputPath}`);
  outputs.set(outputPath, Buffer.from(`${result.code}\n`));
}

async function minifyModule(modulePath, outputPath) {
  const input = (await source(modulePath)).toString();
  const result = await minify(input, { compress: true, mangle: true, format: { comments: /^!/ } });
  if (!result.code) throw new Error(`Terser produced no output for ${modulePath}`);
  outputs.set(outputPath, Buffer.from(`${result.code}\n`));
}

async function minifyJs(inputPaths, outputPath) {
  const paths = Array.isArray(inputPaths) ? inputPaths : [inputPaths];
  const input = (await Promise.all(paths.map(path => readFile(join(root, path), 'utf8')))).join(';\n');
  const result = await minify(input, { compress: true, mangle: true, format: { comments: /^!/ } });
  if (!result.code) throw new Error(`Terser produced no output for ${paths.join(', ')}`);
  outputs.set(outputPath, Buffer.from(`${result.code}\n`));
}

async function minifyCss(modulePaths, outputPath) {
  const input = (await Promise.all(modulePaths.map(path => source(path)))).join('\n');
  const result = new CleanCSS({ level: 2 }).minify(input);
  if (result.errors.length) throw new Error(result.errors.join('\n'));
  outputs.set(outputPath, Buffer.from(`${result.styles}\n`));
}

await copy('jquery/dist/jquery.min.js', 'frontend/libs/jquery/dist/jquery.min.js');
await copy('bootstrap/dist/css/bootstrap.min.css', 'frontend/libs/bootstrap/dist/css/bootstrap.min.css');
await copy('bootstrap/dist/js/bootstrap.bundle.min.js', 'frontend/libs/bootstrap/dist/js/bootstrap.bundle.min.js');
await copy('@fortawesome/fontawesome-free/css/all.min.css', 'frontend/libs/fontawesome-free/css/all.min.css');
for (const file of [
  'fa-brands-400.woff2',
  'fa-regular-400.woff2',
  'fa-solid-900.woff2',
  'fa-v4compatibility.woff2'
]) {
  await copy(`@fortawesome/fontawesome-free/webfonts/${file}`, `frontend/libs/fontawesome-free/webfonts/${file}`);
}
outputs.set('frontend/libs/fontawesome-free/metadata/icons.json', await fontAwesomeMetadata());
await copy('air-datepicker/air-datepicker.css', 'frontend/libs/air-datepicker/air-datepicker.css');
await copy('air-datepicker/air-datepicker.js', 'frontend/libs/air-datepicker/air-datepicker.js');
await copy('@melloware/coloris/dist/coloris.min.css', 'frontend/libs/coloris/coloris.min.css');
await copy('@melloware/coloris/dist/umd/coloris.min.js', 'frontend/libs/coloris/coloris.min.js');
await copy('imask/dist/imask.min.js', 'frontend/libs/imask/imask.min.js');
await copy('select2/dist/css/select2.min.css', 'frontend/libs/select2/dist/css/select2.min.css');
await copy('select2/dist/js/select2.min.js', 'frontend/libs/select2/dist/js/select2.min.js');
await copy('clipboard/dist/clipboard.min.js', 'frontend/libs/clipboard/dist/clipboard.min.js');
const generatedFeatherRuntime = await featherRuntime();
outputs.set('frontend/libs/feather-icons/dist/feather.min.js', generatedFeatherRuntime);
await copy('jsvectormap/dist/jsvectormap.min.css', 'frontend/libs/jsvectormap/dist/css/jsvectormap.min.css');
await copy('jsvectormap/dist/jsvectormap.min.js', 'frontend/libs/jsvectormap/dist/js/jsvectormap.min.js');
await copy('jsvectormap/dist/maps/world.js', 'frontend/libs/jsvectormap/dist/maps/world.js');
for (const file of [
  'ace.js',
  'ext-language_tools.js',
  'mode-css.js',
  'mode-html.js',
  'mode-javascript.js',
  'mode-json.js',
  'mode-php.js',
  'theme-chrome.js',
  'theme-dracula.js',
  'worker-css.js',
  'worker-html.js',
  'worker-javascript.js',
  'worker-json.js',
  'worker-php.js'
]) {
  await copyNormalized(`ace-builds/src-min-noconflict/${file}`, `frontend/libs/ace-builds/${file}`);
}
await copy('@highlightjs/cdn-assets/highlight.min.js', 'frontend/libs/highlight.js/highlight.min.js');
await copy('@highlightjs/cdn-assets/styles/night-owl.min.css', 'frontend/libs/highlight.js/night-owl.min.css');
await copy('devbridge-autocomplete/dist/jquery.autocomplete.min.js', 'frontend/libs/devbridge-autocomplete/jquery.autocomplete.min.js');
await minifyModule('@yaireo/tagify/dist/tagify.js', 'frontend/libs/tagify/tagify.min.js');
await copy('@yaireo/tagify/dist/tagify.css', 'frontend/libs/tagify/tagify.css');
await copy('cookieconsent/build/cookieconsent.min.js', 'cookieconsent.min.js');
await copy('cookieconsent/build/cookieconsent.min.css', 'cookieconsent.min.css');
await copy('svg-injector/dist/svg-injector.min.js', 'frontend/libs/svg-injector/dist/svg-injector.min.js');
await minifyModule('blockadblock/blockadblock.js', 'frontend/libs/blockadblock/blockadblock.min.js');
outputs.set('backend/css/app.css', await adminKitShellCss());
await copy('chart.js/dist/chart.umd.min.js', 'Chart.min.js');
await copy('jodit/es2015/jodit.min.js', 'vendor/jodit/jodit.min.js');
await copy('jodit/es2015/jodit.min.css', 'vendor/jodit/jodit.min.css');
await copy('jodit/LICENSE.txt', 'vendor/jodit/LICENSE.txt');

await minifiedBundle([
  'jquery/dist/jquery.min.js',
  'bootstrap/dist/js/bootstrap.bundle.min.js',
  'select2/dist/js/select2.min.js',
  'clipboard/dist/clipboard.min.js',
  generatedFeatherRuntime,
  'devbridge-autocomplete/dist/jquery.autocomplete.min.js'
], 'bundle.pack.js');

await minifiedBundle([
  'jquery/dist/jquery.min.js',
  'select2/dist/js/select2.min.js'
], 'backend/vendor.min.js');

await minifyCss([
  'select2/dist/css/select2.min.css'
], 'backend/vendor.min.css');

await minifiedBundle([
  'jquery/dist/jquery.min.js',
  'select2/dist/js/select2.min.js',
  '@yaireo/tagify/dist/tagify.js'
], 'backend/admin-vendor.min.js');

await minifyCss([
  'select2/dist/css/select2.min.css',
  '@yaireo/tagify/dist/tagify.css'
], 'backend/admin-vendor.min.css');

await minifyJs(['public/static/chart-compat.js', 'public/static/custom.js'], 'custom.min.js');
await minifyJs('public/static/charts.js', 'charts.min.js');

const packageJson = JSON.parse(await readFile(join(root, 'package.json'), 'utf8'));
const version = name => packageJson.dependencies[name];
const popperVersion = JSON.parse(await source('@popperjs/core/package.json')).version;
const files = {};
for (const [path, content] of [...outputs].sort(([a], [b]) => a.localeCompare(b))) {
  files[path] = {
    bytes: content.byteLength,
    sha256: createHash('sha256').update(content).digest('hex')
  };
}
const embedded = {
  'backend/admin-vendor.min.css': {
    '@yaireo/tagify': version('@yaireo/tagify'),
    select2: version('select2')
  },
  'backend/admin-vendor.min.js': {
    '@yaireo/tagify': version('@yaireo/tagify'),
    jquery: version('jquery'),
    select2: version('select2')
  },
  'backend/css/app.css': {
    '@adminkit/core': adminKitVersion,
    bootstrap: version('bootstrap')
  },
  'backend/vendor.min.css': {
    select2: version('select2')
  },
  'backend/vendor.min.js': {
    jquery: version('jquery'),
    select2: version('select2')
  },
  'bundle.pack.js': {
    '@popperjs/core': popperVersion,
    bootstrap: version('bootstrap'),
    clipboard: version('clipboard'),
    'devbridge-autocomplete': version('devbridge-autocomplete'),
    'feather-icons': version('feather-icons'),
    jquery: version('jquery'),
    select2: version('select2')
  },
  'Chart.min.js': {
    'chart.js': version('chart.js')
  },
  'frontend/libs/bootstrap/dist/js/bootstrap.bundle.min.js': {
    '@popperjs/core': popperVersion,
    bootstrap: version('bootstrap')
  }
};
const manifest = Buffer.from(`${JSON.stringify({
  versions: packageJson.dependencies,
  holds: packageJson.browserCompatibility.holds,
  embedded,
  provenance: {
    'feather-icons': {
      source: `feather-icons@${version('feather-icons')} dist/icons.json`,
      generator: 'scripts/sync-browser-assets.mjs',
      excluded: ['published core-js browser polyfill']
    },
    'backend/css/app.css': {
      source: `@adminkit/core@${adminKitVersion} SCSS from git ${adminKitGitHead}`,
      sourceFile: 'scripts/vendor/adminkit-3.4.0-shell.scss',
      sourceSha256: createHash('sha256').update(await readFile(join(root, 'scripts/vendor/adminkit-3.4.0-shell.scss'))).digest('hex'),
      compiler: `sass@${JSON.parse(await source('sass/package.json')).version}`,
      excluded: ['AdminKit distributed CSS and JavaScript', 'Flatpickr', 'Simplebar', 'stale embedded package runtimes']
    }
  },
  files
}, null, 2)}\n`);
outputs.set('vendor-manifest.json', manifest);

const retired = [
  'backend/js/app.js',
  'backend/js/app.js.LICENSE.txt',
  'backend/js/app.js.map',
  'frontend/libs/bootstrap-notify',
  'frontend/libs/bootstrap-tagsinput',
  'frontend/libs/datepicker',
  'frontend/libs/daterangepicker',
  'frontend/libs/fontawesome-picker',
  'frontend/libs/font-selector',
  'frontend/libs/jquery-mask-plugin',
  'frontend/libs/moment',
  'frontend/libs/spectrum'
];
const managedDirectories = [
  'frontend/libs/air-datepicker',
  'frontend/libs/bootstrap/dist',
  'frontend/libs/coloris',
  'frontend/libs/fontawesome-free',
  'frontend/libs/imask',
  'frontend/libs/jquery/dist',
  'vendor/jodit'
];

if (check) {
  const errors = [];
  for (const [path, expected] of outputs) {
    const target = join(staticRoot, path);
    if (!existsSync(target) || !expected.equals(await readFile(target))) errors.push(path);
  }
  for (const path of retired) {
    if (existsSync(join(staticRoot, path))) errors.push(`${path} must be removed`);
  }
  for (const directory of managedDirectories) {
    for (const path of await listFiles(join(staticRoot, directory))) {
      const outputPath = join(directory, path);
      if (!outputs.has(outputPath)) errors.push(`${outputPath} is not a managed output`);
    }
  }
  if (errors.length) {
    console.error(`Browser assets are stale:\n${errors.map(path => ` - ${path}`).join('\n')}`);
    process.exitCode = 1;
  } else {
    console.log(`Browser assets are reproducible (${outputs.size} files checked).`);
  }
} else {
  for (const path of retired) await rm(join(staticRoot, path), { recursive: true, force: true });
  for (const path of managedDirectories) await rm(join(staticRoot, path), { recursive: true, force: true });
  await rm(join(staticRoot, 'frontend/libs/ace-builds'), { recursive: true, force: true });
  for (const [path, content] of outputs) {
    const target = join(staticRoot, path);
    await mkdir(dirname(target), { recursive: true });
    await writeFile(target, content);
  }
  const totalBytes = Object.values(files).reduce((sum, file) => sum + file.bytes, 0);
  console.log(`Synchronized ${outputs.size} browser assets (${totalBytes} bytes).`);
}
