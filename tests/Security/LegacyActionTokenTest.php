<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;

final class LegacyActionTokenTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function testCronTokensKeepTheirDeployedFormatsAndUseConstantTimeChecks(): void
    {
        $source = $this->source('app/controllers/CronController.php');

        foreach (['nowpayments', 'user', 'data', 'url', 'remind'] as $scope) {
            self::assertStringContainsString(
                "hash_equals(md5('{$scope}'.AuthToken), \$token)",
                $source,
                "The {$scope} cron token must retain its deployed MD5 format and use hash_equals()."
            );
        }

        self::assertDoesNotMatchRegularExpression('/\$token\s*!={1,2}\s*md5\s*\(/', $source);
    }

    public function testPasswordResetAndSsoTokensKeepTheirDeployedFormatsAndUseConstantTimeChecks(): void
    {
        $source = $this->source('app/controllers/UsersController.php');
        $dailyToken = 'hash_equals(md5(AuthToken.": Expires on".strtotime(date(\'Y-m-d\'))), $expiry)';
        $hourlyToken = 'hash_equals(md5(AuthToken.": Expires on".strtotime(date(\'Y-m-d H\'))), $expiry)';

        self::assertSame(2, substr_count($source, $dailyToken));
        self::assertSame(1, substr_count($source, $hourlyToken));
        self::assertDoesNotMatchRegularExpression('/\$expiry\s*!={1,2}\s*md5\s*\(/', $source);
    }

    public function testUpdaterDoesNotAuthorizeMigrationsWithAQueryStringSecret(): void
    {
        $source = $this->source('app/controllers/UpdateController.php');

        self::assertStringNotContainsString('$request->privatekey', $source);
        self::assertStringNotContainsString("md5('update.'.AuthToken)", $source);
        self::assertStringContainsString('if(!$user->admin)', $source);
    }

    public function testGoogleOauthStateUsesATypeSafeCsprngAndConstantTimeVerification(): void
    {
        $source = $this->source('app/helpers/GoogleAuth.php');

        self::assertStringContainsString('bin2hex(random_bytes(32))', $source);
        self::assertStringContainsString("\$request->session('oauth_state', \$state)", $source);
        self::assertStringContainsString("\$request->session('oauth_state')", $source);
        self::assertStringContainsString('is_string($expectedState)', $source);
        self::assertStringContainsString('is_string($providedState)', $source);
        self::assertStringContainsString('hash_equals($expectedState, $providedState)', $source);
        self::assertDoesNotMatchRegularExpression('/->state\s*!={1,2}\s*\$request->session/', $source);
    }

    private function source(string $path): string
    {
        $source = file_get_contents($this->root.'/'.$path);

        self::assertIsString($source);

        return $source;
    }
}
