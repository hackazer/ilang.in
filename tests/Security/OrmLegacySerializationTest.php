<?php

declare(strict_types=1);

namespace Tests\Security;

require_once dirname(__DIR__, 2).'/core/support/ORM.class.php';

use Core\Support\IdiormResultSet;
use PHPUnit\Framework\TestCase;

final class OrmLegacySerializationProbe
{
    public static bool $wokeUp = false;

    public function __wakeup(): void
    {
        self::$wokeUp = true;
    }
}

final class OrmLegacySerializationTest extends TestCase
{
    public function testLegacyResultSetUnserializeDoesNotInstantiateObjects(): void
    {
        OrmLegacySerializationProbe::$wokeUp = false;
        $resultSet = new IdiormResultSet();

        $decoded = $resultSet->unserialize(serialize([new OrmLegacySerializationProbe()]));

        self::assertFalse(OrmLegacySerializationProbe::$wokeUp);
        self::assertIsArray($decoded);
        self::assertInstanceOf(\__PHP_Incomplete_Class::class, $decoded[0]);
    }
}
