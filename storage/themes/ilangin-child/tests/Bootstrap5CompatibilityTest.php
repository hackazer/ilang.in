<?php

declare(strict_types=1);

namespace Tests\Themes\IlanginChild;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class Bootstrap5CompatibilityTest extends TestCase
{
    private const THEME_ROOT = __DIR__;

    public function testBootstrapFourDataApiIsNotUsed(): void
    {
        $this->assertThemeDoesNotMatch(
            '~\b(?:data-toggle\s*=\s*["\'](?:button|buttons|carousel|collapse|dropdown|modal|pill|popover|tab|tooltip)["\']|data-(?:backdrop|dismiss|keyboard|parent|placement|ride|slide|slide-to|target)\s*=)~i',
            'Bootstrap 4 data API'
        );
    }

    public function testRemovedBootstrapFourClassesAreNotUsed(): void
    {
        $this->assertThemeClassLinesDoNotMatch(
            '~(?:^|\s)(?:
                (?:m|p)[lr](?:-(?:sm|md|lg|xl))?-(?:n?[0-5]|auto)
                |(?:text|float|border|rounded)(?:-(?:sm|md|lg|xl))?-(?:left|right)
                |badge-(?:pill|primary|secondary|success|danger|warning|info|light|dark)
                |btn-(?:block|group-toggle|xs)
                |card-(?:columns|deck)
                |close
                |control-label
                |custom-(?:checkbox|control|control-input|control-label|file|file-input|file-label|radio|range|select|switch)
                |dropleft|dropright
                |embed-responsive(?:-[0-9]+by[0-9]+)?
                |font-(?:italic|weight-(?:bold|bolder|light|lighter|normal))
                |form-(?:control-file|control-range|group|inline|row)
                |img-responsive
                |input-group-(?:append|prepend)
                |jumbotron(?:-fluid)?
                |media(?:-body)?
                |no-gutters
                |hidden-xs|pull-(?:left|right)
                |rounded-(?:lg|sm)
                |sr-only(?:-focusable)?
                |text-hide|text-monospace
                |thead-(?:dark|light)
            )(?=\s|$)~ix',
            'removed Bootstrap 4 class'
        );

        $this->assertThemeDoesNotMatch(
            '~\bbadge-<\?php~i',
            'dynamic Bootstrap 4 contextual badge class'
        );
    }

    public function testLegacyJqueryPluginsAndFontAwesomeFiveReferencesAreNotUsed(): void
    {
        $this->assertThemeDoesNotMatch(
            '~\.(?:button|carousel|collapse|dropdown|iconpicker|modal|popover|spectrum|tab|toast|tooltip)\s*\(|font-awesome/5\.|fontawesome\.com/v5/~i',
            'legacy jQuery plugin, discontinued picker, or Font Awesome 5 reference'
        );
    }

    public function testThemeLocalIconPickerFieldsUseFontAwesomeSevenSyntax(): void
    {
        foreach ([
            'admin/plans/edit.php' => 'fa-solid fa-plus',
            'admin/plans/new.php' => 'fa-solid fa-plus',
            'bio/edit.php' => 'fa-brands fa-x-twitter',
            'bio/new.php' => 'fa-brands fa-x-twitter',
        ] as $path => $example) {
            $source = $this->themeSource($path);

            self::assertStringContainsString('name="icon"', $source, $path);
            self::assertStringContainsString($example, $source, $path);
            self::assertStringContainsString('aria-describedby=', $source, $path);
        }

        foreach (['layouts/auth.php', 'layouts/dashboard.php', 'layouts/main.php'] as $path) {
            self::assertStringContainsString(
                "frontend/libs/fontawesome-free/css/all.min.css",
                $this->themeSource($path),
                $path
            );
        }
    }

    public function testThemeSettingsUsesAccessibleColorisInputsWithoutChangingSavedNames(): void
    {
        $source = $this->themeSource('class/themeSettings.php');

        self::assertStringContainsString("CDN::load('coloris')", $source);
        self::assertStringContainsString('Coloris({', $source);

        foreach (['c1', 'c2'] as $id) {
            self::assertStringContainsString('for="'.$id.'"', $source);
            self::assertStringContainsString('name="homecolor['.$id.']"', $source);
            self::assertStringContainsString('id="'.$id.'"', $source);
        }
    }

    private function assertThemeDoesNotMatch(string $pattern, string $label): void
    {
        $matches = [];

        foreach ($this->themeSources() as $path => $source) {
            foreach (preg_split('/\R/', $source) ?: [] as $index => $line) {
                if (preg_match($pattern, $line) === 1) {
                    $matches[] = $path.':'.($index + 1).': '.trim($line);
                }
            }
        }

        self::assertSame([], $matches, $label." remains:\n".implode("\n", $matches));
    }

    private function themeSource(string $path): string
    {
        $source = file_get_contents(dirname(self::THEME_ROOT).DIRECTORY_SEPARATOR.$path);

        self::assertNotFalse($source, $path);

        return $source;
    }

    private function assertThemeClassLinesDoNotMatch(string $pattern, string $label): void
    {
        $matches = [];

        foreach ($this->themeSources() as $path => $source) {
            foreach (preg_split('/\R/', $source) ?: [] as $index => $line) {
                if (preg_match_all('~\bclass\s*=\s*(["\'])(.*?)\1~i', $line, $classAttributes) === 0) {
                    continue;
                }

                foreach ($classAttributes[2] as $classAttribute) {
                    if (preg_match($pattern, $classAttribute) === 1) {
                        $matches[] = $path.':'.($index + 1).': '.trim($line);
                        break;
                    }
                }
            }
        }

        self::assertSame([], $matches, $label." remains:\n".implode("\n", $matches));
    }

    /**
     * @return iterable<string, string>
     */
    private function themeSources(): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(dirname(self::THEME_ROOT), RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            if (str_contains($path, DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            yield substr($path, strlen(dirname(self::THEME_ROOT)) + 1) => (string) file_get_contents($path);
        }
    }
}
