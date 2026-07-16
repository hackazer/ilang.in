<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use PHPUnit\Framework\TestCase;

final class DependencyInventoryTest extends TestCase
{
    private string $fixtureRoot;
    private string $metadataRoot;

    protected function setUp(): void
    {
        $base = sys_get_temp_dir().'/ilang-dependency-inventory-'.bin2hex(random_bytes(6));
        $this->fixtureRoot = $base.'/project';
        $this->metadataRoot = $base.'/metadata';

        $this->write('composer.json', <<<'JSON'
{
  "require": {
    "php": "^8.3",
    "acme/direct": "^1.0"
  },
  "require-dev": {
    "acme/dev-tool": "^3.0",
    "phpunit/phpunit": "^12.5"
  }
}
JSON);
        $this->write('composer.lock', <<<'JSON'
{
  "packages": [
    {
      "name": "acme/direct",
      "version": "1.2.0",
      "license": ["MIT"],
      "autoload": {"psr-4": {"Acme\\Direct\\": "src/"}}
    },
    {
      "name": "acme/transitive",
      "version": "0.9.0",
      "license": ["BSD-3-Clause"],
      "abandoned": "acme/replacement",
      "autoload": {"psr-4": {"Acme\\Transitive\\": "src/"}}
    }
  ],
  "packages-dev": [
    {
      "name": "acme/dev-tool",
      "version": "3.4.0",
      "license": ["Apache-2.0"],
      "autoload": {"psr-4": {"Acme\\DevTool\\": "src/"}}
    },
    {
      "name": "phpunit/phpunit",
      "version": "12.5.31",
      "license": ["BSD-3-Clause"],
      "autoload": {"psr-4": {"PHPUnit\\": "src/"}}
    }
  ]
}
JSON);
        $this->write('package.json', <<<'JSON'
{
  "dependencies": {
    "@adminkit/core": "3.4.0",
    "@yaireo/tagify": "4.38.0",
    "fontawesome-iconpicker": "3.2.0",
    "jquery": "3.6.0",
    "popper.js": "1.16.1",
    "spectrum-colorpicker": "1.8.1"
  },
  "browserCompatibility": {
    "holds": {
      "jquery": "Bootstrap 4 requires jQuery major 3.",
      "fontawesomeIconpicker": "Font Awesome Icon Picker is discontinued.",
      "dashboardBundle": "Admin shell compatibility hold."
    }
  }
}
JSON);
        $this->write('package-lock.json', <<<'JSON'
{
  "lockfileVersion": 3,
  "packages": {
    "": {"dependencies":{"@adminkit/core":"3.4.0","@yaireo/tagify":"4.38.0","fontawesome-iconpicker":"3.2.0","jquery":"3.6.0","popper.js":"1.16.1","spectrum-colorpicker":"1.8.1"}},
    "node_modules/@adminkit/core": {"version":"3.4.0","license":"MIT"},
    "node_modules/@adminkit/core/node_modules/bootstrap": {"version":"5.3.0","license":"MIT"},
    "node_modules/@adminkit/core/node_modules/chart.js": {"version":"2.9.4","license":"MIT"},
    "node_modules/@yaireo/tagify": {"version":"4.38.0","license":"MIT"},
    "node_modules/fontawesome-iconpicker": {"version":"3.2.0","license":"MIT"},
    "node_modules/jquery": {"version":"3.6.0","license":"MIT"},
    "node_modules/popper.js": {"version":"1.16.1","license":"MIT"},
    "node_modules/spectrum-colorpicker": {"version":"1.8.1","license":"MIT"}
  }
}
JSON);
        $this->write('app/UsesDependency.php', <<<'PHP'
<?php

use Acme\Direct\Client;

return new Client();
PHP);
        $this->write('app/config/cdn.php', <<<'PHP'
<?php

file_put_contents(__DIR__.'/executed.txt', 'unsafe');

return [
    'editor' => [
        'version' => '4.16.1',
        'js' => ['https://cdn.ckeditor.com/[version]/standard/ckeditor.js'],
    ],
    'simpleeditor' => [
        'version' => '4.13.3',
        'js' => [assets('vendor/jodit/jodit.min.js')],
        'css' => [assets('vendor/jodit/jodit.min.css')],
    ],
    'mystery' => [
        'version' => '',
        'js' => ['https://cdn.example.test/mystery.js'],
    ],
];
PHP);
        $this->write('app/UsesCdn.php', "<?php\n\\Helpers\\CDN::load('editor');\n");
        $this->write('public/static/frontend/libs/jquery/package.json', <<<'JSON'
{"name":"jquery","version":"3.5.1","license":"MIT"}
JSON);
        $this->write('public/static/frontend/libs/jquery/dist/jquery.js', "/*! jQuery v3.5.1 | MIT */\n");
        $this->write('public/static/frontend/libs/jquery/dist/jquery.min.js', "/*! jQuery v3.5.1 | MIT */\n");
        $this->write('public/static/bundle.pack.js', "/*! jQuery JavaScript Library v3.5.1 | MIT */\n");
        $this->write('public/static/mystery.min.js', '!function(){window.mystery=true}();');
        $this->write('public/static/vendor/jodit/jodit.min.js', "/*! jodit Version: v4.13.3 License(s): MIT */\n");
        $this->write('public/static/vendor/jodit/LICENSE.txt', "MIT License\n");
        $this->write('public/static/frontend/libs/tagify/tagify.min.js', '!function(){}();');
        $this->write('public/static/frontend/libs/fontawesome-picker/dist/fontawesome-iconpicker.min.js', '!function(){}();');
        $this->write('public/static/frontend/libs/spectrum/spectrum.min.js', '!function(){}();');
        $this->write('public/static/backend/js/app.js', '!function(){}();');
        $this->write('public/static/vendor-manifest.json', <<<'JSON'
{
  "versions": {
    "@adminkit/core": "3.4.0",
    "@yaireo/tagify": "4.38.0",
    "jquery": "3.6.0",
    "spectrum-colorpicker": "1.8.1"
  },
  "holds": {
    "dashboardBundle": "Admin shell compatibility hold.",
    "fontawesomeIconpicker": "Font Awesome Icon Picker is discontinued.",
    "jquery": "Bootstrap 4 requires jQuery major 3."
  }
}
JSON);
        $this->write('storage/themes/default/admin/layouts/main.php', <<<'PHP'
<script src="/static/frontend/libs/jquery/dist/jquery.min.js"></script>
<script src="/static/mystery.min.js"></script>
<script src="/static/backend/js/app.js"></script>
PHP);
        $this->write('storage/plugins/example/config.json', <<<'JSON'
{"id":"example","name":"Example Plugin","version":"2.0.0","license":"MIT"}
JSON);
        $this->write('storage/themes/custom/config.json', <<<'JSON'
{"name":"Custom Theme","version":"1.1.0","author":"Example","child":true}
JSON);

        $this->writeMetadata('composer/acme/direct.json', <<<'JSON'
{"packages":{"acme/direct":[{"version":"2.1.0","version_normalized":"2.1.0.0","license":["MIT"]}]}}
JSON);
        $this->writeMetadata('composer/acme/dev-tool.json', <<<'JSON'
{"packages":{"acme/dev-tool":[{"version":"3.5.0","version_normalized":"3.5.0.0","license":["Apache-2.0"],"require":{"php":">=8.4.1"}}]}}
JSON);
        $this->writeMetadata('composer/acme/transitive.json', <<<'JSON'
{"packages":{"acme/transitive":[{"version":"1.0.0","version_normalized":"1.0.0.0","license":["BSD-3-Clause"],"abandoned":"acme/replacement"}]}}
JSON);
        $this->writeMetadata('npm/jquery.json', <<<'JSON'
{"dist-tags":{"latest":"3.7.1"},"versions":{"3.7.1":{"version":"3.7.1","license":"MIT"}}}
JSON);
        $this->writeMetadata('npm/@yaireo/tagify.json', <<<'JSON'
{"dist-tags":{"latest":"5.0.0-beta.1"},"versions":{"4.38.0":{"version":"4.38.0","license":"MIT"},"5.0.0-beta.1":{"version":"5.0.0-beta.1","license":"MIT"}}}
JSON);
    }

