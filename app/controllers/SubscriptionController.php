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

use Core\DB;
use Core\View;
use Core\Auth;
use Core\Helper;
use Core\Request;
use Core\Plugin;
use Core\Response;
use Core\Localization;

class Subscription {

    use Traits\Payments;

    private const CHECKOUT_ATTEMPTS_SESSION = '__checkout_attempts';
    private const CHECKOUT_ATTEMPT_TTL = 900;
    /**
     * Constructor
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     */
    public function __construct(){
        if(!config('pro')) stop(404);
    }
    /**
     * Pricing Page
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public function pricing(){        
        
        Auth::check();

        if(Auth::logged() && Auth::user()->teamid){
            return \Models\Plans::notAllowed();
        }

        $plans = [];

        $default = null;

        $settings = ['monthly' => false, 'yearly' => false, 'lifetime' =>  false, 'discount' => 0];

        $hasUsedTrial = Auth::logged() && DB::payment()->where('userid', Auth::id())->whereNotNull('trial_days')->first();

        foreach(DB::plans()->where('status', 1)->where('free', 1)->find() as $plan){
            $plans[$plan->id] = [
                "free" => $plan->free,
                "name" => $plan->name,
                "description" => $plan->description,
                "icon" => $plan->icon,
                "trial" => $plan->trial_days,
                "price_monthly" => $plan->price_monthly,
                "price_yearly" => $plan->price_yearly,
                "price_lifetime" => $plan->price_lifetime,
                "urls" => $plan->numurls,
                "clicks" => $plan->numclicks,
                "retention" => $plan->retention,
                "permission" => json_decode($plan->permission)
            ];   

            if(!isset($plans[$plan->id]['permission']->channels)) {
                $plans[$plan->id]['permission']->channels = new \stdClass;
                $plans[$plan->id]['permission']->channels->enabled = false;
                $plans[$plan->id]['permission']->channels->count = '';
            }

            if(Auth::logged()){
                if(Auth::user()->planid == $plan->id){
                    $plans[$plan->id]['planurl'] = '#';
                    $plans[$plan->id]['plantext'] = e('Current');
                } else {
                    $plans[$plan->id]['planurl'] =  route('checkout', [$plan->id, 'monthly']).($plan->trial_days && !$hasUsedTrial ? '?trial=1': '');
                    $plans[$plan->id]['plantext'] = ($plan->trial_days && !$hasUsedTrial ? '<span class="mb-2 d-block">'.e('{d}-Day Free Trial', null, ['d' => $plan->trial_days ]).'</span>': '').e('Upgrade');
                }
            } else {
                $plans[$plan->id]['planurl'] =  route('checkout', [$plan->id, 'monthly']).($plan->trial_days ? '?trial=1': '');
                $plans[$plan->id]['plantext'] = ($plan->trial_days ? '<span class="mb-2 d-block">'.e('{d}-Day Free Trial', null, ['d' => $plan->trial_days ]).'</span>': '').e('Get Started');
            }
        }

        foreach(DB::plans()->where('status', 1)->where('free', 0)->orderByAsc('price_monthly')->find() as $plan){

            $discountAmount = 0;                               

            if($plan->price_lifetime && $plan->price_lifetime != "0.00") {
                $settings['lifetime'] = true;
                $default = 'lifetime';
            }             
            
            if($plan->price_yearly && $plan->price_yearly != "0.00"){
                $settings['yearly'] = true;
                $discountAmount = round((($plan->price_monthly*12)-$plan->price_yearly)*100/($plan->price_monthly*12),0);
                $default = 'yearly';
            }

            if($plan->price_monthly && $plan->price_monthly != "0.00") {
                $settings['monthly'] = true;
                $default = 'monthly';
            }

            if($discountAmount > $settings['discount']) $settings['discount'] = $discountAmount;       

            $plans[$plan->id] = [                
                "free" => $plan->free,
                "name" => $plan->name,
                "description" => $plan->description,
                "icon" => $plan->icon,
                "trial" => $plan->trial_days,
                "price_monthly" => $plan->price_monthly,
                "price_yearly" => $plan->price_yearly,
                "price_lifetime" => $plan->price_lifetime,
                "urls" => $plan->numurls,
                "clicks" => $plan->numclicks,
                "retention" => $plan->retention,
                "permission" => json_decode($plan->permission),
            ];

            if(!isset($plans[$plan->id]['permission']->channels)) {
                $plans[$plan->id]['permission']->channels = new \stdClass;
                $plans[$plan->id]['permission']->channels->enabled = false;
                $plans[$plan->id]['permission']->channels->count = '';
            }

            if(Auth::logged()){
                if(Auth::user()->planid == $plan->id && !Auth::user()->trial){
                    $plans[$plan->id]['planurl'] = '#';
                    $plans[$plan->id]['plantext'] = e('Current');
                } else {
                    $plans[$plan->id]['planurl'] =  route('checkout', [$plan->id, $default]).($plan->trial_days && !$hasUsedTrial ? '?trial=1': '');
                    $plans[$plan->id]['plantext'] = ($plan->trial_days && !$hasUsedTrial ? '<span class="mb-2 d-block">'.e('{d}-Day Free Trial', null, ['d' => $plan->trial_days ]).'</span>': '').e('Upgrade');
                }
            } else {
                $plans[$plan->id]['planurl'] =  route('checkout', [$plan->id, $default]).($plan->trial_days ? '?trial=1': '');
                $plans[$plan->id]['plantext'] = ($plan->trial_days ? '<span class="mb-2 d-block">'.e('{d}-Day Free Trial', null, ['d' => $plan->trial_days ]).'</span>': '').e('Get Started');
            }
        }
        $class = 'col-lg-3';
        $count = count($plans);
        
        if($count == 3){
            $class = 'col-md-4';
        }
        if($count <= 2){
            $class = 'col-md-6';
        }
        
        View::set('title', e('Premium Plan Pricing'));

        return View::with('pricing.index', compact('plans', 'settings', 'class', 'default'))->extend('layouts.main');
    }    
   /**
    * Checkout
    *
    * @author GemPixel <https://gempixel.com> 
    * @version 6.2
    * @param \Core\Request $request
    * @param integer $id
    * @param string $type
    * @return void
    */
    public function checkout(Request $request, int $id, string $type){        
                
        if(!Auth::logged()){
            $request->session('redirect', route('checkout', [$id, $type]));
            return Helper::redirect()->to(route('register'));
        }

        if(!in_array($type, ['monthly', 'yearly', 'lifetime'])) $type = "monthly";

        Plugin::dispatch('checkout', [$id, $type]);
        
        $user = Auth::user();

        if(!$plan = DB::plans()->where('id', Helper::RequestClean($id))->first()) return stop(404);

        if($plan->free){
            $user->pro = "0";
            $user->planid = $plan->id;
            $user->last_payment = date("Y-m-d H:i:s");
            $user->expiration = null;
			$user->save();   
                    
            return Helper::redirect()->to(route('dashboard'))->with('success', e('You have been successfully subscribed.'));
        }

        if($request->trial && $plan->trial_days){
            
            if(DB::payment()->whereNotNull('trial_days')->where('userid', $user->id)->first()){
                return Helper::redirect()->to(route('pricing'))->with("danger", e("You have already used a trial."));
            }


            $user->trial = "1";
            $user->pro = "1";
            $user->planid = $plan->id;
            $user->last_payment = date("Y-m-d H:i:s");
            $user->expiration = date("Y-m-d H:i:s", strtotime("+ {$plan->trial_days} days"));
			$user->save();
            
			$payment             = DB::payment()->create();
    		$payment->date       = Helper::dtime();
    		$payment->tid        = Helper::rand(16);
    		$payment->amount     = "0.00";
    		$payment->trial_days = $plan->trial_days;
    		$payment->userid     = $user->id;
    		$payment->status     = "Completed";
    		$payment->expiry     = date("Y-m-d H:i:s", strtotime("+ {$plan->trial_days} days"));
    		$payment->data       = null;
            $payment->save();

            Plugin::dispatch('trial.success');

            return Helper::redirect()->to(route('dashboard'))->with("success", e("Free trial has been activated! Your trial will expire in {$plan->trial_days} days."));
		}

        $user->address = json_decode($user->address);
        
        if($user->planid == $id && !$user->trial) return Helper::redirect()->to(route('dashboard'))->with('danger', e('You already subscribed to this plan. If you want to upgrade, please choose another plan.'));

        View::set('title', 'Checkout');

        $checkoutAttempt = $this->issueCheckoutAttempt((int) $user->id, $id, $type);

        \Core\View::push("<script type='text/javascript'>

        $('input[name=payment]').change(function(){
            $('.paymentOptions').hide();
            $('#'+$(this).val()).show();            
        });
        $('.paymentOptions').hide();
        $('.paymentOptions').filter(':first').show();
        $('<input>', {type: 'hidden', name: 'checkout_attempt', value: '{$checkoutAttempt}'}).appendTo('#payment-form');
        
        </script>", "custom")->tofooter();

        $name = 'price_'.$type;

        $plan->price = $plan->$name;

        if(!\Helpers\App::isExtended()){
            $processors['paypal'] = $this->processor('paypal');
        } else {
            $processors = $this->processor();
        }        
        
        $tax = null;
        $country = null;
        
        if(isset($user->address->country) && !empty($user->address->country)){
            $country = $user->address->country;           
        }else{
            $country = request()->country()['country'];
        }

        if($country && $tax = DB::taxrates()->whereRaw('countries LIKE ?', ["%{$country}%"])->first()){
            $tax->price = round($plan->price * $tax->rate / 100, 2);
        } 

        return View::with('pricing.checkout', compact('plan', 'type', 'user', 'processors', 'tax'))->extend('layouts.main');
    }
    /**
     * Process Payment
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param \Core\Request $request
     * @param integer $id
     * @param string $type
     * @return void
     */
    public function process(Request $request, int $id, string $type){

        \Gem::addMiddleware('DemoProtect');

        if(!in_array($type, ['monthly', 'yearly', 'lifetime'], true)) $type = 'monthly';

        $user = Auth::user();
        $attempt = trim((string) ($request->checkout_attempt ?? ''));

        if(!$this->claimCheckoutAttempt($attempt, (int) $user->id, $id, $type)){
            return back()->with('warning', e('This checkout attempt has already been submitted. Please reload the checkout page before trying again.'));
        }

        $couponLock = null;

        try {
            $payment = trim((string) ($request->payment ?? ''));
            $process = $this->processor($payment, 'payment');
            $subscription = null;

            if(\Helpers\App::isExtended()){
                $subscription = DB::subscription()->where('userid', $user->id)->where('status', 'Active')->first();
            }

            if(!empty(config('saleszapier'))){
                \Core\Http::url(config('saleszapier'))
                            ->with('content-type', 'application/json')
                            ->body([
                                    "type" 			=> "sales",
                                    "name"			=> user()->name,
                                    "email"			=> user()->email,
                                    "country" 	    => $request->country()['country'],
                                    "plan"			=> $id,
                                    "type"          => $type,
                                    "date"			=> date("Y-m-d H:i:s")
                            ])->post();
            }

            $couponCode = trim((string) ($request->coupon ?? ''));

            if($couponCode !== ''){
                $couponLock = $this->couponLockName($couponCode);

                if(!$this->acquireCouponLock($couponLock)){
                    $this->resetCheckoutAttempt($attempt);
                    return back()->with('warning', e('The promo code is currently being used by another checkout. Please try again.'));
                }

                $coupon = DB::coupons()->where('code', clean($couponCode))->first();
                $reservations = $coupon ? $this->couponReservations(
                    $coupon,
                    $payment,
                    (string) ($request->nowpayments_attempt ?? ''),
                    (int) $user->id,
                    $id
                ) : 0;

                if(!$coupon || !$this->couponIsValid($coupon) || !$this->couponHasCapacity((int) $coupon->used, (int) $coupon->maxuse, $reservations)){
                    $this->resetCheckoutAttempt($attempt);
                    return back()->with('danger', e('Promo code has expired. Please try again.'));
                }
            }

            $messageBefore = $_SESSION[Helper::SESSIONMESSAGE] ?? null;
            $creationFailed = false;
            $result = $this->executeCheckoutHandover(
                static fn () => call_user_func_array($process, [$request, $id, $type]),
                fn () => $this->cancelExistingSubscription($user, $subscription),
                function () use ($messageBefore, &$creationFailed): bool {
                    return $creationFailed = $this->checkoutCreationFailed($messageBefore);
                }
            );

            if($creationFailed){
                $this->resetCheckoutAttempt($attempt);
            } else {
                $this->completeCheckoutAttempt($attempt);
            }

            return $result;
        } catch(\Throwable $exception) {
            $this->resetCheckoutAttempt($attempt);
            throw $exception;
        } finally {
            if($couponLock !== null) $this->releaseCouponLock($couponLock);
        }
    }

    private function executeCheckoutHandover(callable $createCheckout, callable $cancelExisting, callable $creationFailed): mixed{
        $result = $createCheckout();

        if($creationFailed()) return $result;

        $cancelExisting();

        return $result;
    }

    private function issueCheckoutAttempt(int $userId, int $planId, string $type, ?int $now = null): string{
        $now ??= time();
        $fingerprint = $this->checkoutFingerprint($userId, $planId, $type);
        $attempts = is_array($_SESSION[self::CHECKOUT_ATTEMPTS_SESSION] ?? null)
            ? $_SESSION[self::CHECKOUT_ATTEMPTS_SESSION]
            : [];

        foreach($attempts as $token => $attempt){
            if(!is_array($attempt) || $now - (int) ($attempt['created_at'] ?? 0) >= self::CHECKOUT_ATTEMPT_TTL){
                unset($attempts[$token]);
                continue;
            }

            if(hash_equals((string) ($attempt['fingerprint'] ?? ''), $fingerprint)){
                $_SESSION[self::CHECKOUT_ATTEMPTS_SESSION] = $attempts;
                return (string) $token;
            }
        }

        $token = bin2hex(random_bytes(16));
        $attempts[$token] = [
            'fingerprint' => $fingerprint,
            'state' => 'ready',
            'created_at' => $now,
        ];
        $_SESSION[self::CHECKOUT_ATTEMPTS_SESSION] = $attempts;

        return $token;
    }

    private function claimCheckoutAttempt(string $token, int $userId, int $planId, string $type, ?int $now = null): bool{
        if(!preg_match('/^[a-f0-9]{32}$/', $token)) return false;

        $now ??= time();
        $attempt = $_SESSION[self::CHECKOUT_ATTEMPTS_SESSION][$token] ?? null;

        if(!is_array($attempt)) return false;

        if($now - (int) ($attempt['created_at'] ?? 0) >= self::CHECKOUT_ATTEMPT_TTL
            || !hash_equals((string) ($attempt['fingerprint'] ?? ''), $this->checkoutFingerprint($userId, $planId, $type))){
            unset($_SESSION[self::CHECKOUT_ATTEMPTS_SESSION][$token]);
            return false;
        }

        if(($attempt['state'] ?? null) !== 'ready') return false;

        $_SESSION[self::CHECKOUT_ATTEMPTS_SESSION][$token]['state'] = 'processing';
        return true;
    }

    private function resetCheckoutAttempt(string $token): void{
        if(($_SESSION[self::CHECKOUT_ATTEMPTS_SESSION][$token]['state'] ?? null) === 'processing'){
            $_SESSION[self::CHECKOUT_ATTEMPTS_SESSION][$token]['state'] = 'ready';
        }
    }

    private function completeCheckoutAttempt(string $token): void{
        if(($_SESSION[self::CHECKOUT_ATTEMPTS_SESSION][$token]['state'] ?? null) === 'processing'){
            $_SESSION[self::CHECKOUT_ATTEMPTS_SESSION][$token]['state'] = 'completed';
        }
    }

    private function checkoutFingerprint(int $userId, int $planId, string $type): string{
        return hash('sha256', $userId.':'.$planId.':'.$type);
    }

    private function checkoutCreationFailed(mixed $messageBefore): bool{
        $message = $_SESSION[Helper::SESSIONMESSAGE] ?? null;

        if(!is_array($message) || $message === $messageBefore) return false;

        return in_array((string) ($message['type'] ?? ''), ['danger', 'error', 'warning'], true);
    }

    private function cancelExistingSubscription(object $user, ?object $subscription): void{
        if(!$subscription || (string) ($subscription->status ?? '') !== 'Active') return;

        foreach($this->processor() as $name => $processor){
            if(!config($name) || !config($name)->enabled || !$processor['cancel']) continue;

            try {
                call_user_func_array($processor['cancel'], [$user, $subscription]);
            } catch(\Throwable $exception) {
                \GemError::log('Subscription handover cancellation failed: '.$exception::class);
            }
        }
    }

    private function couponLockName(string $code): string{
        return 'coupon:'.substr(hash('sha256', strtolower(trim($code))), 0, 57);
    }

    private function acquireCouponLock(string $lock): bool{
        try {
            $statement = DB::get_db()->prepare('SELECT GET_LOCK(:coupon_lock, 10)');
            $statement->execute(['coupon_lock' => $lock]);
            return (int) $statement->fetchColumn() === 1;
        } catch(\Throwable $exception) {
            \GemError::log('Coupon reservation lock failed: '.$exception::class);
            return false;
        }
    }

    private function releaseCouponLock(string $lock): void{
        try {
            $statement = DB::get_db()->prepare('SELECT RELEASE_LOCK(:coupon_lock)');
            $statement->execute(['coupon_lock' => $lock]);
        } catch(\Throwable $exception) {
            \GemError::log('Coupon reservation unlock failed: '.$exception::class);
        }
    }

    private function couponIsValid(object $coupon): bool{
        $validUntil = strtotime((string) ($coupon->validuntil ?? ''));

        return $validUntil !== false && time() <= strtotime(date('Y-m-d 11:59:00', $validUntil));
    }

    private function couponHasCapacity(int $used, int $maxUse, int $reservations = 0): bool{
        return $maxUse <= 0 || $used + $reservations < $maxUse;
    }

    private function couponReservations(object $coupon, string $payment, string $providerAttempt, int $userId, int $planId): int{
        if($payment !== 'nowpayments' || (int) ($coupon->maxuse ?? 0) <= 0) return 0;

        $query = DB::table('nowpayments_transactions')
            ->whereIn('status', ['pending', 'confirming', 'partial', 'paid'])
            ->whereNull('entitlement_applied_at')
            ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(`metadata`, '$.coupon_id')) AS UNSIGNED) = ?", [(int) $coupon->id]);

