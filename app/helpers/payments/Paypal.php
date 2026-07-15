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

        if($type == "yearly"){
			$fee = $plan->price_yearly;
			$period = "Yearly";	
		}elseif($type == "lifetime"){
			$fee = $plan->price_lifetime;
			$period = "Lifetime";	
		}else{
			$fee = $plan->price_monthly;
			$period = "Monthly";
		}
        
        $renew = $request->session('renew') ? 1 : 0;

        $options = [
            "cmd" => "_xclick",
            "business" => config('paypal')->email,
            "currency_code" => config('currency'),
            "item_name" => "{$plan->name} $type Membership (Pro)",
            "custom"  =>  json_encode(["userid" => Auth::id(), "period" => $period, "renew" => $renew, "planid" => $plan->id]),
            "amount" => $fee,
            "return" => route('dashboard'),
            "notify_url" => url("ipn"),
            "cancel_return" => route('dashboard')
        ];

        if(DEBUG){
			$payurl = "https://www.sandbox.paypal.com/cgi-bin/webscr?";
		}else{
			$payurl = "https://www.paypal.com/cgi-bin/webscr?";
		}

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
                (string) $data->period
            );
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            return \GemError::log('Paypal Error: '.$e->getMessage());
        }

        try {
            self::applyProviderTransactionOnce((string) $request->txn_id, function() use ($request, $data, $plan, $expectedAmount, &$info){

                if(!$user = DB::user()->first((int) $data->userid)){
                    throw new \RuntimeException('Paypal user does not exist.');
                }

                if((string) $data->renew === "1"){

                    if($data->period === "Yearly"){

                        $expires = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s", strtotime($user->expiration)) . " + 1 year"));
                        $info["duration"] = "1 Year";

                    }elseif($data->period === "Lifetime"){

                        $expires = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s", strtotime($user->expiration)) . " + 20 years"));
                        $info["duration"] = "20 Years";

                    }else{
                        $expires = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s", strtotime($user->expiration)) . " + 1 month"));
                        $info["duration"] = "1 Month";
                    }

                } else {

                    if($data->period === "Yearly"){

                        $expires = date("Y-m-d H:i:s", strtotime("+ 1 year"));
                        $info["duration"] = "1 Year";

                    }elseif($data->period === "Lifetime"){

                        $expires = date("Y-m-d H:i:s", strtotime("+ 20 years"));
                        $info["duration"] = "20 Years";

                    }else{
                        $expires = date("Y-m-d H:i:s", strtotime("+ 1 month"));
                        $info["duration"] = "1 Month";
                    }

                }

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
    public static function validateCompletedIpn(array $payload, object $plan, string $receiverEmail, string $currency, string $period): string {

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

        $actualCurrency = strtoupper(trim((string) ($payload['mc_currency'] ?? '')));
        $expectedCurrency = strtoupper(trim($currency));

        if($expectedCurrency === '' || $actualCurrency === '' || $actualCurrency !== $expectedCurrency){
            throw new \InvalidArgumentException('Paypal currency does not match the configured currency.');
        }

        $expectedAmount = self::planAmount($plan, $period);
        $expectedCanonical = self::canonicalAmount($expectedAmount);
        $actualCanonical = self::canonicalAmount($payload['mc_gross'] ?? null);

        if($expectedCanonical === null || $actualCanonical === null || !hash_equals($expectedCanonical, $actualCanonical)){
            throw new \InvalidArgumentException('Paypal amount does not match the server-side plan price.');
        }

        return (string) $expectedAmount;
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