    protected function tearDown(): void
    {
        if (isset($this->fixtureRoot)) {
            $this->removeTree(dirname($this->fixtureRoot));
        }
    }

    public function testInventoryIsDeterministicAndCoversEveryDependencySurface(): void
    {
        [$firstStatus, $first] = $this->runInventory('--metadata-dir', $this->metadataRoot);
        [$secondStatus, $second] = $this->runInventory('--metadata-dir', $this->metadataRoot);

        self::assertSame(0, $firstStatus, $first);
        self::assertSame($first, $second);
        self::assertStringStartsWith("kind\tname\trelationship\tcurrent\tconstraint\tlatest\tlicense\tstatus\tsource\tcall_sites\tflags\n", $first);
        self::assertStringContainsString("composer\tacme/direct\tdirect-runtime\t1.2.0\t^1.0\t2.1.0\tMIT\tactive\tcomposer.lock\tapp/UsesDependency.php:3\toutdated", $first);
        self::assertStringContainsString("composer\tacme/transitive\ttransitive-runtime\t0.9.0\t-\t1.0.0\tBSD-3-Clause\tabandoned:acme/replacement\tcomposer.lock\t-\tabandoned,outdated", $first);
        self::assertStringContainsString("composer\tacme/dev-tool\tdirect-dev\t3.4.0\t^3.0\t3.5.0\tApache-2.0\tactive\tcomposer.lock\t-\tcompatibility-hold,outdated", $first);
        self::assertStringContainsString("browser\tjquery\tvendored\t3.6.0\t-\t3.7.1\tMIT\tactive\tpublic/static/frontend/libs/jquery/package.json", $first);
        self::assertStringContainsString("browser\tjodit\tvendored\t4.13.3\t-", $first);
        self::assertStringContainsString("browser\t@yaireo/tagify\tvendored\t4.38.0\t-\t4.38.0\tMIT\tactive", $first);
        self::assertStringContainsString("browser\tspectrum-colorpicker\tvendored\t1.8.1", $first);
        self::assertStringContainsString("cdn\teditor\tremote\t4.16.1", $first);
        self::assertStringContainsString("cdn\tsimpleeditor\tself-hosted\t4.13.3", $first);
        self::assertStringNotContainsString("cdn\tsimpleeditor\tself-hosted\t4.13.3\t-\t-\t-\teol", $first);
        self::assertStringContainsString("admin-shell\tjquery\tloaded\t3.6.0", $first);
        self::assertStringContainsString("admin-shell\t@adminkit/core\tbundled\t3.4.0", $first);
        self::assertStringContainsString("plugin\texample\tinstalled\t2.0.0", $first);
        self::assertStringContainsString("addon\tcustom\ttheme\t1.1.0", $first);
        self::assertStringContainsString("finding\tparallel-browser-artifacts", $first);
        self::assertStringContainsString("finding\tduplicate-browser-package", $first);
        self::assertStringContainsString("finding\tunknown-or-unversioned", $first);
        self::assertFileDoesNotExist($this->fixtureRoot.'/app/config/executed.txt');
    }

