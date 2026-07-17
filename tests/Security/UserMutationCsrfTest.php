<?php

declare(strict_types=1);

namespace Tests\Security;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class UserMutationCsrfTest extends TestCase
{
    private const POST_ROUTES = [
        'links.delete' => "Gem::post('/links/{id}/delete/{token}', 'Link@delete')",
        'links.reset' => "Gem::post('/links/{id}/reset/{token}', 'Link@reset')",
        'campaigns.delete' => "Gem::post('/campaigns/{id}/delete/{token}', 'User\\Campaigns@delete')",
        'links.archive' => "Gem::post('/links/archiveselected', 'Link@archiveSelected')",
        'links.unarchive' => "Gem::post('/links/unarchiveselected', 'Link@unarchiveSelected')",
        'links.public' => "Gem::post('/links/publicselected', 'Link@publicSelected')",
        'links.private' => "Gem::post('/links/privateselected', 'Link@privateSelected')",
        'splash.toggle' => "Gem::post('/splash/{id}/toggle', 'User\\Splash@toggle')",
        'splash.delete' => "Gem::post('/splash/{id}/delete', 'User\\Splash@delete')",
        'overlay.delete' => "Gem::post('/overlay/{id}/delete/{nonce}', 'User\\Overlay@delete')",
        'pixel.delete' => "Gem::post('/pixels/{id}/delete/{nonce}', 'User\\Pixels@delete')",
        'domain.delete' => "Gem::post('/domains/{id}/delete/{nonce}', 'User\\Domains@delete')",
        'qr.delete' => "Gem::post('/qr/{id}/delete/{nonce}', 'User\\QR@delete')",
        'qr.duplicate' => "Gem::post('/qr/{id}/duplicate', 'User\\QR@duplicate')",
        'bio.delete' => "Gem::post('/bio/{id}/delete/{nonce}', 'User\\Bio@delete')",
        'bio.default' => "Gem::post('/bio/{id}/default', 'User\\Bio@default')",
        'bio.duplicate' => "Gem::post('/bio/{id}/duplicate', 'User\\Bio@duplicate')",
        'channel.delete' => "Gem::post('/channel/{id}/delete/{token}', 'User\\Channels@delete')",
        'channel.removefrom' => "Gem::post('/channel/{id}/remove/{type}/{item}', 'User\\Channels@removefrom')",
    ];

    private const LINK_AJAX_METHODS = [
        'archiveSelected',
        'unarchiveSelected',
        'publicSelected',
        'privateSelected',
    ];

    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function postRouteProvider(): iterable
    {
        foreach (self::POST_ROUTES as $name => $definition) {
            yield $name => [$name, $definition];
        }
    }

    #[DataProvider('postRouteProvider')]
    public function testAuthenticatedUserMutationRouteUsesPost(string $name, string $definition): void
    {
        $routes = $this->read('app/routes.php');

        self::assertStringContainsString($definition."->name('{$name}')", $routes);
        self::assertStringNotContainsString(
            str_replace('Gem::post(', 'Gem::get(', $definition)."->name('{$name}')",
            $routes
        );
    }

    public function testReadOnlyAndAccountLandingRoutesRemainGet(): void
    {
        $routes = $this->read('app/routes.php');

        foreach ([
            "Gem::get('/login/reset/{token}', 'Users@reset')->middleware('CheckDomain')->name('reset')",
            "Gem::get('/activate/{token}', 'Users@activate')->middleware('CheckDomain')->name('activate')",
            "Gem::get('/links/archived', 'User\\Dashboard@archived')->name('archive')",
            "Gem::get('/links/fetch', 'User\\Dashboard@fetch')->name('links.fetch')",
            "Gem::get('/links/refresh', 'User\\Dashboard@refresh')->name('links.refresh')",
            "Gem::get('/links/refresh/archive', 'User\\Dashboard@refreshArchive')->name('links.refresh.archive')",
            "Gem::get('/bookmark', 'Link@bookmark')",
        ] as $definition) {
            self::assertStringContainsString($definition, $routes);
        }
    }

    public function testEveryLinkMutationJsonResponseReturnsFreshCsrfToken(): void
    {
        $controller = $this->read('app/controllers/LinkController.php');

        foreach (self::LINK_AJAX_METHODS as $method) {
            $source = $this->methodSource($controller, $method);
            $responseCount = 0;

            foreach (preg_split('/\R/', $source) ?: [] as $line) {
                if (!str_contains($line, 'Response::factory(')) {
                    continue;
                }

                $responseCount++;
                self::assertStringContainsString("'token' => csrf_token()", $line, $method);
                self::assertStringContainsString('->json()', $line, $method);
            }

            self::assertGreaterThanOrEqual(3, $responseCount, $method);
        }
    }

