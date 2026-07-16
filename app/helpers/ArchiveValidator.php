<?php

declare(strict_types=1);

namespace Helpers;

use Core\Request;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

final class ArchiveValidator
{
    public const TYPE_PLUGIN = 'plugin';
    public const TYPE_THEME = 'theme';
    public const MAX_ARCHIVE_BYTES = Request::MAX_UPLOAD_BYTES;
    public const MAX_ENTRIES = 5_000;
    public const MAX_UNCOMPRESSED_BYTES = 262_144_000;

    private const NESTED_ARCHIVE_EXTENSIONS = [
        '7z', 'bz2', 'gz', 'phar', 'rar', 'tar', 'tgz', 'xz', 'zip',
    ];

    private const FORBIDDEN_EXECUTABLE_EXTENSIONS = [
        'bash', 'bat', 'cgi', 'cmd', 'com', 'dll', 'dylib', 'exe', 'jar', 'pht',
        'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'pl', 'ps1', 'py', 'rb',
        'sh', 'so', 'zsh',
    ];

    public function __construct(
        private readonly int $maxArchiveBytes = self::MAX_ARCHIVE_BYTES,
        private readonly int $maxEntries = self::MAX_ENTRIES,
        private readonly int $maxUncompressedBytes = self::MAX_UNCOMPRESSED_BYTES,
    ) {
        if ($maxArchiveBytes < 1 || $maxEntries < 1 || $maxUncompressedBytes < 1) {
            throw new InvalidArgumentException('Archive limits must be positive.');
        }
    }

    public static function packageName(string $filename): string
    {
        if ($filename === '' || str_contains($filename, "\0") || str_contains($filename, '/') || str_contains($filename, '\\')) {
            throw new InvalidArgumentException('Package filename is unsafe.');
        }

        $name = preg_replace('/\.zip\z/i', '', $filename);

        if (!is_string($name)
            || $name === ''
            || $name === '.'
            || $name === '..'
            || str_contains($name, '..')
            || strlen($name) > 128
            || preg_match('/\A[\pL\pN][\pL\pN._ -]*\z/u', $name) !== 1) {
            throw new InvalidArgumentException('Package filename is unsafe.');
        }

        return $name;
    }

    public function validate(string $archivePath, string $type): void
    {
        if (!in_array($type, [self::TYPE_PLUGIN, self::TYPE_THEME], true)) {
            throw new InvalidArgumentException('Package type is invalid.');
        }

        $size = is_file($archivePath) ? filesize($archivePath) : false;

        if (!is_int($size) || $size < 1 || $size > $this->maxArchiveBytes || !is_readable($archivePath)) {
            throw new InvalidArgumentException('Archive size is invalid.');
        }

        $zip = new ZipArchive();

        if ($zip->open($archivePath, ZipArchive::RDONLY) !== true) {
            throw new InvalidArgumentException('Archive cannot be opened.');
        }

        try {
            if ($zip->numFiles < 1 || $zip->numFiles > $this->maxEntries) {
                throw new InvalidArgumentException('Archive contains an invalid number of entries.');
            }

            $totalBytes = 0;
            $seen = [];
            $hasConfig = false;

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index, ZipArchive::FL_UNCHANGED);

                if (!is_array($stat) || !isset($stat['name'], $stat['size']) || !is_string($stat['name'])) {
                    throw new InvalidArgumentException('Archive entry metadata is invalid.');
                }

                $name = $this->validateEntryName($stat['name']);
                $key = strtolower($name);

                if (isset($seen[$key])) {
                    throw new InvalidArgumentException('Archive contains duplicate entry paths.');
                }

                $seen[$key] = true;
                $this->rejectSymlink($zip, $index);

                $entrySize = filter_var($stat['size'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

                if ($entrySize === false || $entrySize > $this->maxUncompressedBytes - $totalBytes) {
                    throw new InvalidArgumentException('Archive expands beyond the allowed byte limit.');
                }

                $totalBytes += $entrySize;

                if (str_ends_with($name, '/')) {
                    continue;
                }

                if ($name === 'config.json') {
                    $hasConfig = true;
                }

                $this->rejectNestedArchive($name);
                $this->rejectUnexpectedExecutable($zip, $index, $name);
            }

            if (!$hasConfig) {
                throw new InvalidArgumentException('Package must contain config.json at its root.');
            }

            $config = $zip->getFromName('config.json', 1_048_577, ZipArchive::FL_UNCHANGED);

            if (!is_string($config) || strlen($config) > 1_048_576 || !is_object(json_decode($config))) {
                throw new InvalidArgumentException('Package config.json is invalid.');
            }
        } finally {
            $zip->close();
        }
    }