    public function testOfflineInventoryUsesManagedVersionsLicensesAndHolds(): void
    {
        [$status, $output] = $this->runInventory();

        self::assertSame(0, $status, $output);
        self::assertMatchesRegularExpression('/browser\tjquery\tvendored\t3\.6\.0\t-\t-\tMIT\tmanaged\t[^\n]+\tcompatibility-hold/', $output);
        self::assertMatchesRegularExpression('/browser\t@yaireo\/tagify\tvendored\t4\.38\.0\t-\t-\tMIT\tmanaged/', $output);
        self::assertMatchesRegularExpression('/browser\tpopper\.js\tbuild-input\t1\.16\.1\t-\t-\tMIT\tmanaged/', $output);
        self::assertMatchesRegularExpression('/browser\tfontawesome-iconpicker\tvendored\t3\.2\.0\t-\t-\tMIT\tdiscontinued\t[^\n]+\tcompatibility-hold,discontinued/', $output);
        self::assertMatchesRegularExpression('/browser\tjodit\tvendored\t4\.13\.3\t-\t-\tMIT\tmanaged/', $output);
        self::assertMatchesRegularExpression('/cdn\tsimpleeditor\tself-hosted\t4\.13\.3\t-\t-\tMIT\tmanaged/', $output);
        self::assertMatchesRegularExpression('/admin-shell\t@adminkit\/core\tbundled\t3\.4\.0\t-\t-\tMIT\tmanaged/', $output);
        self::assertMatchesRegularExpression('/admin-shell\tbootstrap\tembedded\t5\.3\.0\t-\t-\tMIT\tmanaged/', $output);
        self::assertMatchesRegularExpression('/admin-shell\tchart\.js\tembedded\t2\.9\.4\t-\t-\tMIT\tmanaged/', $output);
        self::assertMatchesRegularExpression('/composer\tphpunit\/phpunit\tdirect-dev\t12\.5\.31\t\^12\.5\t-\tBSD-3-Clause\tunknown\tcomposer\.lock\t-\tcompatibility-hold/', $output);
    }

    public function testMarkdownOutputProvidesARefreshableReleaseTable(): void
    {
        [$status, $output] = $this->runInventory('--metadata-dir', $this->metadataRoot, '--format', 'markdown');

        self::assertSame(0, $status, $output);
        self::assertStringContainsString('# Dependency inventory', $output);
        self::assertStringContainsString('## Release table', $output);
        self::assertStringContainsString('| Kind | Dependency | Relationship | Current | Constraint | Latest stable | License | Status | Evidence | Call sites | Flags |', $output);
        self::assertStringContainsString('https://repo.packagist.org/p2/acme/direct.json', $output);
        self::assertStringContainsString('https://registry.npmjs.org/jquery', $output);
    }

    public function testFindingsCanFailCiWithoutMakingDefaultInventoryFail(): void
    {
        [$defaultStatus] = $this->runInventory();
        [$strictStatus, $strictOutput] = $this->runInventory('--fail-on-findings');

        self::assertSame(0, $defaultStatus);
        self::assertSame(2, $strictStatus, $strictOutput);
    }

    private function runInventory(string ...$arguments): array
    {
        $script = dirname(__DIR__, 2).'/scripts/dependency-inventory.sh';
        $command = array_merge(['sh', $script, '--root', $this->fixtureRoot], $arguments);
        $specification = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $specification, $pipes);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);

        return [$status, $stdout.$stderr];
    }

    private function write(string $relativePath, string $contents): void
    {
        $path = $this->fixtureRoot.'/'.$relativePath;
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        file_put_contents($path, $contents);
    }

    private function writeMetadata(string $relativePath, string $contents): void
    {
        $path = $this->metadataRoot.'/'.$relativePath;
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        file_put_contents($path, $contents);
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($path);
    }
}
