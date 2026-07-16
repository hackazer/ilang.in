<?php

declare(strict_types=1);

namespace Tests\Security;

use Helpers\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

$sanitizer = dirname(__DIR__, 2).'/app/helpers/HtmlSanitizer.php';

if (is_file($sanitizer)) {
    require_once $sanitizer;
}

final class BioHtmlSanitizerTest extends TestCase
{
    public function testPreservesTheIntendedRichTextAllowlist(): void
    {
        $output = $this->sanitize(
            '<p class="lead"><strong>Bold</strong> <i>italic</i> '
            .'<a href="https://example.com/read?q=1" target="_blank" title="Read">link</a></p>'
            .'<ul><li><u>One</u></li></ul>'
            .'<img src="https://cdn.example.com/image.png" alt="Example" width="320" height="200">'
        );

        self::assertStringContainsString('<p>', $output);
        self::assertStringContainsString('<strong>Bold</strong>', $output);
        self::assertStringContainsString('<i>italic</i>', $output);
        self::assertStringContainsString('<ul><li><u>One</u></li></ul>', $output);
        self::assertStringContainsString('href="https://example.com/read?q=1"', $output);
        self::assertStringContainsString('target="_blank"', $output);
        self::assertStringContainsString('rel="nofollow noopener noreferrer"', $output);
        self::assertStringContainsString('src="https://cdn.example.com/image.png"', $output);
        self::assertStringContainsString('alt="Example"', $output);
        self::assertStringNotContainsString('class=', $output);
    }

    public function testRemovesExecutableAttributesAndUnsafeUrlSchemes(): void
    {
        $output = $this->sanitize(
            '<p onclick="alert(1)" style="background:url(javascript:alert(1))">Text</p>'
            .'<a href="&#x6a;avascript:alert(2)" onmouseover="alert(3)">unsafe link</a>'
            .'<img src="data:image/svg+xml,&lt;svg onload=alert(4)&gt;" onerror="alert(5)" srcset="x 1x">'
            .'<a href="mailto:user@example.com">mail</a>'
        );

        self::assertStringContainsString('<p>Text</p>', $output);
        self::assertStringContainsString('<a>unsafe link</a>', $output);
        self::assertStringContainsString('href="mailto:user@example.com"', $output);
        self::assertStringNotContainsStringIgnoringCase('javascript:', $output);
        self::assertStringNotContainsStringIgnoringCase('data:', $output);
        self::assertStringNotContainsStringIgnoringCase('onerror', $output);
        self::assertStringNotContainsStringIgnoringCase('onmouseover', $output);
        self::assertStringNotContainsStringIgnoringCase('onclick', $output);
        self::assertStringNotContainsStringIgnoringCase('style=', $output);
        self::assertStringNotContainsStringIgnoringCase('srcset=', $output);
    }

    public function testAllowsOnlyHttpsIframesFromApprovedEmbedProviders(): void
    {
        $output = $this->sanitize(
            '<iframe src="https://www.youtube-nocookie.com/embed/abc" width="560" height="315" '
            .'allowfullscreen onload="alert(1)"></iframe>'
            .'<iframe src="http://player.vimeo.com/video/123"></iframe>'
            .'<iframe src="https://youtube.example.test/embed/abc"></iframe>'
            .'<iframe src="https://127.0.0.1/admin"></iframe>'
            .'<iframe src="data:text/html,&lt;script&gt;alert(2)&lt;/script&gt;"></iframe>'
        );

        self::assertSame(1, substr_count($output, '<iframe'));
        self::assertStringContainsString('src="https://www.youtube-nocookie.com/embed/abc"', $output);
        self::assertStringContainsString('loading="lazy"', $output);
        self::assertStringContainsString('referrerpolicy="no-referrer"', $output);
        self::assertStringNotContainsStringIgnoringCase('onload', $output);
        self::assertStringNotContainsString('127.0.0.1', $output);
        self::assertStringNotContainsString('example.test', $output);
    }

    public function testDropsActiveContentAndNormalizesMalformedMarkup(): void
    {
        $payload = '<p><strong>Safe<img src=x onerror=alert(1)>'
            .'<svg><a href="javascript:alert(2)">bad</a></svg>'
            .'<script>alert(3)</script><style>@import "https://evil.test/x";</style>';

        $output = $this->sanitize($payload);

        self::assertStringContainsString('<p><strong>Safe', $output);
        self::assertStringNotContainsStringIgnoringCase('<script', $output);
        self::assertStringNotContainsStringIgnoringCase('<style', $output);
        self::assertStringNotContainsStringIgnoringCase('<svg', $output);
        self::assertStringNotContainsStringIgnoringCase('javascript:', $output);
        self::assertStringNotContainsStringIgnoringCase('onerror', $output);
        self::assertSame($output, $this->sanitize($output));
    }

    public function testSanitizesOnlyRichTextBioBlocksInProfileData(): void
    {
        $profile = [
            'links' => [
                ['type' => 'html', 'content' => '<p onload="x">Hello <b>world</b></p>'],
                ['type' => 'text', 'text' => '<a href="javascript:x">Read</a>'],
                ['type' => 'link', 'text' => '<img src=x onerror="x">', 'link' => 'https://example.com'],
                'invalid',
            ],
        ];

        $output = $this->sanitizeProfile($profile);

        self::assertSame('<p>Hello <b>world</b></p>', $output['links'][0]['content']);
        self::assertSame('<a>Read</a>', $output['links'][1]['text']);
        self::assertSame($profile['links'][2], $output['links'][2]);
        self::assertSame('invalid', $output['links'][3]);
    }

    public function testBioWriteAndRenderPathsUseTheReusableSanitizer(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root.'/app/controllers/user/BioController.php');
        $gate = file_get_contents($root.'/app/helpers/Gate.php');

        self::assertIsString($controller);
        self::assertIsString($gate);
        self::assertStringContainsString('use Helpers\\HtmlSanitizer;', $controller);
        self::assertGreaterThanOrEqual(2, substr_count($controller, 'HtmlSanitizer::sanitizeBioBlock($value)'));
        self::assertStringContainsString('HtmlSanitizer::sanitizeBioProfileData(', $controller);
        self::assertStringContainsString('HtmlSanitizer::sanitizeBioProfileData(', $gate);
        self::assertStringNotContainsString("Helper::clean(\$value, 3, false, '<strong><i><a><b><u><img><iframe><ul><ol><li><p>')", $controller);
    }

    public function testEditPreviewHexEscapesInlineScriptBoundaryCharacters(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 2).'/app/controllers/user/BioController.php');

        self::assertIsString($controller);
        self::assertStringContainsString(
            'JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT',
            $controller
        );
    }

    public function testSanitizerFailsClosedWithoutTheDomExtension(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/helpers/HtmlSanitizer.php');

        self::assertIsString($source);
        self::assertStringContainsString('if (!class_exists(DOMDocument::class))', $source);
    }

    private function sanitize(string $html): string
    {
        if (!class_exists(HtmlSanitizer::class)) {
            self::fail('The reusable HTML sanitizer has not been implemented.');
        }

        return HtmlSanitizer::sanitizeBioHtml($html);
    }

    private function sanitizeProfile(array $profile): array
    {
        if (!class_exists(HtmlSanitizer::class)) {
            self::fail('The reusable HTML sanitizer has not been implemented.');
        }

        return HtmlSanitizer::sanitizeBioProfileData($profile);
    }
}
