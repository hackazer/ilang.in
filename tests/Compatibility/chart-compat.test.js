const test = require('node:test');
const assert = require('node:assert/strict');
const { spawnSync } = require('node:child_process');
const { resolve } = require('node:path');

const chartCompat = require('../../public/static/chart-compat.js');
const root = resolve(__dirname, '../..');

test('line options use Chart 2 APIs for the AdminKit bundled runtime', () => {
  const options = chartCompat.lineOptions({ version: '2.9.4' }, { reverseX: true, yStepSize: 1000 });

  assert.equal(options.legend.display, false);
  assert.equal(options.tooltips.intersect, false);
  assert.equal(options.scales.xAxes[0].reverse, true);
  assert.equal(options.scales.yAxes[0].ticks.stepSize, 1000);
  assert.equal(options.plugins.legend, undefined);
});

test('line options use Chart 4 APIs after the stats runtime replaces the global', () => {
  const options = chartCompat.lineOptions({ version: '4.5.1' }, { reverseX: true, yStepSize: 1000 });

  assert.equal(options.plugins.legend.display, false);
  assert.equal(options.plugins.tooltip.intersect, false);
  assert.equal(options.scales.x.reverse, true);
  assert.equal(options.scales.y.ticks.stepSize, 1000);
  assert.equal(options.scales.xAxes, undefined);
  assert.equal(options.tooltips, undefined);
});

test('doughnut options preserve the cutout across Chart 2 and Chart 4', () => {
  const chart2 = chartCompat.doughnutOptions({ version: '2.9.4' }, { legendDisplay: false, cutout: 75 });
  const chart4 = chartCompat.doughnutOptions({ version: '4.5.1' }, { legendDisplay: false, cutout: 75 });

  assert.equal(chart2.cutoutPercentage, 75);
  assert.equal(chart2.legend.display, false);
  assert.equal(chart4.cutout, '75%');
  assert.equal(chart4.plugins.legend.display, false);
  assert.equal(chart4.cutoutPercentage, undefined);
});

test('unparseable Chart versions fail closed', () => {
  assert.throws(() => chartCompat.lineOptions({ version: 'unknown' }), /Unsupported Chart.js version/);
});

test('generated browser assets are current and syntactically valid', () => {
  const sync = spawnSync(process.execPath, ['scripts/sync-browser-assets.mjs', '--check'], {
    cwd: root,
    encoding: 'utf8'
  });

  assert.equal(sync.status, 0, sync.stdout + sync.stderr);
  assert.match(sync.stdout, /Browser assets are reproducible/);

  for (const path of [
    'public/static/chart-compat.js',
    'public/static/custom.js',
    'public/static/custom.min.js',
    'public/static/charts.js',
    'public/static/charts.min.js'
  ]) {
    const syntax = spawnSync(process.execPath, ['--check', path], { cwd: root, encoding: 'utf8' });
    assert.equal(syntax.status, 0, `${path}\n${syntax.stdout}${syntax.stderr}`);
  }
});
