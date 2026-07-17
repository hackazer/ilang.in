<?php

namespace Helpers;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final class GoogleAuthenticator
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private DateTimeInterface $instanceTime;

    public function __construct(
        private readonly int $passCodeLength = 6,
        private readonly int $secretLength = 10,
        ?DateTimeInterface $instanceTime = null,
        private readonly int $codePeriod = 30
    ) {
        if ($passCodeLength < 1 || $passCodeLength > 10) {
            throw new InvalidArgumentException('Pass code length must be between 1 and 10.');
        }

        if ($secretLength < 1 || $codePeriod < 1) {
            throw new InvalidArgumentException('Secret length and code period must be positive.');
        }

        $this->instanceTime = $instanceTime ?? new DateTimeImmutable();
    }

    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes($this->secretLength));
    }

    public function getCode(string $secret, DateTimeInterface|int|null $time = null): string
    {
        $timestamp = match (true) {
            $time instanceof DateTimeInterface => $time->getTimestamp(),
            is_int($time) => $time,
            default => $this->instanceTime->getTimestamp(),
        };
        $counter = intdiv($timestamp, $this->codePeriod);
        $counterBytes = pack('N2', ($counter >> 32) & 0xffffffff, $counter & 0xffffffff);
        $hash = hash_hmac('sha1', $counterBytes, $this->base32Decode($secret), true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0f;
        $value = (unpack('N', substr($hash, $offset, 4))[1] & 0x7fffffff)
            % (10 ** $this->passCodeLength);

        return str_pad((string) $value, $this->passCodeLength, '0', STR_PAD_LEFT);
    }

    public function checkCode(string $secret, string|int $code, int $discrepancy = 1): bool
    {
        if ($discrepancy < 0) {
            throw new InvalidArgumentException('Discrepancy must not be negative.');
        }

        $code = (string) $code;
        if (!preg_match('/^\d{'.$this->passCodeLength.'}$/', $code)) {
            return false;
        }

        $timestamp = $this->instanceTime->getTimestamp();
        $valid = false;

        for ($offset = -$discrepancy; $offset <= $discrepancy; $offset++) {
            $candidate = $this->getCode($secret, $timestamp + ($offset * $this->codePeriod));
            $valid = hash_equals($candidate, $code) || $valid;
        }

        return $valid;
    }

    private function base32Decode(string $value): string
    {
        $value = strtoupper(preg_replace('/[\s=]+/', '', $value) ?? '');
        if ($value === '') {
            throw new InvalidArgumentException('Secret must not be empty.');
        }

        $buffer = 0;
        $bits = 0;
        $decoded = '';

        foreach (str_split($value) as $character) {
            $position = strpos(self::BASE32_ALPHABET, $character);
            if ($position === false) {
                throw new InvalidArgumentException('Secret is not valid Base32.');
            }

            $buffer = ($buffer << 5) | $position;
            $bits += 5;

            while ($bits >= 8) {
                $bits -= 8;
                $decoded .= chr(($buffer >> $bits) & 0xff);
            }

            $buffer = $bits > 0 ? $buffer & ((1 << $bits) - 1) : 0;
        }

        return $decoded;
    }

    private function base32Encode(string $value): string
    {
        $buffer = 0;
        $bits = 0;
        $encoded = '';

        foreach (unpack('C*', $value) as $byte) {
            $buffer = ($buffer << 8) | $byte;
            $bits += 8;

            while ($bits >= 5) {
                $bits -= 5;
                $encoded .= self::BASE32_ALPHABET[($buffer >> $bits) & 0x1f];
            }

            $buffer = $bits > 0 ? $buffer & ((1 << $bits) - 1) : 0;
        }

        if ($bits > 0) {
            $encoded .= self::BASE32_ALPHABET[($buffer << (5 - $bits)) & 0x1f];
        }

        return $encoded;
    }
}
