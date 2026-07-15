<?php

declare(strict_types=1);

namespace Helpers;

use InvalidArgumentException;

final class OutboundUrl
{
    public const DEFAULT_CONNECT_TIMEOUT = 5;
    public const DEFAULT_TOTAL_TIMEOUT = 15;
    public const MAX_CONNECT_TIMEOUT = 10;
    public const MAX_TOTAL_TIMEOUT = 30;
    public const MAX_RESPONSE_BYTES = 2097152;

    private const BLOCKED_IPV4_RANGES = [
        '0.0.0.0/8',
        '10.0.0.0/8',
        '100.64.0.0/10',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '172.16.0.0/12',
        '192.0.0.0/24',
        '192.0.2.0/24',
        '192.88.99.0/24',
        '192.168.0.0/16',
        '198.18.0.0/15',
        '198.51.100.0/24',
        '203.0.113.0/24',
        '224.0.0.0/4',
        '240.0.0.0/4',
    ];

    private const BLOCKED_IPV6_RANGES = [
        '::/96',
        '::ffff:0:0/96',
        '64:ff9b::/96',
        '64:ff9b:1::/48',
        '100::/64',
        '100:0:0:1::/64',
        '2001::/32',
        '2001:2::/48',
        '2001:10::/28',
        '2001:20::/28',
        '2001:db8::/32',
        '2002::/16',
        '3fff::/20',
        '5f00::/16',
        'fc00::/7',
        'fe80::/10',
        'ff00::/8',
    ];

    private const INTERNAL_PRIVATE_RANGES = [
        '10.0.0.0/8',
        '127.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '::1/128',
        'fc00::/7',
    ];