        if(preg_match('/^[a-f0-9]{32}$/', $providerAttempt)){
            $query->whereRaw(
                "(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`metadata`, '$.attempt_id')), '') <> ? OR `userid` <> ? OR `planid` <> ?)",
                [$providerAttempt, $userId, $planId]
            );
        }

        return (int) $query->count();
    }

    public function cryptoStatus(Request $request, string $order){
        $transaction = DB::table('nowpayments_transactions')
            ->where('order_id', clean($order))
            ->where('userid', Auth::id())
            ->first();

        if(!$transaction) return stop(404);

        $transaction->metadata = json_decode((string) $transaction->metadata);

        View::set('title', e('Crypto Payment Status'));

        return View::with('pricing.crypto-status', compact('transaction'))->extend('layouts.main');
    }

    public function cryptoStatusJson(Request $request, string $order){
        $transaction = DB::table('nowpayments_transactions')
            ->where('order_id', clean($order))
            ->where('userid', Auth::id())
            ->first();

        if(!$transaction){
            return Response::factory(['error' => 'not_found'], 404, ['Content-Type' => 'application/json'])->json();
        }

        return Response::factory([
            'status' => (string) $transaction->status,
            'provider_status' => (string) $transaction->provider_status,
            'terminal' => \Helpers\Payments\Nowpayments\Status::isTerminal((string) $transaction->status),
            'updated_at' => (string) $transaction->updated_at,
        ], 200, ['Content-Type' => 'application/json'])->json();
    }
    /**
     * Add coupon
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param \Core\Request $request
     * @param integer $id
     * @param string $type
     * @return void
     */
    public function coupon(Request $request, int $id, string $type){

        if($coupon = DB::coupons()->where("code", clean($request->code))->first()){
            
            if(strtotime("now") > strtotime(date("Y-m-d 11:59:00", strtotime($coupon->validuntil)))) return Response::factory(['error' => true, 'message' => e('Promo code has expired. Please try again.')])->json();

            if($coupon->maxuse > 0 && $coupon->used >= $coupon->maxuse) return Response::factory(['error' => true, 'message' => e('Promo code has expired. Please try again.')])->json();
            
            if(!$plan = DB::plans()->first($id)){
                return Response::factory(['error' => true, 'message' => e('Please enter a valid promo code.')])->json();
            }
            
            $name = 'price_'.$type;

            $price = $plan->$name;

            $discountedprice = round((1 - ($coupon->discount/100))*$price, 2);

            $discount = round(($coupon->discount/100)*$price, 2);
            $rate = null;
            if($request->country){
                if($tax = DB::taxrates()->whereRaw('countries LIKE ?', ["%".clean($request->country)."%"])->first()){
                    $rate =  round($discountedprice * $tax->rate / 100, 2);
                    $discountedprice = round($discountedprice * (1 + $tax->rate / 100), 2);                    
                }
            }

            return Response::factory(['error' => false, 'message' => $coupon->description, 'newprice' => Helpers\App::currency(config('currency'), $discountedprice), 'discount' =>  Helpers\App::currency(config('currency'), $discount), 'tax' => Helpers\App::currency(config('currency'), $rate)])->json();
        }
        return Response::factory(['error' => true, 'message' => e('Please enter a valid promo code.')])->json();
    }
    /**
     * Tax Rate
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.2
     * @param \Core\Request $request
     * @param integer $id
     * @param string $type
     * @return void
     */
    public function tax(Request $request, int $id, string $type){

        if(!$plan = DB::plans()->first($id)){
            return Response::factory(['error' => true, 'message' => e('Please enter a valid promo code.')])->json();
        }

        $name = 'price_'.$type;

        $price = $plan->$name;

        if($coupon = DB::coupons()->where("code", clean($request->coupon))->first()){
            
            if(strtotime("now") < strtotime(date("Y-m-d 11:59:00", strtotime($coupon->validuntil)))){
                $price = round((1 - ($coupon->discount/100))*$price, 2);
            }                    
        }

        if($request->country){
            if($tax = DB::taxrates()->whereRaw('countries LIKE ?', ["%".clean($request->country)."%"])->first()){
                $tax->price = round($price * $tax->rate / 100, 2);
                return Response::factory(['html'=>'<div class="form-group mt-4"><div class="row"><div class="col">'.$tax->name.' ('.$tax->rate.'%)</div><div class="col-auto" id="taxamount">'.\Helpers\App::currency(config('currency'), $tax->price).'</div></div></div>', 'newprice' => \Helpers\App::currency(config('currency'), $price + $tax->price)])->json();
            }
        }

        return Response::factory(['html'=>'', 'newprice' => \Helpers\App::currency(config('currency'), $price)])->json();   
    }
}
