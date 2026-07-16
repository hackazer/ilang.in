<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;

final class AdminUpdateAndWebhookCsrfTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function testAdminPurchaseCodeMutationUsesDedicatedCsrfProtectedPostRoute(): void
    {
        $routes = $this->source('app/routes.php');
        $controller = $this->source('app/controllers/admin/DashboardController.php');

        self::assertStringContainsString("Gem::get('/update', 'Admin\\Dashboard@update')->name('admin.update');", $routes);
        self::assertStringContainsString("Gem::post('/update/code', 'Admin\\Dashboard@purchaseCode')->name('admin.update.code');", $routes);
        self::assertStringNotContainsString("Gem::route(['GET', 'POST'], '/update', 'Admin\\Dashboard@update')", $routes);
        self::assertStringNotContainsString('if($request->newcode)', $controller);
        self::assertStringContainsString('public function purchaseCode(Request $request)', $controller);

        foreach (['default', 'ilangin-child'] as $theme) {
            $template = $this->source("storage/themes/{$theme}/admin/update.php");
            self::assertStringContainsString("route('admin.update.code')", $template);
            self::assertGreaterThanOrEqual(2, substr_count($template, 'csrf()'));
        }
    }

    public function testPaymentWebhooksArePostOnly(): void
    {
        $routes = $this->source('app/routes.php');

        self::assertStringContainsString("Gem::post('/ipn', 'Webhook@ipn')", $routes);
        self::assertStringContainsString("Gem::post('/webhook[/{provider}]', 'Webhook@index')", $routes);
        self::assertDoesNotMatchRegularExpression("/Gem::route\\(\\['GET', 'POST'\\], '\\/(?:ipn|webhook)/", $routes);
    }

    private function source(string $path): string
    {
        $source = file_get_contents($this->root.'/'.$path);

        self::assertIsString($source);

        return $source;
    }
}
