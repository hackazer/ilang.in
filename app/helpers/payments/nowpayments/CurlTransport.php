<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

final class CurlTransport implements Transport
{
    public function request(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        array $options
    ): TransportResponse {
        $curl = curl_init($url);

        if ($curl === false) {
            throw new ApiException('NOWPayments transport could not initialize.');
        }

        $formattedHeaders = [];

        foreach ($headers as $name => $value) {
            $formattedHeaders[] = $name.': '.$value;
        }

        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $formattedHeaders,
            CURLOPT_CONNECTTIMEOUT => (int) ($options['connect_timeout'] ?? 5),
            CURLOPT_TIMEOUT => (int) ($options['timeout'] ?? 15),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ]);

        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $responseBody = curl_exec($curl);

        if ($responseBody === false) {
            throw new ApiException('NOWPayments transport failed.');
        }

        $maxBytes = (int) ($options['max_response_bytes'] ?? 2 * 1024 * 1024);

        if (strlen($responseBody) > $maxBytes) {
            throw new ApiException('NOWPayments response exceeded the size limit.');
        }

        return new TransportResponse((int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE), [], $responseBody);
    }
}
