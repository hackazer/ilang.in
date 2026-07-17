<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Bootstrap5MigrationTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function forbiddenSourcePatterns(): iterable
    {
        yield 'Bootstrap 4 JavaScript data attributes' => [
            '~\bdata-(?:toggle\s*=\s*["\'](?:alert|buttons?|carousel|collapse|dropdown|modal|offcanvas|pill|popover|scrollspy|tab|toast|tooltip)["\']|target|dismiss|parent|ride|slide(?:-to)?|placement|boundary|reference)\b~i',
            'Use the namespaced data-bs-* Bootstrap 5 API.',
        ];
        yield 'Bootstrap 4 directional utilities' => [
            '~\bclass\s*=\s*["\'][^"\']*(?<![a-z0-9_-])(?:[mp][lr](?:-(?:sm|md|lg|xl|xxl))?-(?:auto|n?[0-9]+)|text-(?:left|right)|float-(?:left|right)|border-(?:left|right)|rounded-(?:left|right)|dropdown-menu-(?:left|right))(?![a-z0-9_-])[^"\']*["\']~i',
            'Use logical start/end utilities.',
        ];
        yield 'invalid utility names used as CSS properties' => [
            '~(?:border|margin|padding)-(?:start|end)\s*:~i',
            'Use valid CSS properties; Bootstrap start/end names apply only to utility classes.',
        ];
        yield 'removed Bootstrap 4 components and utilities' => [
            '~(?<![a-z0-9_-])(?:no-gutters|form-row|form-group|form-inline|input-group-(?:append|prepend)|btn-block|btn-group-toggle|badge-pill|sr-only(?:-focusable)?|font-weight-(?:bold|bolder|normal|light|lighter)|font-italic|text-monospace|jumbotron|card-(?:deck|columns)|thead-(?:light|dark)|form-control-(?:label|file|range)|control-label|badge-(?:primary|secondary|success|danger|warning|info|light|dark)|custom-(?:control|checkbox|radio|switch|select|file|range)(?:-[a-z-]+)?|embed-responsive(?:-[0-9]+by[0-9]+|-item)?|img-responsive|rounded-(?:sm|lg)|text-hide)(?![a-z0-9_-])~i',
            'Use the Bootstrap 5 component or utility equivalent.',
        ];
        yield 'removed Bootstrap media component classes' => [
            '~\bclass\s*=\s*["\'][^"\']*(?<![a-z0-9_-])media(?:-body)?(?![a-z0-9_-])[^"\']*["\']~i',
            'Use flex utilities instead of the removed media component.',
        ];
        yield 'jQuery Bootstrap plugin calls' => [
            '~\.\s*(?:alert|button|carousel|collapse|dropdown|modal|offcanvas|popover|scrollspy|tab|toast|tooltip)\s*\(~i',
            'Use Bootstrap 5 native constructors or getOrCreateInstance().',
        ];
        yield 'discontinued Font Awesome icon picker API' => [
            '~fontawesome-iconpicker|\.\s*iconpicker\s*\(~i',
            'Use the native Font Awesome 7 picker hook.',
        ];
        yield 'retired Spectrum color picker API' => [
            '~spectrum-colorpicker|CDN::load\s*\(\s*["\']spectrum["\']\s*\)|\.\s*spectrum\s*\(~i',
            'Use the shared Coloris-backed color picker API.',
        ];
    }

    #[DataProvider('forbiddenSourcePatterns')]
    public function testThemeDoesNotUseRemovedFrontendApis(string $pattern, string $guidance): void
    {
        $violations = [];

        foreach ($this->sourceFiles() as $file) {
            $source = (string) file_get_contents($file);
            if (preg_match($pattern, $source) === 1) {
                $violations[] = $this->relativePath($file);
            }
        }

        self::assertSame([], $violations, $guidance.' Found in: '.implode(', ', $violations));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function iconPickerTemplates(): iterable
    {
        yield 'new plan' => ['admin/plans/new.php'];
        yield 'edit plan' => ['admin/plans/edit.php'];
        yield 'new bio link' => ['bio/new.php'];
        yield 'edit bio link' => ['bio/edit.php'];
    }

    #[DataProvider('iconPickerTemplates')]
    public function testFontAwesomeInputsUseTheNativePickerContract(string $relativePath): void
    {
        $source = (string) file_get_contents($this->themeRoot().'/'.$relativePath);

        self::assertSame(1, preg_match('~^.*\bname=["\']icon["\'].*$~mi', $source, $matches));
        $input = $matches[0];

        self::assertStringContainsString('autocomplete="off"', $input, $relativePath);
        self::assertStringContainsString('aria-describedby="', $input, $relativePath);
        self::assertStringNotContainsString('fab fa-twitter', $source, $relativePath);
        self::assertStringNotContainsString('fa fa-plus', $source, $relativePath);
        self::assertStringNotContainsString('fontawesome.com/v5/cheatsheet', $source, $relativePath);
    }

    public function testPostFormsKeepCsrfProtection(): void
    {
        $violations = [];

        foreach ($this->sourceFiles() as $file) {
            $source = (string) file_get_contents($file);
            preg_match_all('~<form\b(?=[^>]*\bmethod\s*=\s*["\']post["\'])[^>]*>.*?</form>~is', $source, $forms);

            foreach ($forms[0] as $index => $form) {
                if (!str_contains($form, 'csrf()')) {
                    $violations[] = $this->relativePath($file).'#'.($index + 1);
                }
            }
        }

        $allowed = [
            'gates/profile.php#1', // External PayPal checkout.
            'gates/profile.php#2', // Existing public newsletter action.
            'index.php#1', // Existing public shortener action.
        ];

        self::assertSame($allowed, $violations, 'Unexpected POST forms without csrf(): '.implode(', ', $violations));
    }

    public function testThemeSettingsUseTheSharedAccessibleColorisPicker(): void
    {
        $source = (string) file_get_contents($this->themeRoot().'/class/themeSettings.php');

        self::assertStringContainsString("CDN::load('coloris')", $source);
        self::assertStringContainsString('AppColorPicker.init("#c1, #c2", {', $source);
        self::assertStringContainsString('preferredFormat: "hex"', $source);
        self::assertStringContainsString('alpha: false', $source);
        self::assertStringNotContainsString('Coloris({', $source);
        self::assertMatchesRegularExpression('~<label[^>]*for="c1"~', $source);
        self::assertMatchesRegularExpression('~<label[^>]*for="c2"~', $source);
        self::assertMatchesRegularExpression('~<input[^>]*name="homecolor\[c1\]"[^>]*value="\'\.\(isset\(\$option->homecolor->c1\)~', $source);
        self::assertMatchesRegularExpression('~<input[^>]*name="homecolor\[c2\]"[^>]*value="\'\.\(isset\(\$option->homecolor->c2\)~', $source);
    }

    public function testMediaFlexReplacementIsLimitedToClassAttributes(): void
    {
        $violations = [];

        foreach ($this->sourceFiles() as $file) {
            foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $lineNumber => $line) {
                if (str_contains($line, 'd-flex align-items-start') && !str_contains($line, 'class=')) {
                    $violations[] = $this->relativePath($file).':'.($lineNumber + 1);
                }
            }
        }

        self::assertSame([], $violations, 'Flex utility replacement leaked outside class attributes.');
    }

    public function testCollapseParentsAreConfiguredOnCollapseElements(): void
    {
        $violations = [];

        foreach ($this->sourceFiles() as $file) {
            foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $lineNumber => $line) {
                if (str_contains($line, 'data-bs-parent=') && preg_match('~<(?:a|button)\b~i', $line) === 1) {
                    $violations[] = $this->relativePath($file).':'.($lineNumber + 1);
                }
            }
        }

        self::assertSame([], $violations, 'Bootstrap 5 collapse parents belong on collapse elements, not triggers.');
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function collapseParentTargets(): iterable
    {
        yield 'new bio main panels' => ['bio/new.php', 'links', 'generator'];
        yield 'new bio appearance panels' => ['bio/new.php', 'singlecolor', 'appearance'];
        yield 'new bio content modal' => ['bio/new.php', 'options', 'modalcontent'];
        yield 'edit bio main panels' => ['bio/edit.php', 'links', 'generator'];
        yield 'edit bio content modal' => ['bio/edit.php', 'options', 'modalcontent'];
        yield 'new QR type panels' => ['qr/new.php', 'text', 'qrbuilder'];
        yield 'new QR color panels' => ['qr/new.php', 'singlecolor', 'colors'];
        yield 'edit QR color panels' => ['qr/edit.php', 'singlecolor', 'colors'];
    }

    #[DataProvider('collapseParentTargets')]
    public function testCollapseGroupsKeepTheirBootstrapFiveParent(string $relativePath, string $id, string $parent): void
    {
        $matchingLine = null;
        foreach (file($this->themeRoot().'/'.$relativePath, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (str_contains($line, 'id="'.$id.'"') && str_contains($line, 'class="') && str_contains($line, 'collapse')) {
                $matchingLine = $line;
                break;
            }
        }

        self::assertNotNull($matchingLine, $relativePath.'#'.$id);
        self::assertStringContainsString('data-bs-parent="#'.$parent.'"', $matchingLine, $relativePath.'#'.$id);
    }

    /**
     * @return iterable<int, string>
     */
    private function sourceFiles(): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->themeRoot(), FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            if (str_starts_with($path, __DIR__.DIRECTORY_SEPARATOR)) {
                continue;
            }

            yield $path;
        }
    }

    private function relativePath(string $file): string
    {
        return ltrim(str_replace($this->themeRoot(), '', $file), DIRECTORY_SEPARATOR);
    }

    private function themeRoot(): string
    {
        $root = realpath(__DIR__.'/..');
        self::assertNotFalse($root);

        return $root;
    }
}
