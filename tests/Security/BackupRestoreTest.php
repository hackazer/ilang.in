<?php

declare(strict_types=1);

namespace Tests\Security;

require_once dirname(__DIR__, 2).'/app/helpers/SafeBackup.php';

use Helpers\SafeBackup;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BackupRestoreTest extends TestCase
{
    private PDO $database;

    protected function setUp(): void
    {
        $this->database = new PDO('sqlite::memory:');
        $this->database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->database->exec('CREATE TABLE user (id INTEGER PRIMARY KEY, email TEXT NOT NULL)');
        $this->database->exec('CREATE TABLE settings (id INTEGER PRIMARY KEY, config TEXT NOT NULL UNIQUE, var TEXT)');
    }

    public function testDecodesTheExistingLegacyGemFormat(): void
    {
        $backup = [
            'user' => [
                ['id' => '7', 'email' => 'owner@example.com'],
            ],
            'settings' => [],
        ];

        self::assertSame($backup, SafeBackup::decode(serialize($backup)));
    }

    public function testRejectsSerializedObjectsBeforeUnserializing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('objects');

        SafeBackup::decode(serialize(['user' => [['email' => new \stdClass()]]]));
    }

    public function testRejectsSerializedReferencesBeforeUnserializing(): void
    {
        $row = ['id' => '1'];
        $rows = [&$row, &$row];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('references');

        SafeBackup::decode(serialize(['user' => $rows]));
    }

    public function testRejectsUnknownTablesUnsafeColumnsAndNestedValues(): void
    {
        foreach ([
            ['unknown_table' => []],
            ['user' => [['email` = NULL; DROP TABLE user; --' => 'x']]],
            ['user' => [['email' => ['nested']]]],
        ] as $payload) {
            try {
                SafeBackup::decode(serialize($payload));
                self::fail('Unsafe backup data was accepted.');
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function testRejectsMalformedAndOversizedContent(): void
    {
        foreach ([
            'not serialized data',
            serialize('not an array'),
            serialize(['user' => []]).'trailing content',
            str_repeat('x', SafeBackup::MAX_BYTES + 1),
        ] as $payload) {
            try {
                SafeBackup::decode($payload);
                self::fail('Malformed or oversized backup content was accepted.');
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function testRestoresAllowedRowsWithBoundValues(): void
    {
        $this->database->exec("INSERT INTO user (id, email) VALUES (1, 'old@example.com')");

        SafeBackup::restore($this->database, [
            'user' => [
                ['id' => '2', 'email' => "new@example.com'); DROP TABLE settings; --"],
            ],
        ]);

        self::assertSame(
            [['id' => 2, 'email' => "new@example.com'); DROP TABLE settings; --"]],
            $this->database->query('SELECT id, email FROM user')->fetchAll(PDO::FETCH_ASSOC)
        );
        self::assertSame('settings', $this->database->query("SELECT name FROM sqlite_master WHERE name = 'settings'")->fetchColumn());
    }

    public function testRejectsColumnsThatDoNotExistWithoutMutatingData(): void
    {
        $this->database->exec("INSERT INTO user (id, email) VALUES (1, 'original@example.com')");

        try {
            SafeBackup::restore($this->database, [
                'user' => [['id' => '2', 'admin_backdoor' => '1']],
            ]);
            self::fail('Unknown database column was accepted.');
        } catch (InvalidArgumentException) {
            self::assertSame('original@example.com', $this->database->query('SELECT email FROM user')->fetchColumn());
        }
    }

    public function testRollsBackEveryTableWhenAnInsertFails(): void
    {
        $this->database->exec("INSERT INTO user (id, email) VALUES (1, 'original@example.com')");
        $this->database->exec("INSERT INTO settings (id, config, var) VALUES (1, 'title', 'Original')");

        try {
            SafeBackup::restore($this->database, [
                'user' => [['id' => '2', 'email' => 'replacement@example.com']],
                'settings' => [
                    ['id' => '2', 'config' => 'duplicate', 'var' => 'first'],
                    ['id' => '3', 'config' => 'duplicate', 'var' => 'second'],
                ],
            ]);
            self::fail('Expected the duplicate setting to fail.');
        } catch (RuntimeException) {
            self::assertSame(
                [['id' => 1, 'email' => 'original@example.com']],
                $this->database->query('SELECT id, email FROM user')->fetchAll(PDO::FETCH_ASSOC)
            );
            self::assertSame(
                [['id' => 1, 'config' => 'title', 'var' => 'Original']],
                $this->database->query('SELECT id, config, var FROM settings')->fetchAll(PDO::FETCH_ASSOC)
            );
        }
    }

    public function testControllerUsesTheSafeTransactionalRestoreBoundary(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/controllers/admin/DashboardController.php');

        self::assertIsString($source);
        self::assertStringContainsString('SafeBackup::read', $source);
        self::assertStringContainsString('SafeBackup::restore', $source);
        self::assertStringNotContainsString('unserialize(file_get_contents($file->location))', $source);
        self::assertStringNotContainsString('DB::truncate($table)', $source);
    }
}
