<?php

declare(strict_types=1);

namespace Helpers\Payments\Nowpayments;

use Core\DB;
use Core\Helper;

final class CustodyDepositService
{
    public function __construct(private readonly Client $client)
    {
    }

    public function create(object $user, object $plan, string $term, PrepaidAttempt $enrollment, PricingResult $pricing, array $settings, string $payCurrency, string $attemptId): PrepaidAttempt
    {
        $auth = $this->client->authenticate((string) $settings['dashboard_email'], (string) $settings['dashboard_password']);
        $jwt = (string) ($auth['token'] ?? $auth['result']['token'] ?? '');
        $customer = DB::table('nowpayments_customers')->where('userid', (int) $user->id)->first();

        if ($jwt === '' || !$customer || !$customer->provider_subpartner_id) {
            throw new \RuntimeException('NOWPayments custody funding context is incomplete.');
        }

        $priceCurrency = strtoupper((string) $settings['settlement_currency']);
        $payCurrency = strtoupper(trim($payCurrency));
        $estimate = $this->client->estimate([
            'amount' => $pricing->decimal(),
            'currency_from' => strtolower($priceCurrency),
            'currency_to' => strtolower($payCurrency),
        ]);
        $payAmount = (string) ($estimate['estimated_amount'] ?? $estimate['result']['estimated_amount'] ?? '');

        if (!is_numeric($payAmount) || (float) $payAmount <= 0) {
            throw new \UnexpectedValueException('NOWPayments did not return a valid custody deposit estimate.');
        }

        $order = Order::fromAttempt((int) $user->id, (int) $plan->id, $term, 'custodial-deposit', $attemptId.'-deposit');
        $attempt = $this->createPending($user, $plan, $term, $enrollment, $pricing, $order, $priceCurrency, $payCurrency, $payAmount);

        try {
            $response = $this->client->createCustomerDeposit([
                'currency' => strtolower($payCurrency),
                'amount' => $payAmount,
                'sub_partner_id' => (string) $customer->provider_subpartner_id,
                'is_fixed_rate' => false,
                'is_fee_paid_by_user' => false,
                'ipn_callback_url' => (string) $settings['callback_url'],
            ], $jwt);

            if (isset($response['result']) && is_array($response['result'])) $response = $response['result'];
            if (empty($response['payment_id'])) throw new \UnexpectedValueException('NOWPayments did not return a custody deposit payment identifier.');

            return (new DatabasePrepaidStore())->markCreated($attempt, $response);
        } catch (\Throwable $exception) {
            if ($transaction = DB::table('nowpayments_transactions')->where('id', $attempt->transactionId())->first()) {
                $transaction->status = Status::FAILED;
                $transaction->provider_status = 'request_failed';
                $transaction->next_retry_at = null;
                $transaction->updated_at = Helper::dtime();
                $transaction->save();
            }
            throw $exception;
        }
    }

    /** @return array{expected_amount:string,price_currency:string,pay_amount:string,pay_currency:string,metadata:array{fiat_target_amount:string,fiat_target_currency:string}} */
    public static function ledgerContext(string $fiatAmount, string $fiatCurrency, string $cryptoAmount, string $cryptoCurrency): array
    {
        return [
            'expected_amount' => $cryptoAmount,
            'price_currency' => strtoupper($cryptoCurrency),
            'pay_amount' => $cryptoAmount,
            'pay_currency' => strtoupper($cryptoCurrency),
            'metadata' => [
                'fiat_target_amount' => $fiatAmount,
                'fiat_target_currency' => strtoupper($fiatCurrency),
            ],
        ];
    }

    private function createPending(object $user, object $plan, string $term, PrepaidAttempt $enrollment, PricingResult $pricing, Order $order, string $priceCurrency, string $payCurrency, string $payAmount): PrepaidAttempt
    {
        $context = self::ledgerContext($pricing->decimal(), $priceCurrency, $payAmount, $payCurrency);
        $transaction = DB::table('nowpayments_transactions')->create();
        $transaction->userid = $user->id;
        $transaction->planid = $plan->id;
        $transaction->subscriptionid = $enrollment->subscriptionId();
        $transaction->order_id = $order->id();
        $transaction->idempotency_key = $order->idempotencyKey();
        $transaction->mode = 'custodial_deposit';
        $transaction->term = $term;
        $transaction->price_currency = $context['price_currency'];
        $transaction->pay_currency = $context['pay_currency'];
        $transaction->settlement_currency = $context['price_currency'];
        $transaction->expected_amount = $context['expected_amount'];
        $transaction->pay_amount = $context['pay_amount'];
        $transaction->status = Status::PENDING;
        $transaction->retry_count = 0;
        $transaction->next_retry_at = Helper::dtime('+2 minutes');
        $transaction->metadata = json_encode(array_replace($context['metadata'], [
            'purpose' => 'custody_deposit',
            'enrollment_transaction_id' => $enrollment->transactionId(),
        ]), JSON_THROW_ON_ERROR);
        $transaction->created_at = Helper::dtime();
        $transaction->updated_at = Helper::dtime();
        $transaction->save();

        return new PrepaidAttempt((int) $transaction->id, $enrollment->subscriptionId(), $order->id(), $order->idempotencyKey(), Status::PENDING);
    }
}
