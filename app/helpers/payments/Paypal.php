<?php
/**
 * =======================================================================================
 *                           GemFramework (c) GemPixel                                     
 * ---------------------------------------------------------------------------------------
 *  This software is packaged with an exclusive framework as such distribution
 *  or modification of this framework is not allowed before prior consent from
 *  GemPixel. If you find that this framework is packaged in a software not distributed 
 *  by GemPixel or authorized parties, you must not use this software and contact GemPixel
 *  at https://gempixel.com/contact to inform them of this misuse.
 * =======================================================================================
 *
 * @package GemPixel\Premium-URL-Shortener
 * @author GemPixel (https://gempixel.com) 
 * @license https://gempixel.com/licenses
 * @link https://gempixel.com  
 */

namespace Helpers\Payments;

use Core\DB;
use Core\Auth;
use Core\Helper;
use Core\Request;
use Core\Response;

class Paypal{
    /**
     * Generate Form
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public static function settings(){
        $config = config('paypal');

        if(!$config && !isset($config->enabled)){

            $settings = DB::settings()->create();

            $settings->config = 'paypal';
            $settings->var = json_encode(['enabled' => config('pt') == 'paypal', 'email' => config('paypal_email')]);
            $settings->save();
            $config = json_decode($settings->var);
        }


        $html = '<div class="form-group">
                    <label for="paypal[enabled]" class="form-label">'.e('Paypal Basic Checkout').'</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" data-binary="true" id="paypal[enabled]" name="paypal[enabled]" value="1" '.($config->enabled ? 'checked':'').' data-toggle="togglefield" data-toggle-for="paypalholder">
                        <label class="form-check-label" for="paypal[enabled]">'.e('Enable').'</label>
                    </div>
                    <p class="form-text">'.e('Collect payments via basic paypal checkout.').'</p>
                </div>
                <div id="paypalholder" class="toggles '.(!$config->enabled ? 'd-none' : '') .'">
                    <div class="form-group">
                        <label for="paypal[email]" class="form-label">'.e('PayPal Email').'</label>
                        <input type="text" class="form-control" name="paypal[email]" placeholder="" id="paypal[email]" value="'.$config->email.'">
                        <p class="form-text">'.e('Payments will be sent to this address. Please make sure that you enable IPN and enable notification.').'</p>
                    </div>
                    <div class="form-group">
                        <label for="paypalipn" class="form-label">'.e('PayPal IPN').'</label>
                        <input type="text" class="form-control" placeholder="" id="paypalipn" value="'.route('webhook.paypal').'" disabled>
                        <p class="form-text">'.e('For more info <a href="https://developer.paypal.com/api/nvp-soap/ipn/IPNSetup/" target="_blank">click here</a>').'</p>
                    </div>
                </div>';
        return $html;
    }
    /**
     * Checkout
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public static function checkout(){
        echo '<div id="paypal" class="paymentOptions"></div>';
    }
    /**
     * Request
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param Request $request
     * @return void
     */
    public static function payment(Request $request, int $id, string $type){

        if(!config('paypal') || !config('paypal')->enabled || !config('paypal')->email) {
            \GemError::log('Payment system "PayPal" not enabled or configured.');
            return back()->with('danger', e('An error ocurred, please try again. You have not been charged.'));
        }

        if(!$plan = DB::plans()->first($id)){
            return back()->with('danger', e('An error ocurred, please try again. You have not been charged.'));
        }

        if($type == 'yearly'){
            $period = 'Yearly';
        }elseif($type == 'lifetime'){
            $period = 'Lifetime';
        }else{
            $period = 'Monthly';
        }

        $renew = $request->session('renew') ? 1 : 0;

        try {
            $coupon = self::validCoupon($request->coupon ?? null);
            $country = clean($request->country ?? '');
            $tax = $country === '' ? null : DB::taxrates()->whereRaw('countries LIKE ?', ["%{$country}%"])->first();
            $pricing = self::createPricingContext(
                $plan,
                $period,
                $coupon,
                $tax ?: null,
                (string) config('currency'),
                (int) Auth::id(),
                (bool) $renew,
                self::pricingSecret()
            );
            $custom = json_encode($pricing, JSON_THROW_ON_ERROR);

            if(strlen($custom) > 256){
                throw new \RuntimeException('Paypal pricing context exceeds the custom field limit.');
            }
        } catch (\Throwable $e) {
            \GemError::log('Paypal Error: '.$e->getMessage());
            return back()->with('danger', e('An error ocurred, please try again. You have not been charged.'));
        }

        if($coupon){
            $coupon->used++;
            $coupon->save();
        }

        $options = [
            'cmd' => '_xclick',
            'business' => config('paypal')->email,
            'currency_code' => $pricing['currency'],
            'item_name' => "{$plan->name} $type Membership (Pro)",
            'custom' => $custom,
            'amount' => $pricing['amount'],
            'return' => route('dashboard'),
            'notify_url' => url('ipn'),
            'cancel_return' => route('dashboard'),
        ];

        $payurl = DEBUG
            ? 'https://www.sandbox.paypal.com/cgi-bin/webscr?'
            : 'https://www.paypal.com/cgi-bin/webscr?';

        return Helper::redirect()->to($payurl.http_build_query($options));
    }
    /**
     * PayPal IPN
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param Request $request
     * @return void
     */
    public static function webhook(Request $request){
        $listener = new IpnListener();

        try {
            $listener->requirePostMethod();
            $verified = $listener->processIpn();   
        } catch (\Throwable $e) {
            if(http_response_code() < 400) http_response_code(503);
            \GemError::log('Paypal Error: '.$e->getMessage());
            return;
        }

        if(!$verified){
            http_response_code(400);
            return \GemError::log('Paypal Error: PayPal rejected IPN verification.');
        }

        $info = [];

        $info['paymentmethod'] = 'paypal';

        if(!$request->custom){
            http_response_code(400);
            return \GemError::log('Paypal Error: Invalid Paypal request.');
        }

        try {
            $data = json_decode((string) $request->custom, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            http_response_code(400);
            return \GemError::log('Paypal Error: Invalid Paypal custom data.');
        }

        if(!is_object($data) || !isset($data->planid, $data->userid, $data->period, $data->renew)){
            http_response_code(400);
            return \GemError::log('Paypal Error: Incomplete Paypal custom data.');
        }

        if(!$plan = DB::plans()->first((int) $data->planid)){
            http_response_code(400);
            return \GemError::log('Paypal Error: Plan does not exist.');
        }

        $paypalConfig = config('paypal');

        try {
            $expectedAmount = self::validateCompletedIpn(
                $request->all(true),
                $plan,
                is_object($paypalConfig) ? (string) ($paypalConfig->email ?? '') : '',
                (string) config('currency'),
                (string) $data->period,
                (array) $data
            );
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            return \GemError::log('Paypal Error: '.$e->getMessage());
        } catch (\Throwable $e) {
            http_response_code(500);
            return \GemError::log('Paypal Error: '.$e->getMessage());
        }

        $entitlement = self::entitlementWindow((string) $data->period);

        try {
            self::applyProviderTransactionOnce((string) $request->txn_id, function() use ($request, $data, $plan, $expectedAmount, $entitlement, &$info){

                if(!$user = DB::user()->first((int) $data->userid)){
                    throw new \RuntimeException('Paypal user does not exist.');
                }

                if((string) $data->renew === "1"){
                    $expires = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s", strtotime($user->expiration)).' '.$entitlement['modifier']));

                } else {
                    $expires = date("Y-m-d H:i:s", strtotime($entitlement['modifier']));
                }

                $info["duration"] = $entitlement['duration'];

                $info["payer_email"] = $request->payer_email;
                $info["payer_id"] = $request->payer_id;
                $info["payment_date"] = $request->payment_date;

                $payment = DB::payment()->create();

                $payment->date = Helper::dtime();
                $payment->tid = (string) $request->txn_id;
                $payment->amount = $expectedAmount;
                $payment->status = 'Completed';
                $payment->userid = (int) $data->userid;
                $payment->expiry = $expires;
                $payment->data = json_encode($info);
                $payment->save();

                $user->last_payment = Helper::dtime();
                $user->expiration = $expires;
                $user->pro = 1;
                $user->planid = $plan->id;
                $user->save();
            });
        } catch (\Throwable $e) {
            http_response_code(500);
            \GemError::log('Paypal Error: '.$e->getMessage());
            return;
        }

        http_response_code(200);
    }

    /**
     * Validate the PayPal fields that authorize entitlement and return the
     * matching server-side plan amount.
     */
    public static function validateCompletedIpn(array $payload, object $plan, string $receiverEmail, string $currency, string $period, ?array $pricingContext = null, ?string $pricingSecret = null): string {

        if(($payload['payment_status'] ?? null) !== 'Completed'){
            throw new \InvalidArgumentException('Paypal payment is not completed.');
        }

        if(trim((string) ($payload['txn_id'] ?? '')) === ''){
            throw new \InvalidArgumentException('Paypal transaction ID is missing.');
        }

        $actualReceiver = strtolower(trim((string) ($payload['receiver_email'] ?? '')));
        $expectedReceiver = strtolower(trim($receiverEmail));

        if($expectedReceiver === '' || !hash_equals($expectedReceiver, $actualReceiver)){
            throw new \InvalidArgumentException('Paypal receiver does not match the configured account.');
        }

        $signedPricing = self::validatedPricingContext($pricingContext, $plan, $period, $pricingSecret);
        $actualCurrency = strtoupper(trim((string) ($payload['mc_currency'] ?? '')));
        $expectedCurrency = $signedPricing['currency'] ?? strtoupper(trim($currency));

        if($expectedCurrency === '' || $actualCurrency === '' || $actualCurrency !== $expectedCurrency){
            throw new \InvalidArgumentException('Paypal currency does not match the configured currency.');
        }

        $expectedAmount = $signedPricing['amount'] ?? self::planAmount($plan, $period);
        $expectedCanonical = self::canonicalAmount($expectedAmount);
        $actualCanonical = self::canonicalAmount($payload['mc_gross'] ?? null);

        if($expectedCanonical === null || $actualCanonical === null || !hash_equals($expectedCanonical, $actualCanonical)){
            throw new \InvalidArgumentException('Paypal amount does not match the server-side plan price.');
        }

        return (string) $expectedAmount;
    }

    /**
     * Derive and sign the exact amount and currency sent to PayPal Basic.
     */
    public static function createPricingContext(object $plan, string $period, ?object $coupon, ?object $tax, string $currency, int $userId, bool $renew, string $secret): array {
        $baseAmount = self::canonicalAmount(self::planAmount($plan, $period));
        $planId = (int) ($plan->id ?? 0);
        $currency = strtoupper(trim($currency));
        $secret = trim($secret);

        if($baseAmount === null || $planId < 1 || $userId < 1 || !preg_match('/^[A-Z]{3}$/D', $currency) || $secret === ''){
            throw new \InvalidArgumentException('Paypal checkout pricing context is invalid.');
        }

        $amount = (float) $baseAmount;

        if($coupon){
            $discount = (float) ($coupon->discount ?? -1);

            if($discount < 0 || $discount > 100){
                throw new \InvalidArgumentException('Paypal coupon discount is invalid.');
            }

            $amount = round((1 - ($discount / 100)) * $amount, 2);
        }

        if($tax){
            $rate = (float) ($tax->rate ?? -1);

            if($rate < 0){
                throw new \InvalidArgumentException('Paypal tax rate is invalid.');
            }

            $amount = round($amount * (1 + ($rate / 100)), 2);
        }

        $context = [
            'userid' => $userId,
            'period' => $period,
            'renew' => $renew ? 1 : 0,
            'planid' => $planId,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => $currency,
        ];
        $context['signature'] = self::pricingSignature($context, $secret);

        return $context;
    }

    /**
     * Keep the legacy lifetime entitlement represented by a 20-year window.
     */
    public static function entitlementWindow(string $period): array {
        if($period === 'Yearly') return ['modifier' => '+ 1 year', 'duration' => '1 Year'];
        if($period === 'Lifetime') return ['modifier' => '+ 20 years', 'duration' => '20 Years'];
        if($period === 'Monthly') return ['modifier' => '+ 1 month', 'duration' => '1 Month'];

        throw new \InvalidArgumentException('Paypal billing period is invalid.');
    }

    private static function validatedPricingContext(?array $context, object $plan, string $period, ?string $secret): ?array {
        if(!$context || !self::hasSignedPricingContext($context)) return null;

        foreach(['userid', 'period', 'renew', 'planid', 'amount', 'currency', 'signature'] as $field){
            if(!array_key_exists($field, $context)){
                throw new \InvalidArgumentException('Paypal pricing context is incomplete.');
            }
        }

        if(!in_array($context['renew'], [0, 1, '0', '1'], true)){
            throw new \InvalidArgumentException('Paypal pricing context is invalid.');
        }

        $normalized = [
            'userid' => (int) $context['userid'],
            'period' => (string) $context['period'],
            'renew' => (int) $context['renew'],
            'planid' => (int) $context['planid'],
            'amount' => (string) $context['amount'],
            'currency' => strtoupper(trim((string) $context['currency'])),
        ];
        self::planAmount($plan, $period);
        $secret ??= self::pricingSecret();
        $secret = trim($secret);
        $expectedSignature = self::pricingSignature($normalized, $secret);
        $actualSignature = trim((string) $context['signature']);

        if(
            $secret === ''
            || $normalized['userid'] < 1
            || $normalized['planid'] !== (int) ($plan->id ?? 0)
            || $normalized['period'] !== $period
            || self::canonicalAmount($normalized['amount']) === null
            || !preg_match('/^[A-Z]{3}$/D', $normalized['currency'])
            || strlen($actualSignature) !== strlen($expectedSignature)
            || !hash_equals($expectedSignature, $actualSignature)
        ){
            throw new \InvalidArgumentException('Paypal pricing context is invalid.');
        }

        return $normalized;
    }

    private static function hasSignedPricingContext(array $context): bool {
        return array_key_exists('amount', $context)
            || array_key_exists('currency', $context)
            || array_key_exists('signature', $context);
    }

    private static function pricingSignature(array $context, string $secret): string {
        $payload = implode('|', [
            (int) ($context['userid'] ?? 0),
            (int) ($context['planid'] ?? 0),
            (string) ($context['period'] ?? ''),
            (int) ($context['renew'] ?? 0),
            (string) ($context['amount'] ?? ''),
            strtoupper(trim((string) ($context['currency'] ?? ''))),
        ]);

        return hash_hmac('sha256', $payload, trim($secret));
    }

    private static function pricingSecret(): string {
        foreach([
            defined('AuthToken') ? (string) AuthToken : '',
            defined('EncryptionToken') ? (string) EncryptionToken : '',
        ] as $secret){
            $secret = trim($secret);

            if($secret !== '' && !in_array($secret, ['__KEY__', '__ENC__'], true)) return $secret;
        }

        throw new \RuntimeException('Paypal checkout pricing secret is not configured.');
    }

    private static function validCoupon($code): ?object {
        $code = trim((string) $code);

        if($code === '') return null;

        $coupon = DB::coupons()->where('code', clean($code))->first();

        if(!$coupon) return null;
        if(strtotime('now') > strtotime(date('Y-m-d 11:59:00', strtotime($coupon->validuntil)))) return null;
        if($coupon->maxuse > 0 && $coupon->used >= $coupon->maxuse) return null;

        return $coupon;
    }

    private static function planAmount(object $plan, string $period): string {

        if($period === 'Yearly') return (string) ($plan->price_yearly ?? '');
        if($period === 'Lifetime') return (string) ($plan->price_lifetime ?? '');
        if($period === 'Monthly') return (string) ($plan->price_monthly ?? '');

        throw new \InvalidArgumentException('Paypal billing period is invalid.');
    }

    private static function canonicalAmount($amount): ?string {

        $amount = trim((string) $amount);

        if(!preg_match('/^\d+(?:\.\d{1,8})?$/D', $amount)) return null;

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $whole = ltrim($whole, '0');
        $fraction = rtrim($fraction, '0');

        if($whole === '') $whole = '0';

        return $fraction === '' ? $whole : $whole.'.'.$fraction;
    }

    /**
     * Serialize processing by PayPal transaction ID and commit the payment and
     * entitlement atomically. Replayed notifications become no-ops.
     */
    private static function applyProviderTransactionOnce(string $transactionId, callable $callback): bool {

        $pdo = DB::get_db();
        $lockName = 'paypal-ipn:'.substr(hash('sha256', $transactionId), 0, 48);
        $lock = $pdo->prepare('SELECT GET_LOCK(:lock_name, 10)');
        $lock->execute(['lock_name' => $lockName]);

        if((int) $lock->fetchColumn() !== 1){
            throw new \RuntimeException('Unable to acquire Paypal transaction lock.');
        }

        try {
            $pdo->beginTransaction();

            if(DB::payment()->where('tid', $transactionId)->first()){
                $pdo->commit();
                return false;
            }

            $callback();
            $pdo->commit();

            return true;
        } catch (\Throwable $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        } finally {
            $release = $pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
            $release->execute(['lock_name' => $lockName]);
        }
    }

}
