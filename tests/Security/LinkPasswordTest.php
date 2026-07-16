<?php

declare(strict_types=1);

namespace Tests\Security;

use Helpers\LinkPassword;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LinkPasswordTest extends TestCase
{
    public function testNewPasswordsUseModernHashes(): void
    {
        $this->requireHelper();

        $hash = LinkPassword::hash('correct horse battery staple');

        self::assertIsString($hash);
        self::assertNotSame('correct horse battery staple', $hash);
        self::assertNotSame(md5('correct horse battery staple'), $hash);
        self::assertTrue(password_verify('correct horse battery staple', $hash));
    }

    public function testEmptyPasswordsRemainUnprotected(): void
    {
        $this->requireHelper();

        self::assertNull(LinkPassword::hash(null));
        self::assertNull(LinkPassword::hash(''));
    }

    public function testNewPasswordsOverTheLimitAreRejected(): void
    {
        $this->requireHelper();

        $this->expectException(InvalidArgumentException::class);
        LinkPassword::hash(str_repeat('a', LinkPassword::MAX_LENGTH + 1));
    }

    public function testExistingModernHashesAreVerifiedWithoutAWrite(): void
    {
        $this->requireHelper();
        $link = new LinkPasswordRecord((string) password_hash('modern-secret', PASSWORD_DEFAULT));

        self::assertTrue(LinkPassword::verifyAndUpgrade('modern-secret', $link));
        self::assertSame(0, $link->saveCalls);
        self::assertTrue(password_verify('modern-secret', $link->pass));
    }

    public function testValidModernHashesWithStaleCostAreUpgraded(): void
    {
        $this->requireHelper();
        $link = new LinkPasswordRecord((string) password_hash('modern-secret', PASSWORD_BCRYPT, ['cost' => 4]));

        self::assertTrue(password_needs_rehash($link->pass, PASSWORD_DEFAULT));
        self::assertTrue(LinkPassword::verifyAndUpgrade('modern-secret', $link));
        self::assertSame(1, $link->saveCalls);
        self::assertFalse(password_needs_rehash($link->pass, PASSWORD_DEFAULT));
        self::assertTrue(password_verify('modern-secret', $link->pass));
    }

    public function testLegacyPlaintextIsAcceptedOnceThenUpgraded(): void
    {
        $this->requireHelper();
        $link = new LinkPasswordRecord('legacy-secret');

        self::assertTrue(LinkPassword::verifyAndUpgrade('legacy-secret', $link));
        self::assertSame(1, $link->saveCalls);
        self::assertNotSame('legacy-secret', $link->pass);
        self::assertTrue(password_verify('legacy-secret', $link->pass));

        self::assertTrue(LinkPassword::verifyAndUpgrade('legacy-secret', $link));
        self::assertSame(1, $link->saveCalls);
    }

    public function testLegacyMd5IsAcceptedOnceThenUpgraded(): void
    {
        $this->requireHelper();
        $legacyHash = md5('legacy-md5-secret');
        $link = new LinkPasswordRecord($legacyHash);

        self::assertTrue(LinkPassword::verifyAndUpgrade('legacy-md5-secret', $link));
        self::assertSame(1, $link->saveCalls);
        self::assertNotSame($legacyHash, $link->pass);
        self::assertTrue(password_verify('legacy-md5-secret', $link->pass));

        self::assertTrue(LinkPassword::verifyAndUpgrade('legacy-md5-secret', $link));
        self::assertSame(1, $link->saveCalls);
    }

    public function testInvalidPasswordsAreRejectedWithoutMigration(): void
    {
        $this->requireHelper();
        $records = [
            new LinkPasswordRecord((string) password_hash('modern-secret', PASSWORD_DEFAULT)),
            new LinkPasswordRecord('legacy-secret'),
            new LinkPasswordRecord(md5('legacy-md5-secret')),
        ];

        foreach ($records as $record) {
            $original = $record->pass;

            self::assertFalse(LinkPassword::verifyAndUpgrade('wrong-secret', $record));
            self::assertSame($original, $record->pass);
            self::assertSame(0, $record->saveCalls);
        }
    }

    public function testOverlongLoginAttemptsAreRejectedWithoutAWrite(): void
    {
        $this->requireHelper();
        $link = new LinkPasswordRecord('legacy-secret');

        self::assertFalse(
            LinkPassword::verifyAndUpgrade(str_repeat('a', LinkPassword::MAX_LENGTH + 1), $link)
        );
        self::assertSame('legacy-secret', $link->pass);
        self::assertSame(0, $link->saveCalls);
    }

    public function testEveryOwnedCreationAndUpdatePathHashesPasswords(): void
    {
        $root = dirname(__DIR__, 2);
        $expectedAssignments = [
            'app/traits/Links.php' => 2,
            'app/controllers/api/LinksController.php' => 2,
            'app/controllers/user/BioController.php' => 2,
        ];

        foreach ($expectedAssignments as $relativePath => $expectedCount) {
            $source = (string) file_get_contents($root.'/'.$relativePath);

            self::assertSame(
                $expectedCount,
                substr_count($source, 'LinkPassword::hash('),
                $relativePath.' must hash every owned password assignment.'
            );
        }
    }

    public function testRedirectVerificationUsesTheMigrationHelper(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/app/controllers/LinkController.php');

        self::assertStringContainsString('LinkPassword::verifyAndUpgrade($request->password, $url)', $source);
        self::assertStringNotContainsString('md5($request->password)', $source);
        self::assertStringNotContainsString('$request->password != $url->pass', $source);
    }

    private function requireHelper(): void
    {
        $path = dirname(__DIR__, 2).'/app/helpers/LinkPassword.php';

        if (is_file($path)) {
            require_once $path;
        }

        self::assertTrue(class_exists(LinkPassword::class), 'The protected-link password helper is missing.');
    }
}

final class LinkPasswordRecord
{
    public int $saveCalls = 0;

    public function __construct(public string $pass)
    {
    }

    public function save(): void
    {
        $this->saveCalls++;
    }
}
