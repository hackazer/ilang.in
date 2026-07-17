<?php

declare(strict_types=1);

namespace Helpers\Payments\Paypal;

final class CurlTransport
{
    public function __invoke(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        array $options
    ): array {
        if (strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
            throw new ApiException('PayPal transport requires HTTPS.');
        }

        $curl = curl_init($url);

        if ($curl === false) {
            throw new ApiException('PayPal transport could not initialize.');
        }

        $formattedHeaders = [];
        foreach ($headers as $name => $value) {
            $formattedHeaders[] = $name.': '.$value;
        }

        $responseHeaders = [];
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $formattedHeaders,
            CURLOPT_CONNECTTIMEOUT => (int) ($options['connect_timeout'] ?? 5),
            CURLOPT_TIMEOUT => (int) ($options['timeout'] ?? 20),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$responseHeaders): int {
                $length = strlen($line);
                $parts = explode(':', $line, 2);

                if (count($parts) === 2) {
                    $responseHeaders[trim($parts[0])] = trim($parts[1]);
                }

                return $length;
            },
        ]);

        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $responseBody = curl_exec($curl);

        if ($responseBody === false) {
            throw new ApiException('PayPal transport failed.');
        }

        if (strlen($responseBody) > (int) ($options['max_response_bytes'] ?? 2 * 1024 * 1024)) {
            throw new ApiException('PayPal response exceeded the size limit.');
        }

        return [
            'status' => (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE),
            'headers' => $responseHeaders,
            'body' => $responseBody,
        ];
    }
}
