<?php

declare(strict_types=1);

namespace Helpers;

use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

final class SafeBackup
{
    public const MAX_BYTES = 52_428_800;

    private const MAX_ROWS = 250_000;
    private const MAX_COLUMNS_PER_ROW = 256;

    private const ALLOWED_TABLES = [
        'ads',
        'affiliates',
        'bundle',
        'coupons',
        'domains',
        'faqs',
        'overlay',
        'page',
        'payment',
        'pixels',
        'plans',
        'posts',
        'profiles',
        'qrs',
        'reports',
        'settings',
        'splash',
        'stats',
        'subscription',
        'taxrates',
        'url',
        'user',
    ];

    public static function read(string $path, ?int $reportedSize = null): array
    {
        if ($reportedSize !== null && ($reportedSize < 1 || $reportedSize > self::MAX_BYTES)) {
            throw new InvalidArgumentException('The backup file size is invalid.');
        }

        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException('The backup file cannot be read.');
        }

        $size = filesize($path);

        if ($size === false || $size < 1 || $size > self::MAX_BYTES) {
            throw new InvalidArgumentException('The backup file size is invalid.');
        }

        $stream = fopen($path, 'rb');

        if ($stream === false) {
            throw new InvalidArgumentException('The backup file cannot be read.');
        }

        try {
            $contents = stream_get_contents($stream, self::MAX_BYTES + 1);
        } finally {
            fclose($stream);
        }

        if (!is_string($contents) || strlen($contents) !== $size) {
            throw new InvalidArgumentException('The backup file could not be read completely.');
        }

