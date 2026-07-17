<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;

final class AutomaticUpdateSecurityTest extends TestCase
{
    public function testAutomaticUpdaterUsesValidatedArchivesAndVerifiedTls(): void
    {
        $root = dirname(__DIR__, 2);
        $source = file_get_contents($root.'/app/helpers/Autoupdate.php');

        self::assertIsString($source);
        self::assertStringContainsString('ArchiveValidator::TYPE_APPLICATION', $source);
        self::assertStringNotContainsString('->extractTo(', $source);
        self::assertStringNotContainsString('update?update=true', $source);
        self::assertStringNotContainsString("md5('update.'.AuthToken)", $source);
        self::assertStringContainsString('CURLOPT_SSL_VERIFYPEER => true', $source);
        self::assertStringContainsString('CURLOPT_SSL_VERIFYHOST => 2', $source);
        self::assertStringContainsString('CURLOPT_CONNECTTIMEOUT => 10', $source);
        self::assertStringContainsString('CURLOPT_TIMEOUT => 60', $source);
    }

    public function testAdminUpdaterRunsDatabaseMigrationsInProcess(): void
    {
        $root = dirname(__DIR__, 2);
        $source = file_get_contents($root.'/app/controllers/admin/DashboardController.php');

        self::assertIsString($source);
        self::assertStringContainsString('$migrator = new \\Update();', $source);
        self::assertStringContainsString('$migrator->process();', $source);
    }
}