    public function extract(string $archivePath, string $destination, string $type): void
    {
        $this->validate($archivePath, $type);

        if (!is_dir($destination) && !mkdir($destination, 0755, true) && !is_dir($destination)) {
            throw new RuntimeException('Package destination cannot be created.');
        }

        $zip = new ZipArchive();

        if ($zip->open($archivePath, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('Archive cannot be reopened for extraction.');
        }

        try {
            if (!$zip->extractTo($destination)) {
                throw new RuntimeException('Archive extraction failed.');
            }
        } finally {
            $zip->close();
        }
    }

    private function validateEntryName(string $name): string
    {
        if ($name === ''
            || strlen($name) > 4096
            || preg_match('/[\x00-\x1f\x7f]/', $name)
            || str_starts_with($name, '/')
            || str_starts_with($name, '\\')
            || preg_match('/\A[A-Za-z]:[\\\\\/]/', $name)
            || str_contains($name, '\\')) {
            throw new InvalidArgumentException('Archive contains an unsafe path.');
        }

        $parts = explode('/', rtrim($name, '/'));

        foreach ($parts as $part) {
            if ($part === '' || $part === '.' || $part === '..') {
                throw new InvalidArgumentException('Archive contains an unsafe path.');
            }
        }

        return $name;
    }

    private function rejectSymlink(ZipArchive $zip, int $index): void
    {
        $operationsSystem = 0;
        $attributes = 0;

        if ($zip->getExternalAttributesIndex($index, $operationsSystem, $attributes)
            && $operationsSystem === ZipArchive::OPSYS_UNIX
            && (($attributes >> 16) & 0170000) === 0120000) {
            throw new InvalidArgumentException('Archive symlinks are not allowed.');
        }
    }

    private function rejectNestedArchive(string $name): void
    {
        $extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));

        if (in_array($extension, self::NESTED_ARCHIVE_EXTENSIONS, true)) {
            throw new InvalidArgumentException('Nested archives are not allowed.');
        }
    }

    private function rejectUnexpectedExecutable(ZipArchive $zip, int $index, string $name): void
    {
        $basename = strtolower((string) basename($name));
        $extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));

        if (in_array($basename, ['.htaccess', '.user.ini', 'web.config'], true)
            || in_array($extension, self::FORBIDDEN_EXECUTABLE_EXTENSIONS, true)) {
            throw new InvalidArgumentException('Archive contains an unexpected executable file.');
        }

        if ($extension === 'php') {
            return;
        }

        $stream = $zip->getStreamIndex($index, ZipArchive::FL_UNCHANGED);

        if (!is_resource($stream)) {
            throw new InvalidArgumentException('Archive entry cannot be inspected.');
        }

        $tail = '';

        try {
            while (!feof($stream)) {
                $chunk = fread($stream, 8192);

                if (!is_string($chunk)) {
                    throw new InvalidArgumentException('Archive entry cannot be inspected.');
                }

                $sample = $tail.$chunk;

                if (preg_match('/<\?(?:php|=)/i', $sample)) {
                    throw new InvalidArgumentException('Archive hides PHP code in a non-PHP file.');
                }

                $tail = substr($sample, -5);
            }
        } finally {
            fclose($stream);
        }
    }
}
