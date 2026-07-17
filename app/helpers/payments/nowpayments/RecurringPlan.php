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
        public readonly string $ipnCallbackUrl,
        public readonly string $mappingKey,
        public readonly string $syncHash
    ) {
    }

    public static function define(int $planId, string $title, string $term, string $mode, string $amount, string $currency, string $ipnCallbackUrl): self
    {
        if ($planId <= 0 || !in_array($term, ['monthly', 'yearly'], true) || !in_array($mode, ['email', 'custodial'], true)) {
            throw new \InvalidArgumentException('Invalid recurring plan definition.');
        }

        if ((float) $amount <= 0 || !preg_match('/^[A-Z0-9_-]{2,16}$/i', $currency)) {
            throw new \InvalidArgumentException('Invalid recurring plan amount or currency.');
        }

        if (filter_var($ipnCallbackUrl, FILTER_VALIDATE_URL) === false
            || strtolower((string) parse_url($ipnCallbackUrl, PHP_URL_SCHEME)) !== 'https') {
            throw new \InvalidArgumentException('Recurring plan IPN callback URL must use HTTPS.');
        }

        $interval = $term === 'yearly' ? 365 : 30;
        $identity = implode('|', [$planId, $term, $mode]);
        $ipnCallbackUrl = trim($ipnCallbackUrl);
        $content = implode('|', [$identity, $title, $amount, strtoupper($currency), $interval, $ipnCallbackUrl]);

        return new self(
            $planId,
            trim($title),
            $term,
            $mode,
            $amount,
            strtoupper($currency),
            $interval,
            $ipnCallbackUrl,
            hash('sha256', $identity),
            hash('sha256', $content)
        );
    }

    /** @return array{title:string, interval_day:int, amount:string, currency:string, ipn_callback_url:string} */
    public function payload(): array
    {
        return [
            'title' => substr($this->title.' '.ucfirst($this->term).' '.ucfirst($this->mode), 0, 191),
            'interval_day' => $this->intervalDays,
            'amount' => $this->amount,
            'currency' => strtolower($this->currency),
            'ipn_callback_url' => $this->ipnCallbackUrl,
        ];
    }
}
