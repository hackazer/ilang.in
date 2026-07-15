<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

final class RecurringPlan
{
    private function __construct(
        public readonly int $planId,
        public readonly string $title,
        public readonly string $term,
        public readonly string $mode,
        public readonly string $amount,
        public readonly string $currency,
        public readonly int $intervalDays,
        public readonly string $mappingKey,
        public readonly string $syncHash
    ) {
    }

    public static function define(int $planId, string $title, string $term, string $mode, string $amount, string $currency): self
    {
        if ($planId <= 0 || !in_array($term, ['monthly', 'yearly'], true) || !in_array($mode, ['email', 'custodial'], true)) {
            throw new \InvalidArgumentException('Invalid recurring plan definition.');
        }

        if ((float) $amount <= 0 || !preg_match('/^[A-Z0-9_-]{2,16}$/i', $currency)) {
            throw new \InvalidArgumentException('Invalid recurring plan amount or currency.');
        }

        $interval = $term === 'yearly' ? 365 : 30;
        $identity = implode('|', [$planId, $term, $mode]);
        $content = implode('|', [$identity, $title, $amount, strtoupper($currency), $interval]);

        return new self(
            $planId,
            trim($title),
            $term,
            $mode,
            $amount,
            strtoupper($currency),
            $interval,
            hash('sha256', $identity),
            hash('sha256', $content)
        );
    }

    /** @return array{title:string, interval_day:int, amount:string, currency:string} */
    public function payload(): array
    {
        return [
            'title' => substr($this->title.' '.ucfirst($this->term).' '.ucfirst($this->mode), 0, 191),
            'interval_day' => $this->intervalDays,
            'amount' => $this->amount,
            'currency' => strtolower($this->currency),
        ];
    }
}
