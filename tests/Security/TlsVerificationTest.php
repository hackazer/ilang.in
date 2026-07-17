<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;

final class TlsVerificationTest extends TestCase
{
    public function testFirstPartyHttpClientsNeverDisableTlsPeerVerification(): void
    {
        $root = dirname(__DIR__, 2);

        foreach (['app', 'core'] as $directory) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root.'/'.$directory, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $source = file_get_contents($file->getPathname());
                self::assertIsString($source);
                self::assertDoesNotMatchRegularExpression(
                    '/CURLOPT_SSL_VERIFYPEER\s*(?:,|=>)\s*false/',
                    $source,
                    $file->getPathname().' disables TLS peer verification.'
                );
            }
        }
    }
}
