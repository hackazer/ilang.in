<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;

final class MixedAdminRouteCsrfTest extends TestCase
{
    public function testAdminImportAndEmailTemplateMutationsUseCsrfProtectedPostRoutes(): void
    {
        $root = dirname(__DIR__, 2);
        $routes = file_get_contents($root.'/app/routes.php');

        self::assertIsString($routes);
        self::assertStringContainsString("Gem::get('/links/import', 'Admin\\Links@import')->name('admin.links.import');", $routes);
        self::assertStringContainsString("Gem::post('/links/import', 'Admin\\Links@import')->name('admin.links.import.process');", $routes);
        self::assertStringContainsString("Gem::get('/email/templates', 'Admin\\Dashboard@emailTemplates')->name('admin.email.template');", $routes);
        self::assertStringContainsString("Gem::post('/email/templates', 'Admin\\Dashboard@emailTemplates')->name('admin.email.template.save');", $routes);
        self::assertStringNotContainsString("Gem::route(['GET', 'POST'], '/links/import'", $routes);
        self::assertStringNotContainsString("Gem::route(['GET', 'POST'], '/email/templates'", $routes);

        foreach (['default', 'ilangin-child'] as $theme) {
            $import = file_get_contents($root."/storage/themes/{$theme}/admin/links/import.php");
            $email = file_get_contents($root."/storage/themes/{$theme}/admin/email_templates.php");
            self::assertIsString($import);
            self::assertIsString($email);
            self::assertStringContainsString("route('admin.links.import.process')", $import);
            self::assertStringContainsString('route("admin.email.template.save")', $email);
        }
    }
}
