<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use Core\DB;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/core/support/ORM.class.php';
require_once dirname(__DIR__, 2).'/core/DB.class.php';

final class IdempotentSchemaAlterTest extends TestCase
{
    public function testAlterWithNoOperationsIsASuccessfulNoOp(): void
    {
        self::assertTrue(DB::alter('already_current', static function (DB $table): void {
            // Every conditional migration is already satisfied.
        }));
    }

    public function testSchemaErrorsUseResolvableGlobalBoundaries(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/core/DB.class.php');

        self::assertIsString($source);
        self::assertStringNotContainsString('catch(Exception ', $source);
        self::assertStringNotContainsString("\n\t\t\tGemError::trigger", $source);
    }
}