    /**
     * @param callable(string): array<int, string>|null $resolver
     * @return array{scheme: string, host: string, port: int, addresses: array<int, string>}
     */
    public static function assertSafe(string $url, bool $allowPrivate = false, ?callable $resolver = null): array
    {
        if ($url === '' || trim($url) !== $url || strlen($url) > 8192 || preg_match('/[\x00-\x20\x7f]/', $url)) {
            throw new InvalidArgumentException('Outbound URL is malformed.');
        }

        $parts = parse_url($url);

        if (!is_array($parts)) {
            throw new InvalidArgumentException('Outbound URL is malformed.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Outbound URL scheme is not allowed.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('Outbound URL credentials are not allowed.');
        }

        $host = self::normalizeHost((string) ($parts['host'] ?? ''));
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));

        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException('Outbound URL port is invalid.');
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP)
            ? [$host]
            : ($resolver ?? [self::class, 'resolve'])($host);

        if (!is_array($addresses) || $addresses === []) {
            throw new InvalidArgumentException('Outbound URL host did not resolve.');
        }

        $validated = [];

        foreach ($addresses as $address) {
            $address = trim((string) $address, '[]');

            if (!filter_var($address, FILTER_VALIDATE_IP)) {
                throw new InvalidArgumentException('Outbound URL resolved to an invalid address.');
            }

            if (!self::isPublicIp($address)) {
                if (!$allowPrivate || !self::isInternalPrivateIp($address)) {
                    throw new InvalidArgumentException('Outbound URL resolved to a blocked address.');
                }
            }

            $validated[] = $address;
        }

        return [
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port,
            'addresses' => array_values(array_unique($validated)),
        ];
    }

    public static function isPublicIp(string $address): bool
    {
        if (!filter_var($address, FILTER_VALIDATE_IP)) {
            return false;
        }

        $isIpv4 = filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;

        if (!$isIpv4 && !self::isInRange($address, '2000::/3')) {
            return false;
        }

        $ranges = $isIpv4 ? self::BLOCKED_IPV4_RANGES : self::BLOCKED_IPV6_RANGES;

        foreach ($ranges as $range) {
            if (self::isInRange($address, $range)) {
                return false;
            }
        }

        return filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    /**
     * @param array{scheme: string, host: string, port: int, addresses: array<int, string>} $target
     * @return array<int, mixed>
     */
    public static function curlOptions(
        array $target,
        int $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT,
        int $totalTimeout = self::DEFAULT_TOTAL_TIMEOUT
    ): array {
        $addresses = array_map(
            static fn (string $address): string => str_contains($address, ':') ? '['.$address.']' : $address,
            $target['addresses']
        );

        $options = [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => self::boundedTimeout($connectTimeout, self::MAX_CONNECT_TIMEOUT),
            CURLOPT_TIMEOUT => self::boundedTimeout($totalTimeout, self::MAX_TOTAL_TIMEOUT),
            CURLOPT_MAXFILESIZE => self::MAX_RESPONSE_BYTES,
            CURLOPT_NOSIGNAL => true,
            CURLOPT_HEADER => false,
            CURLOPT_UNRESTRICTED_AUTH => false,
            CURLOPT_PROXY => '',
            CURLOPT_NOPROXY => '*',
        ];

        if (!filter_var($target['host'], FILTER_VALIDATE_IP)) {
            $options[CURLOPT_RESOLVE] = [
                $target['host'].':'.$target['port'].':'.implode(',', $addresses),
            ];
        }

        if (defined('CURLOPT_PROTOCOLS_STR')) {
            $options[CURLOPT_PROTOCOLS_STR] = 'http,https';
            $options[CURLOPT_REDIR_PROTOCOLS_STR] = 'http,https';
        } else {
            $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
            $options[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        if (defined('CURLOPT_HTTP09_ALLOWED')) {
            $options[CURLOPT_HTTP09_ALLOWED] = false;
        }

        return $options;
    }

    public static function responseWriter(
        string &$body,
        bool &$limitExceeded,
        int $maximumBytes = self::MAX_RESPONSE_BYTES
    ): callable {
        return static function ($curl, string $chunk) use (&$body, &$limitExceeded, $maximumBytes): int {
            if (strlen($body) + strlen($chunk) > $maximumBytes) {
                $limitExceeded = true;
                return 0;
            }

            $body .= $chunk;
            return strlen($chunk);
        };
    }

    /** @return array<int, string> */
    private static function resolve(string $host): array
    {
        $addresses = [];
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);

        if (is_array($records)) {
            foreach ($records as $record) {
                if (isset($record['ip'])) {
                    $addresses[] = $record['ip'];
                }

                if (isset($record['ipv6'])) {
                    $addresses[] = $record['ipv6'];
                }
            }
        }

        $ipv4Addresses = @gethostbynamel($host);

        if (is_array($ipv4Addresses)) {
            $addresses = array_merge($addresses, $ipv4Addresses);
        }

        return array_values(array_unique($addresses));
    }

    private static function normalizeHost(string $host): string
    {
        $host = trim($host, '[]');

        if ($host === '' || str_contains($host, '%') || str_ends_with($host, '.')) {
            throw new InvalidArgumentException('Outbound URL host is invalid.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return strtolower($host);
        }

        if (preg_match('/[^\x20-\x7e]/', $host)) {
            throw new InvalidArgumentException('Internationalized outbound host is unsupported.');
        }

        if (strlen($host) > 253) {
            throw new InvalidArgumentException('Outbound URL host is invalid.');
        }

        foreach (explode('.', $host) as $label) {
            if ($label === '' || strlen($label) > 63 || !preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/i', $label)) {
                throw new InvalidArgumentException('Outbound URL host is invalid.');
            }
        }

        return strtolower($host);
    }

    private static function boundedTimeout(int $timeout, int $maximum): int
    {
        return max(1, min($timeout, $maximum));
    }

    private static function isInternalPrivateIp(string $address): bool
    {
        foreach (self::INTERNAL_PRIVATE_RANGES as $range) {
            if (self::isInRange($address, $range)) {
                return true;
            }
        }

        return false;
    }

    private static function isInRange(string $address, string $range): bool
    {
        [$network, $prefixLength] = explode('/', $range, 2);
        $addressBytes = inet_pton($address);
        $networkBytes = inet_pton($network);

        if ($addressBytes === false || $networkBytes === false || strlen($addressBytes) !== strlen($networkBytes)) {
            return false;
        }

        $prefixLength = (int) $prefixLength;
        $wholeBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;

        if ($wholeBytes > 0 && substr($addressBytes, 0, $wholeBytes) !== substr($networkBytes, 0, $wholeBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xff << (8 - $remainingBits)) & 0xff;

        return (ord($addressBytes[$wholeBytes]) & $mask) === (ord($networkBytes[$wholeBytes]) & $mask);
    }
}