        return self::decode($contents);
    }

    public static function decode(string $contents): array
    {
        $length = strlen($contents);

        if ($length < 1 || $length > self::MAX_BYTES) {
            throw new InvalidArgumentException('The backup content size is invalid.');
        }

        self::rejectDangerousSerializationTokens($contents);

        set_error_handler(static function (int $severity, string $message): never {
            throw new InvalidArgumentException('The backup contains malformed serialized data.');
        });

        try {
            $data = unserialize($contents, ['allowed_classes' => false]);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('The backup contains malformed serialized data.', 0, $exception);
        } finally {
            restore_error_handler();
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException('The backup root must be a table map.');
        }

        self::validate($data);

        return $data;
    }

    public static function restore(PDO $database, array $data, string $prefix = ''): int
    {
        self::validate($data);
        self::assertIdentifierPrefix($prefix);

        if ($database->inTransaction()) {
            throw new RuntimeException('A backup restore cannot run inside another transaction.');
        }

        $schemas = [];

        foreach ($data as $table => $rows) {
            $physicalTable = $prefix.$table;
            $schemas[$table] = self::databaseColumns($database, $physicalTable);

            foreach ($rows as $row) {
                foreach (array_keys($row) as $column) {
                    if (!isset($schemas[$table][$column])) {
                        throw new InvalidArgumentException('The backup contains a column that is not present in the current database schema.');
                    }
                }
            }
        }

        if ($data === []) {
            return 0;
        }

        $restored = 0;

        try {
            if (!$database->beginTransaction()) {
                throw new RuntimeException('The database did not start the restore transaction.');
            }

            foreach (array_keys($data) as $table) {
                $physicalTable = $prefix.$table;
                $database->exec('DELETE FROM '.self::quoteIdentifier($physicalTable));
            }

            foreach ($data as $table => $rows) {
                $physicalTable = $prefix.$table;
                $statements = [];

                foreach ($rows as $row) {
                    $columns = array_keys($row);
                    $statementKey = implode("\0", $columns);

                    if (!isset($statements[$statementKey])) {
                        $quotedColumns = array_map(self::quoteIdentifier(...), $columns);
                        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                        $sql = 'INSERT INTO '.self::quoteIdentifier($physicalTable)
                            .' ('.implode(', ', $quotedColumns).') VALUES ('.$placeholders.')';
                        $statements[$statementKey] = $database->prepare($sql);
                    }

                    $statements[$statementKey]->execute(array_values($row));
                    $restored++;
                }
            }

            if (!$database->commit()) {
                throw new RuntimeException('The database did not commit the restore transaction.');
            }
        } catch (Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }

            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            throw new RuntimeException('The database rejected the backup restore.', 0, $exception);
        }

        return $restored;
    }

    private static function validate(array $data): void
    {
        $allowedTables = array_fill_keys(self::ALLOWED_TABLES, true);
        $rowCount = 0;

        foreach ($data as $table => $rows) {
            if (!is_string($table) || !isset($allowedTables[$table])) {
                throw new InvalidArgumentException('The backup contains an unsupported table.');
            }

            if (!is_array($rows) || !array_is_list($rows)) {
                throw new InvalidArgumentException('Backup table rows must be a list.');
            }

            $rowCount += count($rows);

            if ($rowCount > self::MAX_ROWS) {
                throw new InvalidArgumentException('The backup contains too many rows.');
            }

            foreach ($rows as $row) {
                if (!is_array($row) || $row === [] || array_is_list($row) || count($row) > self::MAX_COLUMNS_PER_ROW) {
                    throw new InvalidArgumentException('Each backup row must be a non-empty column map.');
                }

                foreach ($row as $column => $value) {
                    if (!is_string($column) || !self::isIdentifier($column)) {
                        throw new InvalidArgumentException('The backup contains an unsafe column identifier.');
                    }

                    if (!is_null($value) && !is_scalar($value)) {
                        throw new InvalidArgumentException('Backup row values must be scalar or null.');
                    }

                    if (is_float($value) && !is_finite($value)) {
                        throw new InvalidArgumentException('Backup row numbers must be finite.');
                    }
                }
            }
        }
    }

    private static function rejectDangerousSerializationTokens(string $contents): void
    {
        $length = strlen($contents);

        for ($offset = 0; $offset < $length; $offset++) {
            $token = $contents[$offset];

            if ($token === 's' && ($contents[$offset + 1] ?? null) === ':') {
                if (!preg_match('/\Gs:(\d+):"/', $contents, $match, 0, $offset)) {
                    throw new InvalidArgumentException('The backup contains malformed serialized data.');
                }

                $valueOffset = $offset + strlen($match[0]);
                $valueLength = (int) $match[1];
                $terminatorOffset = $valueOffset + $valueLength;

                if (substr($contents, $terminatorOffset, 2) !== '";') {
                    throw new InvalidArgumentException('The backup contains malformed serialized data.');
                }

                $offset = $terminatorOffset + 1;
                continue;
            }

            if (in_array($token, ['O', 'C', 'E'], true) && ($contents[$offset + 1] ?? null) === ':') {
                throw new InvalidArgumentException('Serialized objects are not allowed in backup files.');
            }

            if (in_array($token, ['R', 'r'], true) && ($contents[$offset + 1] ?? null) === ':') {
                throw new InvalidArgumentException('Serialized references are not allowed in backup files.');
            }
        }
    }

    private static function databaseColumns(PDO $database, string $table): array
    {
        if (!self::isIdentifier($table)) {
            throw new InvalidArgumentException('The database table identifier is unsafe.');
        }

        $driver = (string) $database->getAttribute(PDO::ATTR_DRIVER_NAME);
        $quotedTable = self::quoteIdentifier($table);

        if ($driver === 'mysql') {
            $rows = $database->query('SHOW COLUMNS FROM '.$quotedTable)->fetchAll(PDO::FETCH_ASSOC);
            $columns = array_column($rows, 'Field');
        } elseif ($driver === 'sqlite') {
            $rows = $database->query('PRAGMA table_info('.$quotedTable.')')->fetchAll(PDO::FETCH_ASSOC);
            $columns = array_column($rows, 'name');
        } else {
            throw new RuntimeException('The database driver is not supported for backup restores.');
        }

        if ($columns === []) {
            throw new InvalidArgumentException('A backup table is not present in the current database schema.');
        }

        return array_fill_keys($columns, true);
    }

    private static function assertIdentifierPrefix(string $prefix): void
    {
        if ($prefix !== '' && !preg_match('/\A[A-Za-z_][A-Za-z0-9_]{0,31}\z/', $prefix)) {
            throw new InvalidArgumentException('The database table prefix is unsafe.');
        }
    }

    private static function isIdentifier(string $identifier): bool
    {
        return preg_match('/\A[A-Za-z_][A-Za-z0-9_]{0,63}\z/', $identifier) === 1;
    }

    private static function quoteIdentifier(string $identifier): string
    {
        if (!self::isIdentifier($identifier)) {
            throw new InvalidArgumentException('The database identifier is unsafe.');
        }

        return '`'.$identifier.'`';
    }
}
