<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;

final class TenantResourceOwnershipTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function testTeamDeletionIsPostOnlyAndDoesNotAcceptTenantOrNonceParameters(): void
    {
        $routes = $this->read('app/routes.php');

        self::assertStringContainsString(
            "Gem::post('/teams/user/{id}/remove', 'User\\Teams@delete')->name('team.delete');",
            $routes
        );
        self::assertStringNotContainsString("Gem::get('/teams/{team}/user/{id}/remove/{nonce}'", $routes);
        self::assertStringNotContainsString("Gem::post('/teams/{team}/user/{id}/remove/{nonce}'", $routes);
    }

    public function testTeamDeletionRequiresOwnerAndScopesMemberToAuthenticatedOwner(): void
    {
        $source = $this->methodSource($this->read('app/controllers/user/TeamsController.php'), 'delete');

        self::assertStringContainsString('public function delete(int $id)', $source);
        self::assertStringContainsString('if(Auth::user()->teamid)', $source);
        self::assertStringContainsString(
            "DB::user()->where('id', \$id)->where('teamid', Auth::user()->rID())->first()",
            $source
        );
        self::assertStringNotContainsString('Helper::validateNonce', $source);
        self::assertLessThan(
            strpos($source, 'DB::user()->where'),
            strpos($source, 'if(Auth::user()->teamid)'),
            'Owner authorization must happen before the member lookup.'
        );
    }

    public function testSplashEditAndUpdateAreScopedToAuthenticatedOwner(): void
    {
        $controller = $this->read('app/controllers/user/SplashController.php');

        foreach (['edit', 'update'] as $method) {
            $source = $this->methodSource($controller, $method);

            self::assertStringContainsString(
                "DB::splash()->where('id', \$id)->where('userid', Auth::user()->rID())->first()",
                $source,
                $method
            );
            self::assertStringNotContainsString(
                "DB::splash()->where('id', \$id)->first()",
                $source,
                $method
            );
        }
    }

    public function testTeamDeleteControlsAreCsrfProtectedPostFormsInBothThemes(): void
    {
        foreach (['default', 'ilangin-child'] as $theme) {
            $source = $this->read('storage/themes/'.$theme.'/teams/index.php');

            self::assertDoesNotMatchRegularExpression(
                '~<a\b[^>]*href=["\'][^"\']*route\(["\']team\.delete["\']~is',
                $source,
                $theme
            );
            preg_match_all('~<form\b[^>]*>.*?</form>~is', $source, $forms);
            $deleteForms = array_values(array_filter(
                $forms[0],
                static fn (string $form): bool => str_contains($form, "route('team.delete', [\$team->id])")
            ));

            self::assertCount(1, $deleteForms, $theme);
            self::assertStringContainsString('method="post"', $deleteForms[0], $theme);
            self::assertStringContainsString('csrf()', $deleteForms[0], $theme);
            self::assertMatchesRegularExpression('~<button\b[^>]*type=["\']submit["\']~i', $deleteForms[0], $theme);
            self::assertStringNotContainsString("Helper::nonce('team.delete')", $source, $theme);
        }
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

    private function read(string $relativePath): string
    {
        $source = file_get_contents($this->root.'/'.$relativePath);
        self::assertNotFalse($source, $relativePath);

        return $source;
    }
}
