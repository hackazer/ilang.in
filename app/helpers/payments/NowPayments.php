<?php

declare(strict_types=1);

namespace Helpers\Payments;

use Core\DB;
use Core\Auth;
use Core\Helper;
use Core\Request;
use Helpers\Payments\Nowpayments\Client;
use Helpers\Payments\Nowpayments\Credentials;
use Helpers\Payments\Nowpayments\CurlTransport;
use Helpers\Payments\Nowpayments\DatabasePrepaidStore;
use Helpers\Payments\Nowpayments\Migrations;
use Helpers\Payments\Nowpayments\Order;
use Helpers\Payments\Nowpayments\PrepaidCommand;
use Helpers\Payments\Nowpayments\PrepaidService;
use Helpers\Payments\Nowpayments\Pricing;
use Helpers\Payments\Nowpayments\Readiness;
use Helpers\Payments\Nowpayments\EntitlementService;
use Helpers\Payments\Nowpayments\WebhookService;
use Helpers\Payments\Nowpayments\SubscriptionService;
use Helpers\Payments\Nowpayments\CustodyDepositService;

final class NowPayments
{
    public static function settings(): string
    {
        $stored = config('nowpayments') ?: Migrations::defaultSettings();
        $settings = array_replace(Migrations::defaultSettings(), Credentials::renderable($stored));
        $modes = (array) ($settings['enabled_modes'] ?? ['prepaid']);
        $enabled = self::checked($settings['enabled'] ?? '0');
        $custodialReady = self::custodialReady($stored);
        $webhookUrl = route('webhook.nowpayments');

        return '<div class="np-settings" data-nowpayments-settings>
            <div class="d-flex flex-wrap align-items-start justify-content-between mb-4">
                <div class="pr-3">
                    <h5 class="mb-1">'.e('NOWPayments').'</h5>
                    <p class="form-text mb-0">'.e('Accept crypto for prepaid terms, email renewals, or funded custodial automatic renewals. Prepaid is the safe default.').'</p>
                </div>
                <div class="form-check form-switch mt-2">
                    <input type="hidden" name="nowpayments[enabled]" value="0">
                    <input class="form-check-input" type="checkbox" id="nowpayments-enabled" name="nowpayments[enabled]" value="1" '.($enabled ? 'checked' : '').' data-toggle="togglefield" data-toggle-for="nowpayments-holder">
                    <label class="form-check-label" for="nowpayments-enabled">'.e('Enable gateway').'</label>
                </div>
            </div>
            <div id="nowpayments-holder" class="toggles '.(!$enabled ? 'd-none' : '').'">
                <div class="alert alert-info" role="status">
                    <strong>'.e('Activation stays off until payment is final.').'</strong> '.e('Waiting, confirming, partial, failed, and expired payments never grant access.').'
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="form-label" for="nowpayments-environment">'.e('Environment').'</label>
                        <select class="form-control" id="nowpayments-environment" name="nowpayments[environment]">
                            <option value="sandbox" '.(($settings['environment'] ?? '') === 'sandbox' ? 'selected' : '').'>'.e('Sandbox').'</option>
                            <option value="production" '.(($settings['environment'] ?? '') === 'production' ? 'selected' : '').'>'.e('Production').'</option>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="form-label" for="nowpayments-default-mode">'.e('Default payment mode').'</label>
                        <select class="form-control" id="nowpayments-default-mode" name="nowpayments[default_mode]">
                            '.self::modeOption('prepaid', e('Prepaid term'), (string) $settings['default_mode']).'
                            '.self::modeOption('email', e('Email renewal'), (string) $settings['default_mode']).'
                            '.self::modeOption('custodial', e('Custodial automatic renewal'), (string) $settings['default_mode']).'
                        </select>
                    </div>
                </div>
                <fieldset class="form-group">
                    <legend class="form-label mb-2">'.e('Enabled payment modes').'</legend>
                    <input type="hidden" name="nowpayments[enabled_modes][]" value="prepaid">
                    '.self::modeCheckbox('prepaid', e('Prepaid term'), e('One payment buys one monthly, yearly, or lifetime term.'), $modes, true).'
                    '.self::modeCheckbox('email', e('Email renewal'), e('NOWPayments emails a payment link each billing period.'), $modes).'
                    '.self::modeCheckbox('custodial', e('Custodial automatic renewal'), e('Charges a funded NOWPayments sub-partner balance.'), $modes, false, !$custodialReady).'
                </fieldset>
                <div class="row">
                    '.self::secretField('api-key', e('API key'), 'api_key', (bool) ($settings['api_key_configured'] ?? false)).'
                    '.self::secretField('ipn-secret', e('IPN secret'), 'ipn_secret', (bool) ($settings['ipn_secret_configured'] ?? false)).'
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="form-label" for="nowpayments-settlement-currency">'.e('Price currency').'</label>
                        <input class="form-control text-uppercase" id="nowpayments-settlement-currency" name="nowpayments[settlement_currency]" maxlength="16" value="'.self::escape((string) ($settings['settlement_currency'] ?? 'USD')).'" autocomplete="off">
                        <p class="form-text">'.e('The local plan price currency sent to NOWPayments.').'</p>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="form-label" for="nowpayments-pay-currency">'.e('Default crypto currency').'</label>
                        <input class="form-control text-uppercase" id="nowpayments-pay-currency" name="nowpayments[default_pay_currency]" maxlength="32" value="'.self::escape((string) ($settings['default_pay_currency'] ?? '')).'" placeholder="BTC" autocomplete="off">
                        <p class="form-text">'.e('Optional. Users may choose another enabled currency during prepaid checkout.').'</p>
                    </div>
                </div>
                <hr class="my-4">
                <h6>'.e('Recurring and custody credentials').'</h6>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="form-label" for="nowpayments-dashboard-email">'.e('Dashboard email').'</label>
                        <input class="form-control" type="email" id="nowpayments-dashboard-email" name="nowpayments[dashboard_email]" value="'.self::escape((string) ($settings['dashboard_email'] ?? '')).'" autocomplete="username">
                    </div>
                    '.self::secretField('dashboard-password', e('Dashboard password'), 'dashboard_password', (bool) ($settings['dashboard_password_configured'] ?? false), 'current-password').'
                </div>
                <input type="hidden" name="nowpayments[custodial_enabled]" value="0">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="nowpayments-custodial-enabled" name="nowpayments[custodial_enabled]" value="1" '.(self::checked($settings['custodial_enabled'] ?? '0') ? 'checked' : '').' '.(!$custodialReady ? 'disabled aria-disabled="true"' : '').'>
                    <label class="form-check-label" for="nowpayments-custodial-enabled">'.e('Allow custodial automatic renewal').'</label>
                </div>
                <div class="alert '.($custodialReady ? 'alert-success' : 'alert-warning').'" role="status" aria-live="polite">
                    '.($custodialReady
                        ? e('Custodial readiness requirements are configured.')
                        : e('Custodial mode remains locked until API, IPN, dashboard, HTTPS webhook, and settlement settings are complete. Save credentials first, then enable custody.')).'
                </div>
                <div class="form-group">
                    <label class="form-label" for="nowpayments-webhook">'.e('IPN callback URL').'</label>
                    <input class="form-control" id="nowpayments-webhook" value="'.self::escape($webhookUrl).'" readonly>
                </div>
            </div>
        </div>';
    }

    public static function save(Request $request): void
    {
        $setting = DB::settings()->where('config', 'nowpayments')->first();

        if (!$setting) {
            return;
        }

        $stored = json_decode((string) $setting->var, true);
        $stored = is_array($stored) ? $stored : Migrations::defaultSettings();

        if (self::checked($stored['custodial_enabled'] ?? '0') && !self::custodialReady($stored)) {
            $stored['custodial_enabled'] = '0';
            $stored['enabled_modes'] = array_values(array_diff((array) ($stored['enabled_modes'] ?? []), ['custodial']));
            $setting->var = json_encode($stored, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $setting->save();
        }
    }

    public static function checkout(): void
    {
        $settings = self::runtimeSettings();

        if (!self::checked($settings['enabled'] ?? '0')) {
            return;
        }

        $modes = (array) ($settings['enabled_modes'] ?? ['prepaid']);
        $default = in_array((string) ($settings['default_mode'] ?? ''), $modes, true)
            ? (string) $settings['default_mode']
            : 'prepaid';

        echo '<div id="nowpayments" class="paymentOptions mb-4" data-nowpayments-checkout>
            <input type="hidden" name="nowpayments_attempt" value="'.bin2hex(random_bytes(16)).'">
            <div class="border rounded-lg p-4 bg-white">
                <div class="d-flex align-items-start mb-3">
                    <div class="mr-3" aria-hidden="true"><span class="badge badge-primary p-2">CRYPTO</span></div>
                    <div><h6 class="mb-1">'.e('Pay with cryptocurrency').'</h6><p class="text-sm text-muted mb-0">'.e('Choose how this plan renews. Access starts only after final blockchain settlement.').'</p></div>
                </div>
                <div class="form-group">
                    <label for="nowpayments-mode" class="form-label">'.e('Payment schedule').'</label>
                    <select id="nowpayments-mode" name="nowpayments_mode" class="form-control" data-nowpayments-mode>
                        '.(in_array('prepaid', $modes, true) ? self::modeOption('prepaid', e('Prepaid term'), $default) : '').'
                        '.(in_array('email', $modes, true) ? self::modeOption('email', e('Email renewal'), $default) : '').'
                        '.(in_array('custodial', $modes, true) && self::checked($settings['custodial_enabled'] ?? '0') ? self::modeOption('custodial', e('Custodial automatic renewal'), $default) : '').'
                    </select>
                </div>
                <div class="form-group" data-nowpayments-prepaid>
                    <label for="nowpayments-pay-currency" class="form-label">'.e('Crypto currency').'</label>
                    <input id="nowpayments-pay-currency" name="nowpayments_pay_currency" class="form-control text-uppercase" maxlength="32" value="'.self::escape((string) ($settings['default_pay_currency'] ?? '')).'" placeholder="BTC" autocomplete="off" required>
                    <p class="form-text">'.e('Enter a currency enabled in your NOWPayments merchant account. The exact amount and address appear on the next screen.').'</p>
                </div>
                <div class="text-sm text-muted" aria-live="polite" data-nowpayments-status>'.e('No crypto payment is created until you submit checkout.').'</div>
            </div>
        </div>';
    }

    public static function payment(Request $request, int $id, string $type): mixed
    {
        $settings = self::runtimeSettings();

        if (!self::checked($settings['enabled'] ?? '0')
            || trim((string) ($settings['api_key'] ?? '')) === ''
            || trim((string) ($settings['ipn_secret'] ?? '')) === '') {
            return Helper::redirect()->back()->with('danger', e('NOWPayments is not fully configured. No payment was created.'));
        }

        $mode = strtolower(trim((string) ($request->nowpayments_mode ?? $settings['default_mode'] ?? 'prepaid')));
        $enabledModes = (array) ($settings['enabled_modes'] ?? ['prepaid']);

        if (!in_array($mode, $enabledModes, true)) {
            return Helper::redirect()->back()->with('danger', e('The selected crypto payment mode is not enabled.'));
        }

        if ($mode !== 'prepaid') {
            return self::createRecurring($request, $id, $type, $mode, $settings);
        }

        if (!$plan = DB::plans()->where('id', $id)->where('status', 1)->first()) {
            return Helper::redirect()->back()->with('danger', e('The selected plan is unavailable.'));
        }

        $user = Auth::user();
        $coupon = self::coupon($request);
        $tax = self::tax($request);

        try {
            $pricing = Pricing::forPlan($plan, $type, $coupon, $tax);
            $attemptId = preg_match('/^[a-f0-9]{32}$/', (string) $request->nowpayments_attempt)
                ? (string) $request->nowpayments_attempt
                : bin2hex(random_bytes(16));
            $order = Order::fromAttempt((int) $user->id, (int) $plan->id, $type, 'prepaid', $attemptId);
            $payCurrency = strtoupper(trim((string) ($request->nowpayments_pay_currency ?: $settings['default_pay_currency'] ?? '')));
            $priceCurrency = strtoupper(trim((string) ($settings['settlement_currency'] ?? config('currency') ?? 'USD')));
            $command = new PrepaidCommand(
                userId: (int) $user->id,
                planId: (int) $plan->id,
                term: $type,
                orderId: $order->id(),
                idempotencyKey: $order->idempotencyKey(),
                amount: $pricing->decimal(),
                priceCurrency: $priceCurrency,
                payCurrency: $payCurrency,
                callbackUrl: route('webhook.nowpayments'),
                description: (string) $plan->name.' '.ucfirst($type),
                metadata: array_replace($pricing->metadata(), ['attempt_id' => $order->attemptId()])
            );
            $client = new Client(
                new CurlTransport(),
                (string) $settings['api_key'],
                ($settings['environment'] ?? 'sandbox') === 'production' ? Client::PRODUCTION_URL : Client::SANDBOX_URL
            );
            $created = (new PrepaidService(new DatabasePrepaidStore(), $client))->create($command);

            return Helper::redirect()->to(route('checkout.crypto.status', [$created->orderId()]));
        } catch (\Throwable $exception) {
            \GemError::log('NOWPayments checkout failed: '.$exception::class);

            return Helper::redirect()->back()->with('danger', e('The crypto payment could not be created. No payment was collected.'));
        }
    }

    public static function webhook(Request $request): mixed
    {
        if (!$request->isPost()) {
            return \Core\Response::factory(['error' => 'method_not_allowed'], 405, ['Content-Type' => 'application/json'])->json();
        }

        $contentType = strtolower($request->serverString('CONTENT_TYPE'));
        $contentLength = (int) $request->serverString('CONTENT_LENGTH', '0');

        if (!str_starts_with($contentType, 'application/json') || $contentLength > 1024 * 1024) {
            return \Core\Response::factory(['error' => 'invalid_request'], 415, ['Content-Type' => 'application/json'])->json();
        }

        $raw = $request->getBody();

        if (!is_string($raw) || $raw === '' || strlen($raw) > 1024 * 1024) {
            return \Core\Response::factory(['error' => 'invalid_body'], 400, ['Content-Type' => 'application/json'])->json();
        }

        try {
            $payload = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);

            if (!is_array($payload)) {
                throw new \JsonException('Expected a JSON object.');
            }

            $settings = self::runtimeSettings();
            $result = (new WebhookService(new EntitlementService()))->handle(
                $payload,
                $request->serverString('HTTP_X_NOWPAYMENTS_SIG'),
                (string) ($settings['ipn_secret'] ?? '')
            );

            return \Core\Response::factory(['result' => $result->result], $result->httpStatus, ['Content-Type' => 'application/json'])->json();
        } catch (\JsonException) {
            return \Core\Response::factory(['error' => 'invalid_json'], 400, ['Content-Type' => 'application/json'])->json();
        } catch (\Throwable $exception) {
            \GemError::log('NOWPayments webhook failed: '.$exception::class);

            return \Core\Response::factory(['error' => 'processing_failed'], 500, ['Content-Type' => 'application/json'])->json();
        }
    }

    /** @return array<string, mixed> */
    private static function runtimeSettings(): array
    {
        $stored = config('nowpayments') ?: Migrations::defaultSettings();

        try {
            return array_replace(
                Migrations::defaultSettings(),
                Credentials::runtime($stored, static fn (string $secret): string => Helper::decrypt($secret))
            );
        } catch (\Throwable) {
            return Migrations::defaultSettings();
        }
    }

    private static function custodialReady(array|object $stored): bool
    {
        try {
            $runtime = Credentials::runtime($stored, static fn (string $secret): string => Helper::decrypt($secret));
            $runtime['callback_url'] = route('webhook.nowpayments');

            return Readiness::custodial($runtime)->ready();
        } catch (\Throwable) {
            return false;
        }
    }

    private static function createRecurring(Request $request, int $id, string $type, string $mode, array $settings): mixed
    {
        if ($type === 'lifetime') {
            return Helper::redirect()->back()->with('warning', e('Lifetime plans use prepaid crypto because they do not renew.'));
        }

        if (trim((string) ($settings['dashboard_email'] ?? '')) === '' || trim((string) ($settings['dashboard_password'] ?? '')) === '') {
            return Helper::redirect()->back()->with('danger', e('Recurring NOWPayments credentials are incomplete. No enrollment was created.'));
        }

        if ($mode === 'custodial') {
            $settings['callback_url'] = route('webhook.nowpayments');

            if (!self::checked($settings['custodial_enabled'] ?? '0') || !Readiness::custodial($settings)->ready()) {
                return Helper::redirect()->back()->with('danger', e('Custodial automatic renewal is not ready. No enrollment was created.'));
            }
        }

        if (!$plan = DB::plans()->where('id', $id)->where('status', 1)->first()) {
            return Helper::redirect()->back()->with('danger', e('The selected plan is unavailable.'));
        }

        try {
            $pricing = Pricing::forPlan($plan, $type, self::coupon($request), self::tax($request));
            $attemptId = preg_match('/^[a-f0-9]{32}$/', (string) $request->nowpayments_attempt)
                ? (string) $request->nowpayments_attempt
                : bin2hex(random_bytes(16));
            $client = new Client(
                new CurlTransport(),
                (string) $settings['api_key'],
                ($settings['environment'] ?? 'sandbox') === 'production' ? Client::PRODUCTION_URL : Client::SANDBOX_URL
            );
            $settings['callback_url'] = route('webhook.nowpayments');
            $enrollment = (new SubscriptionService($client))->enroll(Auth::user(), $plan, $type, $mode, $pricing, $settings, $attemptId);

            if ($mode === 'custodial') {
                $payCurrency = strtoupper(trim((string) ($request->nowpayments_pay_currency ?: $settings['default_pay_currency'] ?? '')));
                $deposit = (new CustodyDepositService($client))->create(Auth::user(), $plan, $type, $enrollment, $pricing, $settings, $payCurrency, $attemptId);

                return Helper::redirect()->to(route('checkout.crypto.status', [$deposit->orderId()]));
            }

            $message = $mode === 'email'
                ? e('Crypto renewal enrollment created. Check your email for the NOWPayments payment link.')
                : e('Custodial automatic renewal enrollment created. Fund the linked custody balance before the due date.');

            return Helper::redirect()->to(route('dashboard'))->with('success', $message);
        } catch (\Throwable $exception) {
            \GemError::log('NOWPayments recurring enrollment failed: '.$exception::class);

            return Helper::redirect()->back()->with('danger', e('The recurring crypto enrollment could not be created.'));
        }
    }

    public static function cancel(object $user, object $subscription): bool
    {
        $data = json_decode((string) $subscription->data);

        if (!isset($data->paymentmethod) || $data->paymentmethod !== 'nowpayments' || empty($data->provider_subscription_id)) {
            return false;
        }

        $settings = self::runtimeSettings();

        try {
            $client = new Client(new CurlTransport(), (string) $settings['api_key'], ($settings['environment'] ?? 'sandbox') === 'production' ? Client::PRODUCTION_URL : Client::SANDBOX_URL);
            $auth = $client->authenticate((string) $settings['dashboard_email'], (string) $settings['dashboard_password']);
            $jwt = (string) ($auth['token'] ?? $auth['result']['token'] ?? '');

            if ($jwt === '') return false;

            $client->cancelSubscription((string) $data->provider_subscription_id, $jwt);
            $subscription->status = 'Canceled';
            $subscription->save();
            DB::table('nowpayments_transactions')->where('provider_subscription_id', (string) $data->provider_subscription_id)->update([
                'status' => 'canceled',
                'provider_status' => 'canceled',
                'next_retry_at' => null,
                'updated_at' => Helper::dtime(),
            ]);

            return true;
        } catch (\Throwable $exception) {
            \GemError::log('NOWPayments cancellation failed: '.$exception::class);
            return false;
        }
    }

    private static function coupon(Request $request): ?object
    {
        $code = trim((string) ($request->coupon ?? ''));

        if ($code === '' || !$coupon = DB::coupons()->where('code', clean($code))->first()) {
            return null;
        }

        if (strtotime((string) $coupon->validuntil) < time()) {
            return null;
        }

        if ((int) $coupon->maxuse > 0 && (int) $coupon->used >= (int) $coupon->maxuse) {
            return null;
        }

        return $coupon;
    }

    private static function tax(Request $request): ?object
    {
        $country = trim((string) ($request->country ?? ''));

        if ($country === '') {
            return null;
        }

        return DB::taxrates()->whereRaw('countries LIKE ?', ['%'.clean($country).'%'])->first() ?: null;
    }

    private static function secretField(string $id, string $label, string $name, bool $configured, string $autocomplete = 'off'): string
    {
        return '<div class="col-md-6 form-group">
            <label class="form-label" for="nowpayments-'.$id.'">'.$label.'</label>
            <input class="form-control" type="password" id="nowpayments-'.$id.'" name="nowpayments['.$name.']" value="" autocomplete="'.$autocomplete.'">
            <p class="form-text">'.($configured ? e('Configured. Leave blank to keep the saved secret.') : e('Not configured.')).'</p>
        </div>';
    }

    private static function modeCheckbox(string $mode, string $label, string $description, array $enabled, bool $checked = false, bool $disabled = false): string
    {
        $checked = $checked || in_array($mode, $enabled, true);

        return '<div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="nowpayments-mode-'.$mode.'" name="nowpayments[enabled_modes][]" value="'.$mode.'" '.($checked ? 'checked' : '').' '.($disabled ? 'disabled aria-disabled="true"' : '').'>
            <label class="form-check-label" for="nowpayments-mode-'.$mode.'"><strong>'.$label.'</strong><span class="d-block text-muted text-sm">'.$description.'</span></label>
        </div>';
    }

    private static function modeOption(string $mode, string $label, string $selected): string
    {
        return '<option value="'.$mode.'" '.($selected === $mode ? 'selected' : '').'>'.$label.'</option>';
    }

    private static function checked(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on'], true);
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