    public function testSplashDeletionIsScopedToAuthenticatedOwnerBeforeMutation(): void
    {
        $controller = $this->read('app/controllers/user/SplashController.php');
        $source = $this->methodSource($controller, 'delete');

        self::assertStringContainsString('public function delete(int $id)', $source);
        self::assertStringContainsString(
            'DB::splash()->where(\'id\', $id)->where(\'userid\', Auth::user()->rID())->first()',
            $source
        );
        self::assertStringContainsString(
            'DB::url()->where("type", $splash->id)->where(\'userid\', Auth::user()->rID())->update',
            $source
        );
        self::assertStringNotContainsString('Helper::validateNonce', $source);
    }

    public function testLinkMutationAjaxPostsCurrentTokenAndRotatesItOnAllResponsePaths(): void
    {
        foreach (['public/static/server.js', 'public/static/server.min.js'] as $path) {
            $source = $this->read($path);

            self::assertStringContainsString('updateCsrfToken', $source, $path);
            self::assertStringContainsString('_token', $source, $path);
            self::assertMatchesRegularExpression(
                '~archiveselected[\s\S]+?type\s*:\s*["\']POST["\']~',
                $source,
                $path
            );
        }

        $source = $this->read('public/static/server.js');
        $handler = $this->between(
            $source,
            "$(document).on('click', '[data-trigger=archiveselected]'",
            "$(document).on('change', \"#payment-form select\""
        );

        self::assertStringContainsString("form.find('input[name=_token]').val()", $handler);
        self::assertStringContainsString('updateCsrfToken(response);', $handler);
        self::assertStringContainsString('error: function(xhr)', $handler);
        self::assertStringContainsString('updateCsrfToken(xhr.responseJSON || {});', $handler);
        self::assertStringNotContainsString('type: "GET"', $handler);
    }

    public function testMutationControlsAreCsrfProtectedPostFormsInBothThemes(): void
    {
        foreach (['default', 'ilangin-child'] as $theme) {
            $sources = $this->themeSources($theme);
            $allSource = implode("\n", $sources);

            foreach (array_keys(self::POST_ROUTES) as $routeName) {
                if (!str_contains($allSource, $routeName)) {
                    continue;
                }

                self::assertDoesNotMatchRegularExpression(
                    '~<a\b[^>]*href=["\'][^"\']*route\(["\']'.preg_quote($routeName, '~').'["\']~is',
                    $allSource,
                    $theme.' must not expose '.$routeName.' as a link.'
                );

                $forms = $this->formsReferencingRoute($sources, $routeName);
                self::assertNotEmpty($forms, $theme.' must submit '.$routeName.' from a form.');

                foreach ($forms as $form) {
                    self::assertStringContainsString('method="post"', $form, $routeName);
                    self::assertStringContainsString('csrf()', $form, $routeName);
                    self::assertMatchesRegularExpression('~<button\b[^>]*type=["\']submit["\']~i', $form, $routeName);
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    private function themeSources(string $theme): array
    {
        $directory = $this->root.'/storage/themes/'.$theme;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );
        $sources = [];

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            if (str_contains($path, '/layouts/') || str_contains($path, '/admin/')) {
                continue;
            }

            $source = file_get_contents($path);
            self::assertNotFalse($source, $path);
            $sources[] = $source;
        }

        return $sources;
    }

    /**
     * @param list<string> $sources
     * @return list<string>
     */
    private function formsReferencingRoute(array $sources, string $routeName): array
    {
        $matches = [];

        foreach ($sources as $source) {
            preg_match_all('~<form\b[^>]*>.*?</form>~is', $source, $forms);
            foreach ($forms[0] as $form) {
                if (preg_match('~route\(["\']'.preg_quote($routeName, '~').'["\']~', $form) === 1) {
                    $matches[] = $form;
                }
            }
        }

        return $matches;
    }

    private function methodSource(string $source, string $method): string
    {
        $start = strpos($source, 'public function '.$method.'(');
        self::assertNotFalse($start, $method);

        $brace = strpos($source, '{', $start);
        self::assertNotFalse($brace, $method);
        $depth = 0;
        $length = strlen($source);

        for ($offset = $brace; $offset < $length; $offset++) {
            if ($source[$offset] === '{') {
                $depth++;
            } elseif ($source[$offset] === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($source, $start, $offset - $start + 1);
                }
            }
        }

        self::fail('Could not isolate '.$method.'.');
    }

    private function between(string $source, string $startNeedle, string $endNeedle): string
    {
        $start = strpos($source, $startNeedle);
        self::assertNotFalse($start, $startNeedle);
        $end = strpos($source, $endNeedle, $start);
        self::assertNotFalse($end, $endNeedle);

        return substr($source, $start, $end - $start);
    }

    private function read(string $relativePath): string
    {
        $source = file_get_contents($this->root.'/'.$relativePath);
        self::assertNotFalse($source, $relativePath);

        return $source;
    }
}
