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
    public const TYPE_APPLICATION = 'application';
    public const TYPE_LANGUAGE = 'language';
    public const MAX_ARCHIVE_BYTES = Request::MAX_UPLOAD_BYTES;
    public const MAX_ENTRIES = 5_000;
    public const MAX_UNCOMPRESSED_BYTES = 262_144_000;
    public const MAX_LANGUAGE_FILE_BYTES = 10_485_760;

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
        if (!in_array($type, [self::TYPE_PLUGIN, self::TYPE_THEME, self::TYPE_APPLICATION, self::TYPE_LANGUAGE], true)) {
            throw new InvalidArgumentException('Package type is invalid.');
        }

        $zip = $this->open($archivePath);

        try {
            $this->inspect($zip, $type);
        } finally {
            $zip->close();
        }
    }

    public function extract(string $archivePath, string $destination, string $type): void
    {
        if ($type === self::TYPE_LANGUAGE) {
            $this->extractLanguages($archivePath, $destination);
            return;
        }

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

    private function open(string $archivePath): ZipArchive
    {
        $size = is_file($archivePath) ? filesize($archivePath) : false;

        if (!is_int($size) || $size < 1 || $size > $this->maxArchiveBytes || !is_readable($archivePath)) {
            throw new InvalidArgumentException('Archive size is invalid.');
        }

        $zip = new ZipArchive();

        if ($zip->open($archivePath, ZipArchive::RDONLY) !== true) {
            throw new InvalidArgumentException('Archive cannot be opened.');
        }

        return $zip;
    }

    /**
     * @return array<string, string>
     */
    private function inspect(ZipArchive $zip, string $type): array
    {
        if ($zip->numFiles < 1 || $zip->numFiles > $this->maxEntries) {
            throw new InvalidArgumentException('Archive contains an invalid number of entries.');
        }

        $totalBytes = 0;
        $seen = [];
        $hasConfig = false;
        $languageFiles = [];

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

            if ($type === self::TYPE_LANGUAGE) {
                $this->inspectLanguageEntry($zip, $index, $name, $entrySize, $languageFiles);
                continue;
            }

            if (str_ends_with($name, '/')) {
                continue;
            }

            if ($name === 'config.json') {
                $hasConfig = true;
            }

            $this->rejectNestedArchive($name);
            $this->rejectUnexpectedExecutable($zip, $index, $name);
        }

        if ($type === self::TYPE_LANGUAGE) {
            if ($languageFiles === []) {
                throw new InvalidArgumentException('Language package does not contain a locale app.php file.');
            }

            return $languageFiles;
        }

        if ($type !== self::TYPE_APPLICATION && !$hasConfig) {
            throw new InvalidArgumentException('Package must contain config.json at its root.');
        }

        if ($type !== self::TYPE_APPLICATION) {
            $config = $zip->getFromName('config.json', 1_048_577, ZipArchive::FL_UNCHANGED);

            if (!is_string($config) || strlen($config) > 1_048_576 || !is_object(json_decode($config))) {
                throw new InvalidArgumentException('Package config.json is invalid.');
            }
        }

        return [];
    }

    /**
     * @param array<string, string> $languageFiles
     */
    private function inspectLanguageEntry(ZipArchive $zip, int $index, string $name, int $entrySize, array &$languageFiles): void
    {
        if (str_ends_with($name, '/')) {
            if (preg_match('/\A[A-Za-z][A-Za-z0-9_-]{0,31}\/\z/', $name) !== 1) {
                throw new InvalidArgumentException('Language package contains an unexpected directory.');
            }

            return;
        }

        if (preg_match('/\A([A-Za-z][A-Za-z0-9_-]{0,31})\/app\.php\z/', $name, $matches) !== 1) {
            $this->rejectNestedArchive($name);
            throw new InvalidArgumentException('Language package contains an unexpected file.');
        }

        $locale = $matches[1];
        $localeKey = strtolower($locale);

        if (isset($languageFiles[$localeKey])) {
            throw new InvalidArgumentException('Language package contains duplicate locales.');
        }

        if ($entrySize > self::MAX_LANGUAGE_FILE_BYTES) {
            throw new InvalidArgumentException('Language file exceeds the allowed byte limit.');
        }

        $contents = $zip->getFromIndex($index, self::MAX_LANGUAGE_FILE_BYTES + 1, ZipArchive::FL_UNCHANGED);

        if (!is_string($contents) || strlen($contents) !== $entrySize) {
            throw new InvalidArgumentException('Language file cannot be read safely.');
        }

        $this->validateLanguageFile($contents, $locale);
        $languageFiles[$localeKey] = $contents;
    }

    private function validateLanguageFile(string $contents, string $locale): void
    {
        try {
            $tokens = token_get_all($contents, TOKEN_PARSE);
        } catch (\ParseError $exception) {
            throw new InvalidArgumentException('Language file contains invalid PHP.', 0, $exception);
        }

        $openTags = 0;
        $returns = 0;
        $allowedTokens = [
            T_ARRAY,
            T_COMMENT,
            T_CONSTANT_ENCAPSED_STRING,
            T_DOC_COMMENT,
            T_DOUBLE_ARROW,
            T_LNUMBER,
            T_DNUMBER,
            T_OPEN_TAG,
            T_RETURN,
            T_WHITESPACE,
        ];

        foreach ($tokens as $token) {
            if (is_string($token)) {
                if (!str_contains('[](),;', $token)) {
                    throw new InvalidArgumentException('Language file contains executable PHP.');
                }

                continue;
            }

            [$id, $text] = $token;

            if ($id === T_OPEN_TAG) {
                $openTags++;
            } elseif ($id === T_RETURN) {
                $returns++;
            } elseif ($id === T_STRING) {
                if (!in_array(strtolower($text), ['false', 'null', 'true'], true)) {
                    throw new InvalidArgumentException('Language file contains executable PHP.');
                }

                continue;
            }

            if (!in_array($id, $allowedTokens, true) && $id !== T_STRING) {
                throw new InvalidArgumentException('Language file contains executable PHP.');
            }
        }

        if ($openTags !== 1 || $returns !== 1) {
            throw new InvalidArgumentException('Language file must return one static array.');
        }

        $temporary = tempnam(sys_get_temp_dir(), 'language-validate-');

        if (!is_string($temporary)) {
            throw new RuntimeException('Language file cannot be staged for validation.');
        }

        try {
            chmod($temporary, 0600);

            if (file_put_contents($temporary, $contents, LOCK_EX) !== strlen($contents)) {
                throw new RuntimeException('Language file cannot be staged for validation.');
            }

            $data = (static function (string $path): mixed {
                return include $path;
            })($temporary);
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }

        if (!is_array($data)
            || !isset($data['code'], $data['region'], $data['name'], $data['author'], $data['link'], $data['date'], $data['rtl'], $data['data'])
            || !is_string($data['code'])
            || strcasecmp($data['code'], $locale) !== 0
            || !is_string($data['region'])
            || !is_string($data['name'])
            || !is_string($data['author'])
            || !is_string($data['link'])
            || !is_string($data['date'])
            || !is_bool($data['rtl'])
            || !is_array($data['data'])) {
            throw new InvalidArgumentException('Language file metadata is invalid.');
        }

        foreach ($data['data'] as $source => $translation) {
            if (!is_string($source) || !is_string($translation)) {
                throw new InvalidArgumentException('Language translations must contain string keys and values.');
            }
        }
    }

    private function extractLanguages(string $archivePath, string $destination): void
    {
        if (is_link($destination) || (file_exists($destination) && !is_dir($destination))) {
            throw new RuntimeException('Language destination is unsafe.');
        }

        if (!is_dir($destination) && !mkdir($destination, 0755, true) && !is_dir($destination)) {
            throw new RuntimeException('Language destination cannot be created.');
        }

        $zip = $this->open($archivePath);

        try {
            $languageFiles = $this->inspect($zip, self::TYPE_LANGUAGE);
        } finally {
            $zip->close();
        }

        $stage = dirname($destination).'/.'.basename($destination).'-language-stage-'.bin2hex(random_bytes(12));

        if (!mkdir($stage, 0700)) {
            throw new RuntimeException('Private language staging directory cannot be created.');
        }

        try {
            $contentRoot = $stage.'/content';
            $backupRoot = $stage.'/backup';

            if (!mkdir($contentRoot, 0700) || !mkdir($backupRoot, 0700)) {
                throw new RuntimeException('Private language staging directory cannot be prepared.');
            }

            foreach ($languageFiles as $locale => $contents) {
                $localeStage = $contentRoot.'/'.$locale;

                if (!mkdir($localeStage, 0700)) {
                    throw new RuntimeException('Language locale cannot be staged.');
                }

                $file = $localeStage.'/app.php';

                if (file_put_contents($file, $contents, LOCK_EX) !== strlen($contents)) {
                    throw new RuntimeException('Language file cannot be staged.');
                }

                chmod($file, 0644);
                chmod($localeStage, 0755);
            }

            $this->publishLanguages($contentRoot, $backupRoot, $destination, array_keys($languageFiles));
        } finally {
            $this->remove($stage);
        }
    }

    /**
     * @param list<string> $locales
     */
    private function publishLanguages(string $contentRoot, string $backupRoot, string $destination, array $locales): void
    {
        sort($locales, SORT_STRING);
        $backedUp = [];
        $published = [];
        $createdDirectories = [];

        try {
            foreach ($locales as $locale) {
                $targetDirectory = $destination.'/'.$locale;

                if (is_link($targetDirectory) || (file_exists($targetDirectory) && !is_dir($targetDirectory))) {
                    throw new RuntimeException('Existing language destination is unsafe.');
                }

                if (!is_dir($targetDirectory)) {
                    if (!mkdir($targetDirectory, 0755)) {
                        throw new RuntimeException('Language destination cannot be created.');
                    }

                    $createdDirectories[] = $targetDirectory;
                }

                $target = $targetDirectory.'/app.php';

                if (is_link($target) || (file_exists($target) && !is_file($target))) {
                    throw new RuntimeException('Existing language file is unsafe.');
                }

                if (is_file($target)) {
                    $backup = $backupRoot.'/'.$locale.'.php';

                    if (!rename($target, $backup)) {
                        throw new RuntimeException('Existing language cannot be staged for replacement.');
                    }

                    $backedUp[$locale] = $backup;
                }
            }

            foreach ($locales as $locale) {
                $target = $destination.'/'.$locale.'/app.php';

                if (!rename($contentRoot.'/'.$locale.'/app.php', $target)) {
                    throw new RuntimeException('Language cannot be published.');
                }

                $published[] = $locale;
            }
        } catch (\Throwable $exception) {
            foreach (array_reverse($published) as $locale) {
                $this->remove($destination.'/'.$locale.'/app.php');
            }

            foreach (array_reverse($backedUp, true) as $locale => $backup) {
                $target = $destination.'/'.$locale.'/app.php';

                if (!file_exists($target) && !rename($backup, $target)) {
                    throw new RuntimeException('Language publication failed and rollback was incomplete.', 0, $exception);
                }
            }

            foreach (array_reverse($createdDirectories) as $directory) {
                if (is_dir($directory) && (scandir($directory) ?: []) === ['.', '..']) {
                    rmdir($directory);
                }
            }

            throw new RuntimeException('Language publication failed.', 0, $exception);
        }
    }

    private function remove(string $path): void
    {
        if (is_file($path) || is_link($path)) {
            unlink($path);
            return;
        }

        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $this->remove($path.'/'.$entry);
        }

        rmdir($path);
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
