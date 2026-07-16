<?php

declare(strict_types=1);

namespace Tests\Security;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class AdminMutationCsrfTest extends TestCase
{
    private const POST_ROUTES = [
        'admin.plans.sync' => "Gem::post('/plans/sync', 'Admin\\Plans@sync')",
        'admin.plans.toggle' => "Gem::post('/plans/{id}/toggle', 'Admin\\Plans@toggle')",
        'admin.payments.markas' => "Gem::post('/payments/{id}/{action}','Admin\\Membership@markAs')",
        'admin.links.report.action' => "Gem::post('/links/report/{id}/{action}', 'Admin\\Links@reportAction')",
        'admin.links.bad.cancel' => "Gem::post('/links/bad/{id}/cancel', 'Admin\\Links@badCancel')",
        'admin.links.disable' => "Gem::post('/links/{id}/disable', 'Admin\\Links@disable')",
        'admin.links.approve' => "Gem::post('/links/{id}/approve', 'Admin\\Links@approve')",
        'admin.users.ban' => "Gem::post('/users/{id}/ban', 'Admin\\Users@ban')",
        'admin.users.verify' => "Gem::post('/user/verify/{id}/{token}', 'Admin\\Users@verify')",
        'admin.bio.toggle' => "Gem::post('/bio/toggle/{type}/{id}', 'Admin\\Bio@toggle')",
        'admin.domains.disable' => "Gem::post('/domains/{id}/disable', 'Admin\\Domains@disable')",
        'admin.domains.activate' => "Gem::post('/domains/{id}/activate', 'Admin\\Domains@activate')",
        'admin.domains.pending' => "Gem::post('/domains/{id}/pending', 'Admin\\Domains@pending')",
        'admin.affiliate.pay' => "Gem::post('/affiliates/{id}/pay', 'Admin\\Affiliates@pay')",
        'admin.affiliate.update' => "Gem::post('/affiliates/{id}/{action}', 'Admin\\Affiliates@update')",
        'admin.themes.activate' => "Gem::post('/themes/{id}/activate', 'Admin\\Themes@activate')",
        'admin.plugins.activate' => "Gem::post('/plugins/{id}/activate', 'Admin\\Plugins@activate')",
        'admin.plugins.disable' => "Gem::post('/plugins/{id}/disable', 'Admin\\Plugins@disable')",
        'admin.plugins.install' => "Gem::post('/plugins/directory/{id}/install', 'Admin\\Plugins@install')",
        'admin.languages.set' => "Gem::post('/languages/{id}/set', 'Admin\\Languages@set')",
        'admin.languages.sync' => "Gem::post('/languages/{id}/sync', 'Admin\\Languages@sync')",
        'admin.languages.auto' => "Gem::post('/languages/{id}/auto', 'Admin\\Languages@automatic')",
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
    public function testHighRiskAdminMutationRouteUsesPost(string $name, string $definition): void
    {
        $routes = $this->read('app/routes.php');

        self::assertStringContainsString($definition."->name('{$name}')", $routes);
        self::assertStringNotContainsString(
            str_replace('Gem::post(', 'Gem::get(', $definition)."->name('{$name}')",
            $routes
        );
    }

    public function testReadOnlyAdminPagesRemainGetRoutes(): void
    {
        $routes = $this->read('app/routes.php');

        foreach ([
            "Gem::get('/plans', 'Admin\\Plans@index')->name('admin.plans')",
            "Gem::get('/payments', 'Admin\\Membership@payments')->name('admin.payments')",
            "Gem::get('/links/pending', 'Admin\\Links@pending')->name('admin.links.pending')",
            "Gem::get('/links/report', 'Admin\\Links@report')->name('admin.links.report')",
            "Gem::get('/users/banned', 'Admin\\Users@banned')->name('admin.users.banned')",
            "Gem::get('/plugins/directory', 'Admin\\Plugins@directory')->name('admin.plugins.dir')",
        ] as $definition) {
            self::assertStringContainsString($definition, $routes);
        }
    }

    public function testMutationControlsAreCsrfProtectedPostFormsInBothThemes(): void
    {
        foreach (['default', 'ilangin-child'] as $theme) {
            $sources = $this->adminTemplateSources($theme);
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

    public function testPluginDirectoryInstallHasDedicatedPostAction(): void
    {
        $controller = $this->read('app/controllers/admin/PluginsController.php');

        self::assertStringContainsString('public function install(Request $request, string $id)', $controller);
        self::assertStringContainsString('ArchiveValidator::packageName($id)', $controller);
        self::assertStringNotContainsString('if($request->install)', $controller);
        self::assertStringNotContainsString('$request->install', $controller);
    }

    public function testPluginActivationHookCannotBeTriggeredByReadOnlyIndexQuery(): void
    {
        $controller = $this->read('app/controllers/admin/PluginsController.php');
        $indexStart = strpos($controller, 'public function index(Request $request)');
        $activateStart = strpos($controller, 'public function activate', $indexStart ?: 0);

        self::assertNotFalse($indexStart);
        self::assertNotFalse($activateStart);

        $indexMethod = substr($controller, $indexStart, $activateStart - $indexStart);
        self::assertStringNotContainsString('$request->activated', $indexMethod);
        self::assertStringContainsString("Plugin::dispatch('admin.plugin.activate'", $controller);
    }

    /**
     * @return list<string>
     */
    private function adminTemplateSources(string $theme): array
    {
        $directory = $this->root.'/storage/themes/'.$theme.'/admin';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );
        $sources = [];

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            if (str_contains($path, '/layouts/') || basename($path) === 'editor.php') {
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

    private function read(string $relativePath): string
    {
        $source = file_get_contents($this->root.'/'.$relativePath);
        self::assertNotFalse($source, $relativePath);

        return $source;
    }
}
