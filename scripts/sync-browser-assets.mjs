import { createHash } from 'node:crypto';
import { existsSync } from 'node:fs';
import { mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import { dirname, join, resolve } from 'node:path';
import CleanCSS from 'clean-css';
import { minify } from 'terser';

const root = resolve(import.meta.dirname, '..');
const modules = join(root, 'node_modules');
const staticRoot = join(root, 'public/static');
const check = process.argv.includes('--check');
const outputs = new Map();

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

async function minifiedBundle(modulePaths, outputPath) {
  const input = (await Promise.all(modulePaths.map(async path => (await source(path)).toString()))).join(';\n');
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
await copy('select2/dist/css/select2.min.css', 'frontend/libs/select2/dist/css/select2.min.css');
await copy('select2/dist/js/select2.min.js', 'frontend/libs/select2/dist/js/select2.min.js');
await copy('clipboard/dist/clipboard.min.js', 'frontend/libs/clipboard/dist/clipboard.min.js');
await copy('feather-icons/dist/feather.min.js', 'frontend/libs/feather-icons/dist/feather.min.js');
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
await copy('moment/min/moment.min.js', 'frontend/libs/moment/moment.min.js');
await minifyModule('daterangepicker/daterangepicker.js', 'frontend/libs/daterangepicker/daterangepicker.min.js');
await minifyCss(['daterangepicker/daterangepicker.css'], 'frontend/libs/daterangepicker/daterangepicker.min.css');
await copy('@chenfengyuan/datepicker/dist/datepicker.min.js', 'frontend/libs/datepicker/datepicker.min.js');
await copy('@chenfengyuan/datepicker/dist/datepicker.min.css', 'frontend/libs/datepicker/datepicker.min.css');
await copy('devbridge-autocomplete/dist/jquery.autocomplete.min.js', 'frontend/libs/devbridge-autocomplete/jquery.autocomplete.min.js');
await minifyModule('@yaireo/tagify/dist/tagify.js', 'frontend/libs/tagify/tagify.min.js');
await copy('@yaireo/tagify/dist/tagify.css', 'frontend/libs/tagify/tagify.css');
await copy('cookieconsent/build/cookieconsent.min.js', 'cookieconsent.min.js');
await copy('cookieconsent/build/cookieconsent.min.css', 'cookieconsent.min.css');
await copy('jquery-mask-plugin/dist/jquery.mask.min.js', 'frontend/libs/jquery-mask-plugin/dist/jquery.mask.min.js');
await copy('svg-injector/dist/svg-injector.min.js', 'frontend/libs/svg-injector/dist/svg-injector.min.js');
await minifyModule('blockadblock/blockadblock.js', 'frontend/libs/blockadblock/blockadblock.min.js');
await minifyModule('spectrum-colorpicker/spectrum.js', 'frontend/libs/spectrum/spectrum.min.js');
await minifyCss(['spectrum-colorpicker/spectrum.css'], 'frontend/libs/spectrum/spectrum.min.css');
await copy('fontawesome-iconpicker/dist/js/fontawesome-iconpicker.min.js', 'frontend/libs/fontawesome-picker/dist/js/fontawesome-iconpicker.min.js');
await copy('fontawesome-iconpicker/dist/css/fontawesome-iconpicker.min.css', 'frontend/libs/fontawesome-picker/dist/css/fontawesome-iconpicker.min.css');
await copy('@adminkit/core/dist/js/app.js', 'backend/js/app.js');
await copy('@adminkit/core/dist/js/app.js.LICENSE.txt', 'backend/js/app.js.LICENSE.txt');
await copy('@adminkit/core/dist/css/app.css', 'backend/css/app.css');
await copy('chart.js/dist/chart.umd.min.js', 'Chart.min.js');

await minifiedBundle([
  'jquery/dist/jquery.min.js',
  'bootstrap/dist/js/bootstrap.bundle.min.js',
  'select2/dist/js/select2.min.js',
  'clipboard/dist/clipboard.min.js',
  'feather-icons/dist/feather.min.js',
  'devbridge-autocomplete/dist/jquery.autocomplete.min.js',
  '@chenfengyuan/datepicker/dist/datepicker.min.js'
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
const files = {};
for (const [path, content] of [...outputs].sort(([a], [b]) => a.localeCompare(b))) {
  files[path] = {
    bytes: content.byteLength,
    sha256: createHash('sha256').update(content).digest('hex')
  };
}
const manifest = Buffer.from(`${JSON.stringify({
  versions: packageJson.dependencies,
  holds: packageJson.browserCompatibility.holds,
  files
}, null, 2)}\n`);
outputs.set('vendor-manifest.json', manifest);

const retired = [
  'frontend/libs/bootstrap-notify',
  'frontend/libs/bootstrap-tagsinput'
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
  if (errors.length) {
    console.error(`Browser assets are stale:\n${errors.map(path => ` - ${path}`).join('\n')}`);
    process.exitCode = 1;
  } else {
    console.log(`Browser assets are reproducible (${outputs.size} files checked).`);
  }
} else {
  for (const path of retired) await rm(join(staticRoot, path), { recursive: true, force: true });
  await rm(join(staticRoot, 'frontend/libs/ace-builds'), { recursive: true, force: true });
  for (const [path, content] of outputs) {
    const target = join(staticRoot, path);
    await mkdir(dirname(target), { recursive: true });
    await writeFile(target, content);
  }
  const totalBytes = Object.values(files).reduce((sum, file) => sum + file.bytes, 0);
  console.log(`Synchronized ${outputs.size} browser assets (${totalBytes} bytes).`);
}
