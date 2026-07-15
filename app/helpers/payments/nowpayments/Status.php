<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

final class Status
{
    public const PENDING = 'pending';
    public const CONFIRMING = 'confirming';
    public const PAID = 'paid';
    public const PARTIAL = 'partial';
    public const EXPIRED = 'expired';
    public const FAILED = 'failed';
    public const REFUNDED = 'refunded';
    public const CANCELED = 'canceled';

    private const MAP = [
        'waiting' => self::PENDING,
        'waiting_pay' => self::PENDING,
        'confirming' => self::CONFIRMING,
        'confirmed' => self::CONFIRMING,
        'sending' => self::CONFIRMING,
        'partially_paid' => self::PARTIAL,
        'partial' => self::PARTIAL,
        'finished' => self::PAID,
        'paid' => self::PAID,
        'failed' => self::FAILED,
        'expired' => self::EXPIRED,
        'refunded' => self::REFUNDED,
        'canceled' => self::CANCELED,
        'cancelled' => self::CANCELED,
    ];

    private const TERMINAL = [
        self::PAID,
        self::EXPIRED,
        self::FAILED,
        self::REFUNDED,
        self::CANCELED,
    ];

    public static function normalize(?string $status): string
    {
        $key = strtolower(trim((string) $status));

        return self::MAP[$key] ?? self::FAILED;
    }

    public static function isTerminal(string $status): bool
    {
        return in_array($status, self::TERMINAL, true);
    }

    public static function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        if ($from === self::PAID && $to === self::REFUNDED) {
            return true;
        }

        return !self::isTerminal($from);
    }
}
