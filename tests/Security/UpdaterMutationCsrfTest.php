<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;

final class UpdaterMutationCsrfTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function testDatabaseUpdaterUsesReadOnlyGetAndCsrfProtectedPostRoutes(): void
    {
        $routes = $this->source('app/routes.php');

        self::assertStringContainsString("Gem::get('/update', 'Update@index');", $routes);
        self::assertStringContainsString("Gem::post('/update', 'Update@process');", $routes);
        self::assertStringNotContainsString("Gem::route(['GET', 'POST'], '/update'", $routes);
    }

    public function testUpdaterCannotRunMigrationsFromAQueryString(): void
    {
        $source = $this->source('app/controllers/UpdateController.php');

        self::assertStringNotContainsString('$request->update', $source);
        self::assertStringNotContainsString('$request->privatekey', $source);
        self::assertStringContainsString('public function process()', $source);
        self::assertStringContainsString('method=\"post\"', $source);
        self::assertStringContainsString("'.csrf().'", $source);
    }

    private function source(string $path): string
    {
        $source = file_get_contents($this->root.'/'.$path);

        self::assertIsString($source);

        return $source;
    }
}
