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
use Core\View;
use Helpers\Payments\Paypal\ApiException;
use Helpers\Payments\Paypal\Client;
use Helpers\Payments\Paypal\CurlTransport;

class PaypalApi{
    /**
     * Generate Payment Form
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public static function settings(){

        $config = config('paypalapi');

        if(!$config && !isset($config->enabled)){
    
            $settings = DB::settings()->create();

            $settings->config = 'paypalapi';
            $settings->var = json_encode(['enabled' => config('pt') == 'paypalapi', 'secret' => config('ppprivate'), 'public' => config('pppublic')]);
            $settings->save();

            $config = json_decode($settings->var);
        }

        $html = '<div class="form-group">
                    <label for="paypalapi[enabled]" class="form-label">'.e('Paypal API Payments').'</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" data-binary="true" id="paypalapi[enabled]" name="paypalapi[enabled]" value="1" '.($config->enabled ? 'checked':'').' data-toggle="togglefield" data-toggle-for="paypalapiholder">
                        <label class="form-check-label" for="paypalapi[enabled]">'.e('Enable').'</label>
                    </div>
                    <p class="form-text">'.e('Collect payments securely with PayPal API.').'</p>
                </div>
                <div id="paypalapiholder" class="toggles '.(!$config->enabled ? 'd-none' : '') .'">
                    <div class="form-group">
                        <label for="paypalapi[public]" class="form-label">'.e('Client ID').'</label>
                        <input type="text" class="form-control" name="paypalapi[public]" placeholder="" id="paypalapi[public]" value="'.$config->public.'">
                        <p class="form-text">'.e('Please enter your live client ID.').'</p>
                    </div>
                    <div class="form-group">
                        <label for="paypalapi[secret]" class="form-label">'.e('Client Secret Key').'</label>
                        <input type="text" class="form-control" name="paypalapi[secret]" placeholder=""  id="paypalapi[secret]" value="'.$config->secret.'">
                        <p class="form-text">'.e('Please enter your live client secret.').'</p>
                    </div>                        
                </div>';
        View::push("<script>$('#paypalapi\\\[enabled\\\]').change(function(){ 
            $('.alert-danger').remove();
            if($(this).is(':checked') && $('#paypal\\\[enabled\\\]').is(':checked')){
                $('#paypalapi\\\[enabled\\\]').parents('.form-group').before('<div class=\"alert alert-danger p-3\">".e('You cannot enable both basic paypal and paypal api at the same time. You must choose one.')."</div>');
            }
         })</script>", 'custom')->tofooter();
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
        echo '<div id="paypalapi" class="paymentOptions"></div>';
    }
    /**
     * Payment
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public static function payment(Request $request, int $id, string $type){

        if(!config('paypalapi') || !config('paypalapi')->enabled || !config('paypalapi')->public || !config('paypalapi')->secret) {
            
            \GemError::log('Payment system "PaypalAPI" not enabled or configured.');

            return back()->with('danger', e('An error ocurred, please try again. You have not been charged.'));
        }

        if(!$plan = DB::plans()->first($id)){
			return back()->with('danger', e('An error ocurred, please try again. You have not been charged.'));
	  	}			

        $plan->data = json_decode($plan->data);
		
        $user = Auth::user();

        $client = self::client();

        $coupon = null;
        if($request->coupon && $coupon = DB::coupons()->where('code', clean($request->coupon))->first()){
            
            $valid = true;

            if(strtotime("now") > strtotime(date("Y-m-d 11:59:00", strtotime($coupon->validuntil)))) $valid = false;

            if($coupon->maxuse > 0 && $coupon->used >= $coupon->maxuse) $valid = false;

			if($valid) {	
				$coupon->used++;
				$coupon->save();
				$coupon->data = json_decode($coupon->data);
			}
		}
        
        $tax = DB::taxrates()->whereRaw('countries LIKE ?', ["%".clean($request->country)."%"])->first();
                
        if($type == 'lifetime'){
            
            $price = isset($coupon) ? round((1 - ($coupon->discount / 100)) * $plan->price_lifetime, 2) : $plan->price_lifetime;

            if($tax){
                $price = round($price * (1+($tax->rate / 100)), 2);
            }

            $amount = self::money($price);
            $currency = strtoupper(trim((string) config('currency')));

            try {
                $order = $client->createOrder([
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'description' => 'userid:'.$user->id,
                        'custom_id' => 'userid:'.$user->id,
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => $amount,
                        ],
                    ]],
                    'payment_source' => [
                        'paypal' => [
                            'experience_context' => [
                                'return_url' => url('webhook/paypal?success=true'),
                                'cancel_url' => url('webhook/paypal?success=false'),
                                'user_action' => 'PAY_NOW',
                            ],
                        ],
                    ],
                ]);
                $approvalUrl = self::approvalUrl($order);

                if($approvalUrl === null || empty($order['id'])) {
                    throw new ApiException('PayPal did not return an order ID and approval URL.');
                }

                $sub = DB::subscription()->create();
                $sub->tid = $order['id'];
                $sub->userid = $user->id;
                $sub->plan = 'lifetime';
                $sub->planid = $plan->id;
                $sub->status = "Pending";
                $sub->amount = $amount;
                $sub->date = Helper::dtime();
                $sub->expiry = Helper::dtime('+1 day');
                $sub->lastpayment = Helper::dtime();
                $sub->data = json_encode([
                    'paymentmethod' => 'PaypalApi',
                    'intent' => [
                        'order_id' => (string) $order['id'],
                        'user_id' => (int) $user->id,
                        'plan_id' => (int) $plan->id,
                        'term' => 'lifetime',
                        'amount' => $amount,
                        'currency' => $currency,
                    ],
                    'paypal' => $order,
                ], JSON_THROW_ON_ERROR);
                $sub->uniqueid = Helper::rand(16);
                $sub->save();

                header("Location: {$approvalUrl}");
                exit;
            } catch (\Exception $e) {
                \GemError::log('PayPal API Error: '.$e->getMessage());
                return back()->with("danger",e("An issue occurred. You have not been charged."));
            }

        } else {

            $planid = self::createSinglePlan($plan, $type, $coupon, $tax, $client);

            if(!$planid) return back()->with("danger",e("An issue occurred. You have not been charged."));

            $price = $type == 'yearly' ? $plan->price_yearly : $plan->price_monthly;
            $price = $coupon ? round((1 - ($coupon->discount / 100)) * $price, 2) : $price;

            if($tax) $price = round($price * (1 + ($tax->rate / 100)), 2);
    
            try {
                $subscription = $client->createSubscription([
                    'plan_id' => $planid,
                    'custom_id' => 'userid:'.$user->id,
                    'subscriber' => ['email_address' => $user->email],
                    'application_context' => [
                        'brand_name' => config('title'),
                        'user_action' => 'SUBSCRIBE_NOW',
                        'return_url' => url('webhook/paypal?success=true'),
                        'cancel_url' => url('webhook/paypal?success=false'),
                    ],
                ]);
                $approvalUrl = self::approvalUrl($subscription);

                if($approvalUrl === null || empty($subscription['id'])) {
                    throw new ApiException('PayPal did not return a subscription approval URL.');
                }

                $uniqueid = Helper::rand(16);
    
                $sub = DB::subscription()->create();
                $sub->tid = $subscription['id'];
                $sub->userid = $user->id;
                $sub->plan = $type;
                $sub->planid = $plan->id;
                $sub->status = "Pending";
                $sub->amount = "0";
                $sub->date = Helper::dtime();
                $sub->expiry = Helper::dtime('+5 minutes');
                $sub->lastpayment = Helper::dtime();
                $sub->data = json_encode([
                    'paymentmethod' => 'PaypalApi',
                    'expected_amount' => self::money($price),
                    'paypal' => $subscription,
                ]);
                $sub->uniqueid = $uniqueid;
                $sub->save();
    
                $user->address = json_encode([
                        "address" 	=>	clean($request->address),
                        "city" 		=>	clean($request->city), 
                        "state" 	=>	clean($request->state),
                        "zip" 		=>	clean($request->zip),
                        "country" 	=>	clean($request->country)
                    ]);
                $user->name = clean($request->name);
                $user->save();
    
                header("Location: {$approvalUrl}");
                exit;
            } catch (\Exception $e) {
                \GemError::log('Paypal:' .$e->getMessage());
                return back()->with("danger",e("An issue occurred. You have not been charged."));
            }
        }			
    }
    /**
     * Webhook
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param [type] $request
     * @return void
     */
    public static function webhook($request){

        if(!config('paypalapi') || !config('paypalapi')->enabled || !config('paypalapi')->public || !config('paypalapi')->secret) {
            
            \GemError::log('Payment system "PaypalAPI" not enabled or configured.');

            return null;
        }

        $client = self::client();

        if($request->success === null) return self::handleWebhookEvent($request, $client);

        if($request->success != 'true') {
            return Helper::redirect()->to(route('dashboard'))->with("warning", e("Your payment has been canceled."));
        }

        try {
            if(self::lifetimeIntentForCallback($request) !== null) {
                return self::completeLifetimeOrder($request, $client);
            }

            return self::completeSubscription($request, $client);
        } catch (\Exception $exception) {
            \GemError::log('Paypal: '.$exception->getMessage());
            return Helper::redirect()->to(route('dashboard'))->with("danger",e("An issue occurred. You have not been charged."));
        }
    }
    /**
     * Create plan
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.1.6
     * @param [type] $plan
     * @return void
     */
    public static function createplan($plan){
        
    }
    /**
     * Create Paypal Plan on demande
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param [type] $plan
     * @return void
     */
    public static function createSinglePlan($plan, $type, $coupon = null, $tax = null, ?Client $client = null){
        $client = $client ?? self::client();
        $description = $plan->description ? $plan->description : $plan->name;
        $price = $type == 'yearly' ? $plan->price_yearly : $plan->price_monthly;
        $price = $coupon ? round((1 - ($coupon->discount / 100)) * $price, 2) : $price;

        if($tax) $price = round($price * (1 + ($tax->rate / 100)), 2);

        try {
            $product = $client->createProduct([
                'name' => $plan->name,
                'description' => $description,
                'type' => 'SERVICE',
                'category' => 'SOFTWARE',
            ]);

            if(empty($product['id'])) throw new ApiException('PayPal did not return a product ID.');

            $paypalPlan = $client->createPlan([
                'product_id' => $product['id'],
                'name' => $plan->name,
                'description' => $description,
                'billing_cycles' => [[
                    'frequency' => [
                        'interval_unit' => $type == 'yearly' ? 'YEAR' : 'MONTH',
                        'interval_count' => 1,
                    ],
                    'tenure_type' => 'REGULAR',
                    'sequence' => 1,
                    'total_cycles' => 0,
                    'pricing_scheme' => [
                        'fixed_price' => [
                            'value' => self::money($price),
                            'currency_code' => config('currency'),
                        ],
                    ],
                ]],
                'payment_preferences' => [
                    'auto_bill_outstanding' => true,
                    'setup_fee' => [
                        'value' => '0',
                        'currency_code' => config('currency'),
                    ],
                    'setup_fee_failure_action' => 'CONTINUE',
                    'payment_failure_threshold' => 0,
                ],
            ]);

            return $paypalPlan['id'] ?? false;
        } catch (\Exception $exception) {
            \GemError::log('Paypal: '.$exception->getMessage());
            return false;
        }
    }
    /**
     * Update Plan
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param [type] $plan
     * @return void
     */
    public static function updateplan($request, $plan){
        return self::createplan($plan);
    }
    /**
     * Sync Plans
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param [type] $plan
     * @return void
     */
    public static function syncplan($plan){
        return self::createplan($plan);
    }
    /**
     * Save Settings
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param \Core\Request $request
     * @return void
     */
    public static function save($request){

        if(!$request->paypalapi['public'] || !$request->paypalapi['secret']) return false;
        
        try {
            $client = self::client($request->paypalapi['public'], $request->paypalapi['secret']);
            $client->authenticate();
            $webhook = $client->createWebhook(route('webhook', ['paypal']), [
                'PAYMENT.CAPTURE.COMPLETED',
                'PAYMENT.CAPTURE.REFUNDED',
                'PAYMENT.CAPTURE.REVERSED',
                'PAYMENT.SALE.COMPLETED',
                'PAYMENT.SALE.REFUNDED',
                'PAYMENT.SALE.REVERSED',
                'BILLING.SUBSCRIPTION.ACTIVATED',
                'BILLING.SUBSCRIPTION.PAYMENT.FAILED',
                'BILLING.SUBSCRIPTION.CANCELLED',
                'BILLING.SUBSCRIPTION.EXPIRED',
                'BILLING.SUBSCRIPTION.SUSPENDED',
            ]);

            if(!empty($webhook['id'])) {
                $settings = $request->paypalapi;
                $settings['webhook_id'] = $webhook['id'];

                if($record = DB::settings()->where('config', 'paypalapi')->first()) {
                    $record->var = json_encode($settings);
                    $record->save();
                }
            }

            return true;
        } catch (\Exception $exception) {
            \GemError::log('PayPal API: '.$exception->getMessage());
            return false;
        }
    }
    /**
     * Cancel
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.1.8
     * @return void
     */
    public static function cancel($user, $subscription){
        $data = json_decode($subscription->data ?? '');

        if(($data->paymentmethod ?? null) !== 'PaypalApi' || !$subscription->tid) return false;

        try {
            self::client()->cancelSubscription((string) $subscription->tid, 'Customer requested cancellation.');
            return true;
        } catch (\Exception $exception) {
            \GemError::log('Paypal: '.$exception->getMessage());
            return false;
        }
    }

    /**
     * Resolve lifetime checkout context exclusively from the locally stored order intent.
     */
    public static function lifetimeIntentForCallback($request, ?callable $lookup = null): ?array{
        $orderId = trim((string) ($request->token ?? ''));

        if($orderId === '') return null;

        $lookup ??= static fn(string $id) => DB::subscription()->where('tid', $id)->first();
        $subscription = $lookup($orderId);

        if(!$subscription) return null;

        $data = json_decode((string) ($subscription->data ?? ''), true);
        $intent = is_array($data) && is_array($data['intent'] ?? null) ? $data['intent'] : null;

        if(($data['paymentmethod'] ?? null) !== 'PaypalApi' || !$intent || ($intent['term'] ?? null) !== 'lifetime') {
            return null;
        }

        $intentAmount = self::canonicalMoney($intent['amount'] ?? null);
        $storedAmount = self::canonicalMoney($subscription->amount ?? null);
        $trusted = [
            'order_id' => trim((string) ($intent['order_id'] ?? '')),
            'user_id' => (int) ($intent['user_id'] ?? 0),
            'plan_id' => (int) ($intent['plan_id'] ?? 0),
            'term' => 'lifetime',
            'amount' => $intentAmount,
            'currency' => strtoupper(trim((string) ($intent['currency'] ?? ''))),
        ];

        if(
            $trusted['order_id'] === ''
            || !hash_equals($trusted['order_id'], $orderId)
            || $trusted['user_id'] < 1
            || $trusted['plan_id'] < 1
            || $trusted['amount'] === null
            || $trusted['currency'] === ''
            || (string) ($subscription->tid ?? '') !== $trusted['order_id']
            || (int) ($subscription->userid ?? 0) !== $trusted['user_id']
            || (int) ($subscription->planid ?? 0) !== $trusted['plan_id']
            || (string) ($subscription->plan ?? '') !== $trusted['term']
            || $storedAmount === null
            || !hash_equals($trusted['amount'], $storedAmount)
        ) {
            throw new ApiException('Local PayPal lifetime intent is invalid.');
        }

        return $trusted;
    }

    /**
     * Recover an already captured order, or capture an approved order exactly once.
     */
    public static function captureOrFetchLifetimeOrder(string $orderId, callable $fetch, callable $capture): array{
        $order = $fetch($orderId);

        if(($order['status'] ?? null) === 'COMPLETED') return $order;

        if(($order['status'] ?? null) !== 'APPROVED') {
            throw new ApiException('PayPal order is not approved for capture.');
        }

        return $capture($orderId);
    }

    /**
     * Verify a captured PayPal order against the immutable local checkout intent.
     */
    public static function validateLifetimeCapture(array $order, array $intent): array{
        $orderId = trim((string) ($order['id'] ?? ''));
        $expectedOrderId = trim((string) ($intent['order_id'] ?? ''));
        $purchaseUnits = $order['purchase_units'] ?? [];
        $captures = is_array($purchaseUnits) && count($purchaseUnits) === 1
            ? ($purchaseUnits[0]['payments']['captures'] ?? [])
            : [];
        $capture = is_array($captures) && count($captures) === 1 ? $captures[0] : [];
        $captureId = trim((string) ($capture['id'] ?? ''));
        $actualAmount = self::canonicalMoney($capture['amount']['value'] ?? null);
        $expectedAmount = self::canonicalMoney($intent['amount'] ?? null);
        $actualCurrency = strtoupper(trim((string) ($capture['amount']['currency_code'] ?? '')));
        $expectedCurrency = strtoupper(trim((string) ($intent['currency'] ?? '')));

        if(
            $orderId === ''
            || $expectedOrderId === ''
            || !hash_equals($expectedOrderId, $orderId)
            || ($order['status'] ?? null) !== 'COMPLETED'
            || ($capture['status'] ?? null) !== 'COMPLETED'
            || $captureId === ''
            || $actualAmount === null
            || $expectedAmount === null
            || !hash_equals($expectedAmount, $actualAmount)
            || $actualCurrency === ''
            || $expectedCurrency === ''
            || !hash_equals($expectedCurrency, $actualCurrency)
            || (int) ($intent['user_id'] ?? 0) < 1
            || (int) ($intent['plan_id'] ?? 0) < 1
            || ($intent['term'] ?? null) !== 'lifetime'
        ) {
            throw new ApiException('Captured PayPal order does not match the local lifetime intent.');
        }

        return [
            'order_id' => $expectedOrderId,
            'capture_id' => $captureId,
            'user_id' => (int) ($intent['user_id'] ?? 0),
            'plan_id' => (int) ($intent['plan_id'] ?? 0),
            'term' => 'lifetime',
            'amount' => $expectedAmount,
            'currency' => $expectedCurrency,
        ];
    }

    /**
     * Commit local lifetime effects as one idempotent database transaction.
     */
    public static function applyLifetimeTransaction(\PDO $pdo, callable $alreadyProcessed, callable $apply): bool{
        return self::applyWebhookTransaction($pdo, $alreadyProcessed, $apply);
    }

    /**
     * Commit a verified PayPal webhook exactly once.
     */
    public static function applyWebhookTransaction(\PDO $pdo, callable $alreadyProcessed, callable $apply): bool{
        $pdo->beginTransaction();

        try {
            if($alreadyProcessed()) {
                $pdo->commit();
                return false;
            }

            $apply();
            $pdo->commit();

            return true;
        } catch (\Throwable $exception) {
            if($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    /**
     * Calculate entitlement expiry using the same rolling sentinel as other gateways.
     */
    public static function entitlementExpiry(string $term, ?string $currentExpiration = null, ?int $now = null): string{
        if(!in_array($term, ['monthly', 'yearly', 'lifetime'], true)) {
            throw new \InvalidArgumentException('Unsupported PayPal entitlement term.');
        }

        $now ??= time();
        $current = $currentExpiration !== null ? strtotime($currentExpiration) : false;
        $base = max($now, $current === false ? 0 : $current);
        $modifier = match ($term) {
            'yearly' => '+1 year',
            'lifetime' => '+20 years',
            default => '+1 month',
        };

        return date('Y-m-d H:i:s', strtotime($modifier, $base));
    }

    /**
     * Only revoke the exact entitlement funded by a fully reversed PayPal sale.
     */
    public static function reversalRevokesEntitlement(
        string $kind,
        float $reversedTotal,
        float $originalAmount,
        int $userPlanId,
        int $subscriptionPlanId,
        ?string $userExpiration,
        ?string $paymentExpiry
    ): bool {
        $fullyReversed = $kind === 'payment_reversed'
            || $reversedTotal + 0.000001 >= $originalAmount;
        $userExpiry = strtotime((string) $userExpiration);
        $paidExpiry = strtotime((string) $paymentExpiry);

        return $fullyReversed
            && $userPlanId > 0
            && $userPlanId === $subscriptionPlanId
            && $userExpiry !== false
            && $paidExpiry !== false
            && $userExpiry === $paidExpiry;
    }

    /**
     * Replace PayPal response data without discarding local idempotency metadata.
     */
    public static function updatedSubscriptionData(array $existing, array $paypal): array{
        $existing['paymentmethod'] = 'PaypalApi';
        $existing['paypal'] = $paypal;

        return $existing;
    }

    /**
     * A paid sale restores recoverable states but cannot undo terminal PayPal state.
     */
    public static function statusAfterCompletedSale(?string $currentStatus): string{
        return in_array($currentStatus, ['Canceled', 'Expired'], true)
            ? $currentStatus
            : 'Active';
    }

    /**
     * Normalize PayPal subscription webhooks into trusted local actions.
     */
    public static function normalizeSubscriptionWebhookEvent(array $event): ?array{
        $eventType = trim((string) ($event['event_type'] ?? ''));
        $supported = [
            'PAYMENT.SALE.COMPLETED',
            'PAYMENT.SALE.REFUNDED',
            'PAYMENT.SALE.REVERSED',
            'BILLING.SUBSCRIPTION.ACTIVATED',
            'BILLING.SUBSCRIPTION.PAYMENT.FAILED',
            'BILLING.SUBSCRIPTION.CANCELLED',
            'BILLING.SUBSCRIPTION.EXPIRED',
            'BILLING.SUBSCRIPTION.SUSPENDED',
        ];

        if(!in_array($eventType, $supported, true)) return null;

        $eventId = trim((string) ($event['id'] ?? ''));
        $occurredAt = trim((string) ($event['create_time'] ?? ''));
        $resource = is_array($event['resource'] ?? null) ? $event['resource'] : [];

        if($eventId === '' || $occurredAt === '' || strtotime($occurredAt) === false || !$resource) {
            throw new ApiException('PayPal webhook event identity is invalid.');
        }

        $statusMap = [
            'BILLING.SUBSCRIPTION.ACTIVATED' => 'Active',
            'BILLING.SUBSCRIPTION.PAYMENT.FAILED' => 'Past Due',
            'BILLING.SUBSCRIPTION.CANCELLED' => 'Canceled',
            'BILLING.SUBSCRIPTION.EXPIRED' => 'Expired',
            'BILLING.SUBSCRIPTION.SUSPENDED' => 'Suspended',
        ];

        if(isset($statusMap[$eventType])) {
            $subscriptionId = trim((string) ($resource['id'] ?? ''));

            if($subscriptionId === '') throw new ApiException('PayPal subscription webhook ID is invalid.');

            return [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'kind' => 'subscription_status',
                'subscription_id' => $subscriptionId,
                'status' => $statusMap[$eventType],
                'occurred_at' => $occurredAt,
            ];
        }

        $state = strtolower(trim((string) ($resource['state'] ?? '')));
        $expectedState = $eventType === 'PAYMENT.SALE.REVERSED' ? 'reversed' : 'completed';
        $saleId = trim((string) (
            $eventType === 'PAYMENT.SALE.REFUNDED'
                ? ($resource['sale_id'] ?? '')
                : ($resource['sale_id'] ?? $resource['id'] ?? '')
        ));
        $subscriptionId = trim((string) ($resource['billing_agreement_id'] ?? ''));
        $amount = self::canonicalMoney($resource['amount']['total'] ?? $resource['amount']['value'] ?? null);
        $currency = strtoupper(trim((string) ($resource['amount']['currency'] ?? $resource['amount']['currency_code'] ?? '')));

        if($state !== $expectedState || $saleId === '' || $amount === null || $currency === '') {
            throw new ApiException('PayPal subscription payment webhook is invalid.');
        }

        if($eventType === 'PAYMENT.SALE.COMPLETED' && $subscriptionId === '') {
            throw new ApiException('PayPal subscription payment is missing its subscription ID.');
        }

        return [
            'event_id' => $eventId,
            'event_type' => $eventType,
            'kind' => match ($eventType) {
                'PAYMENT.SALE.COMPLETED' => 'payment_completed',
                'PAYMENT.SALE.REFUNDED' => 'payment_refunded',
                default => 'payment_reversed',
            },
            'sale_id' => $saleId,
            'subscription_id' => $subscriptionId !== '' ? $subscriptionId : null,
            'amount' => $amount,
            'currency' => $currency,
            'occurred_at' => $occurredAt,
        ];
    }

    private static function withLifetimeOrderLock(string $orderId, callable $callback): mixed{
        $pdo = DB::get_db();
        $lockName = 'paypal-lifetime:'.substr(hash('sha256', $orderId), 0, 48);
        $acquire = $pdo->prepare('SELECT GET_LOCK(:lock_name, 10)');
        $acquire->execute(['lock_name' => $lockName]);

        if((int) $acquire->fetchColumn() !== 1) {
            throw new ApiException('Unable to acquire PayPal lifetime order lock.');
        }

        try {
            return $callback();
        } finally {
            $release = $pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
            $release->execute(['lock_name' => $lockName]);
        }
    }

    private static function completeLifetimeOrder($request, Client $client){
        $orderId = trim((string) ($request->token ?? ''));

        if($orderId === '') throw new ApiException('PayPal order ID is missing.');

        $result = self::withLifetimeOrderLock($orderId, static function () use ($request, $client, $orderId): ?array {
            if(DB::payment()->where('cid', $orderId)->first()) return null;

            $subscription = DB::subscription()->where('tid', $orderId)->first();
            $intent = self::lifetimeIntentForCallback(
                $request,
                static fn(string $id) => $id === $orderId ? $subscription : null
            );

            if($intent === null || (string) ($subscription->status ?? '') !== 'Pending') {
                throw new ApiException('Local PayPal lifetime intent is not pending.');
            }

            $order = self::captureOrFetchLifetimeOrder(
                $orderId,
                static fn(string $id): array => $client->getOrder($id),
                static fn(string $id): array => $client->captureOrder($id)
            );
            $capture = self::validateLifetimeCapture($order, $intent);
            $pdo = DB::get_db();
            $dispatch = null;

            self::applyLifetimeTransaction(
                $pdo,
                static fn(): bool => (bool) DB::payment()->where('cid', $capture['order_id'])->first()
                    || (bool) DB::payment()->where('tid', $capture['capture_id'])->first(),
                static function () use (&$dispatch, $capture, $intent, $order, $orderId): void {
                    $subscription = DB::subscription()->where('tid', $orderId)->first();
                    $freshIntent = self::lifetimeIntentForCallback(
                        (object) ['token' => $orderId],
                        static fn(string $id) => $id === $orderId ? $subscription : null
                    );

                    if(
                        $freshIntent === null
                        || $freshIntent !== $intent
                        || (string) ($subscription->status ?? '') !== 'Pending'
                    ) {
                        throw new ApiException('Local PayPal lifetime intent changed during capture.');
                    }

                    $user = DB::user()->where('id', $capture['user_id'])->first();

                    if(!$user || !DB::plans()->where('id', $capture['plan_id'])->first()) {
                        throw new ApiException('Local PayPal lifetime user or plan no longer exists.');
                    }

                    $expiry = self::entitlementExpiry('lifetime', $user->expiration ?? null);
                    $payment = DB::payment()->create();
                    $payment->date = Helper::dtime('now');
                    $payment->cid = $capture['order_id'];
                    $payment->tid = $capture['capture_id'];
                    $payment->amount = $capture['amount'];
                    $payment->userid = $capture['user_id'];
                    $payment->status = "Completed";
                    $payment->expiry = $expiry;
                    $payment->data = json_encode([
                        'intent' => $intent,
                        'paypal' => $order,
                    ], JSON_THROW_ON_ERROR);
                    $payment->save();

                    $subscription->status = "Active";
                    $subscription->amount = $capture['amount'];
                    $subscription->expiry = $expiry;
                    $subscription->lastpayment = Helper::dtime();
                    $subscription->data = json_encode([
                        'paymentmethod' => 'PaypalApi',
                        'intent' => $intent,
                        'paypal' => $order,
                    ], JSON_THROW_ON_ERROR);
                    $subscription->save();

                    $user->last_payment = Helper::dtime();
                    $user->expiration = $expiry;
                    $user->pro = 1;
                    $user->planid = $capture['plan_id'];
                    $user->save();

                    $dispatch = [$user, $capture['plan_id'], $payment->id()];
                }
            );

            return $dispatch;
        });

        if($result !== null) \Core\Plugin::dispatch('payment.success', $result);

        return Helper::redirect()->to(route('billing'))->with("success", e("Your payment was successfully made. Thank you."));
    }

    private static function completeSubscription($request, Client $client){
        $subscriptionId = $request->subscription_id ?: $request->token;

        if(!$subscriptionId) throw new ApiException('PayPal subscription ID is missing.');

        $response = $client->getSubscription((string) $subscriptionId);

        if(($response['status'] ?? null) !== 'ACTIVE') throw new ApiException('PayPal subscription is not active.');

        $userid = str_replace('userid:', '', (string) ($response['custom_id'] ?? ''));

        if(!$userid || !$user = DB::user()->where('id', $userid)->first()) {
            throw new ApiException('PayPal subscription data did not match a user.');
        }

        $subscription = DB::subscription()->where('tid', $response['id'])->first();

        if(!$subscription || (int) ($subscription->userid ?? 0) !== (int) $user->id) {
            throw new ApiException('Local PayPal subscription was not found.');
        }

        $storedData = json_decode($subscription->data ?? '', true) ?: [];
        if((string) ($subscription->status ?? '') === 'Pending') $subscription->status = "Active";
        $subscription->data = json_encode(
            self::updatedSubscriptionData($storedData, $response),
            JSON_THROW_ON_ERROR
        );
        $subscription->save();

        return Helper::redirect()->to(route('billing'))->with("success", e("Your PayPal subscription was activated. Access is granted after payment confirmation."));
    }

    private static function handleWebhookEvent(Request $request, Client $client): Response{
        $body = file_get_contents('php://input');
        $event = json_decode($body ?: '', true);
        $webhookId = config('paypalapi')->webhook_id ?? null;

        if(!is_array($event) || !$webhookId) {
            \GemError::log('PayPal webhook could not be verified because its payload or webhook ID is missing.');
            return Response::factory('', 400);
        }

        try {
            $verified = $client->verifyWebhookSignature([
                'auth_algo' => $request->serverString('HTTP_PAYPAL_AUTH_ALGO'),
                'cert_url' => $request->serverString('HTTP_PAYPAL_CERT_URL'),
                'transmission_id' => $request->serverString('HTTP_PAYPAL_TRANSMISSION_ID'),
                'transmission_sig' => $request->serverString('HTTP_PAYPAL_TRANSMISSION_SIG'),
                'transmission_time' => $request->serverString('HTTP_PAYPAL_TRANSMISSION_TIME'),
                'webhook_id' => $webhookId,
                'webhook_event' => $event,
            ]);

            if(!$verified) return Response::factory('', 400);

            $action = self::normalizeSubscriptionWebhookEvent($event);

            if($action === null) return Response::factory('', 200);

            $status = match ($action['kind']) {
                'payment_completed' => self::handleCompletedSubscriptionPayment($action, $event),
                'payment_refunded', 'payment_reversed' => self::handleSubscriptionPaymentReversal($action, $event),
                'subscription_status' => self::handleSubscriptionStatusEvent($action, $event),
                default => 200,
            };

            return Response::factory('', $status);
        } catch (\Exception $exception) {
            \GemError::log('PayPal webhook verification failed: '.$exception->getMessage());
            return Response::factory('', 400);
        }
    }

    private static function handleCompletedSubscriptionPayment(array $action, array $event): int{
        $result = self::withWebhookEventLock(
            $action['subscription_id'],
            static function () use ($action, $event): array {
                if(
                    DB::payment()->where('tid', $action['event_id'])->first()
                    || DB::payment()->where('cid', $action['sale_id'])->first()
                ) {
                    return ['status' => 200, 'dispatch' => null];
                }

                $subscription = DB::subscription()->where('tid', $action['subscription_id'])->first();

                if(!$subscription) return ['status' => 409, 'dispatch' => null];

                $data = json_decode((string) ($subscription->data ?? ''), true) ?: [];
                $expectedAmount = self::canonicalMoney($data['expected_amount'] ?? null);
                $expectedCurrency = strtoupper(trim((string) config('currency')));

                if(
                    ($data['paymentmethod'] ?? null) !== 'PaypalApi'
                    || !in_array((string) ($subscription->plan ?? ''), ['monthly', 'yearly'], true)
                    || $expectedAmount === null
                    || !hash_equals($expectedAmount, $action['amount'])
                    || $expectedCurrency === ''
                    || !hash_equals($expectedCurrency, $action['currency'])
                ) {
                    return ['status' => 422, 'dispatch' => null];
                }

                $user = DB::user()->where('id', $subscription->userid)->first();

                if(!$user) return ['status' => 409, 'dispatch' => null];

                $pdo = DB::get_db();
                $dispatch = null;

                self::applyWebhookTransaction(
                    $pdo,
                    static fn(): bool => (bool) DB::payment()->where('tid', $action['event_id'])->first()
                        || (bool) DB::payment()->where('cid', $action['sale_id'])->first(),
                    static function () use (&$dispatch, $action, $event, $subscription, $user, $data): void {
                        $occurredAt = strtotime($action['occurred_at']);
                        $expiry = self::entitlementExpiry(
                            (string) $subscription->plan,
                            $user->expiration ?? null,
                            $occurredAt === false ? time() : $occurredAt
                        );
                        $payment = DB::payment()->create();
                        $payment->date = Helper::dtime($action['occurred_at']);
                        $payment->cid = $action['sale_id'];
                        $payment->tid = $action['event_id'];
                        $payment->amount = $action['amount'];
                        $payment->userid = $user->id;
                        $payment->status = 'Completed';
                        $payment->expiry = $expiry;
                        $payment->data = json_encode($event, JSON_THROW_ON_ERROR);
                        $payment->save();

                        $subscription->amount = (float) ($subscription->amount ?? 0) + (float) $action['amount'];
                        $subscription->expiry = $expiry;
                        $subscription->lastpayment = Helper::dtime($action['occurred_at']);
                        $subscription->status = self::statusAfterCompletedSale($subscription->status ?? null);
                        $subscription->data = json_encode(
                            self::updatedSubscriptionData($data, $event),
                            JSON_THROW_ON_ERROR
                        );
                        $subscription->save();

                        $user->last_payment = Helper::dtime($action['occurred_at']);
                        $user->expiration = $expiry;
                        $user->pro = 1;
                        $user->planid = $subscription->planid;
                        $user->save();

                        $dispatch = [$user, $subscription->planid, $payment->id()];
                    }
                );

                return ['status' => 200, 'dispatch' => $dispatch];
            }
        );

        if($result['dispatch'] !== null) \Core\Plugin::dispatch('payment.success', $result['dispatch']);

        return $result['status'];
    }

    private static function handleSubscriptionPaymentReversal(array $action, array $event): int{
        return self::withWebhookEventLock(
            $action['sale_id'],
            static function () use ($action, $event): int {
                if(DB::payment()->where('tid', $action['event_id'])->first()) return 200;

                $original = DB::payment()
                    ->where('cid', $action['sale_id'])
                    ->where('status', 'Completed')
                    ->first();

                if(!$original) return 409;

                $originalData = json_decode((string) ($original->data ?? ''), true) ?: [];
                $subscriptionId = $action['subscription_id']
                    ?? ($originalData['resource']['billing_agreement_id'] ?? null);
                $subscription = $subscriptionId
                    ? DB::subscription()->where('tid', $subscriptionId)->first()
                    : null;
                $user = DB::user()->where('id', $original->userid)->first();

                if(!$subscription || !$user) return 409;

                $expectedCurrency = strtoupper(trim((string) config('currency')));

                if($expectedCurrency === '' || !hash_equals($expectedCurrency, $action['currency'])) return 422;

                $reversedTotal = (float) $action['amount'];

                foreach(DB::payment()->where('cid', $action['sale_id'])->findMany() as $record) {
                    if(in_array((string) ($record->status ?? ''), ['Refunded', 'Reversed'], true)) {
                        $reversedTotal += abs((float) ($record->amount ?? 0));
                    }
                }

                $fullyReversed = $action['kind'] === 'payment_reversed'
                    || $reversedTotal + 0.000001 >= (float) $original->amount;
                $revokeEntitlement = self::reversalRevokesEntitlement(
                    $action['kind'],
                    $reversedTotal,
                    (float) $original->amount,
                    (int) ($user->planid ?? 0),
                    (int) ($subscription->planid ?? 0),
                    $user->expiration ?? null,
                    $original->expiry ?? null
                );
                $pdo = DB::get_db();

                self::applyWebhookTransaction(
                    $pdo,
                    static fn(): bool => (bool) DB::payment()->where('tid', $action['event_id'])->first(),
                    static function () use ($action, $event, $original, $subscription, $user, $fullyReversed, $revokeEntitlement): void {
                        $adjustment = DB::payment()->create();
                        $adjustment->date = Helper::dtime($action['occurred_at']);
                        $adjustment->cid = $action['sale_id'];
                        $adjustment->tid = $action['event_id'];
                        $adjustment->amount = -((float) $action['amount']);
                        $adjustment->userid = $original->userid;
                        $adjustment->status = $action['kind'] === 'payment_reversed' ? 'Reversed' : 'Refunded';
                        $adjustment->expiry = $original->expiry;
                        $adjustment->data = json_encode($event, JSON_THROW_ON_ERROR);
                        $adjustment->save();

                        $subscription->amount = max(0, (float) ($subscription->amount ?? 0) - (float) $action['amount']);

                        if($fullyReversed) {
                            $subscription->status = $action['kind'] === 'payment_reversed' ? 'Suspended' : 'Refunded';

                            if($revokeEntitlement) {
                                $user->expiration = Helper::dtime();
                                $user->pro = 0;
                                $user->planid = null;
                                $user->save();
                            }
                        }

                        $subscription->save();
                    }
                );

                return 200;
            }
        );
    }

    private static function handleSubscriptionStatusEvent(array $action, array $event): int{
        return self::withWebhookEventLock(
            $action['subscription_id'],
            static function () use ($action, $event): int {
                $subscription = DB::subscription()->where('tid', $action['subscription_id'])->first();

                if(!$subscription) return 409;

                $data = json_decode((string) ($subscription->data ?? ''), true) ?: [];
                $processed = is_array($data['processed_webhook_events'] ?? null)
                    ? $data['processed_webhook_events']
                    : [];
                $pdo = DB::get_db();

                self::applyWebhookTransaction(
                    $pdo,
                    static fn(): bool => in_array($action['event_id'], $processed, true),
                    static function () use ($action, $event, $subscription, $data, $processed): void {
                        $subscription->status = $action['status'];
                        $processed[] = $action['event_id'];
                        $data['paymentmethod'] = 'PaypalApi';
                        $data['paypal'] = $event;
                        $data['processed_webhook_events'] = array_slice(array_values(array_unique($processed)), -20);
                        $subscription->data = json_encode($data, JSON_THROW_ON_ERROR);
                        $subscription->save();

                        if($action['status'] !== 'Expired') return;

                        $user = DB::user()->where('id', $subscription->userid)->first();

                        if(
                            $user
                            && (int) ($user->planid ?? 0) === (int) ($subscription->planid ?? 0)
                            && strtotime((string) ($user->expiration ?? '')) <= time()
                        ) {
                            $user->pro = 0;
                            $user->planid = null;
                            $user->save();
                        }
                    }
                );

                return 200;
            }
        );
    }

    private static function withWebhookEventLock(string $resourceId, callable $callback): mixed{
        $pdo = DB::get_db();
        $lockName = 'paypal-webhook:'.substr(hash('sha256', $resourceId), 0, 47);
        $acquire = $pdo->prepare('SELECT GET_LOCK(:lock_name, 10)');
        $acquire->execute(['lock_name' => $lockName]);

        if((int) $acquire->fetchColumn() !== 1) {
            throw new ApiException('Unable to acquire PayPal webhook event lock.');
        }

        try {
            return $callback();
        } finally {
            $release = $pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
            $release->execute(['lock_name' => $lockName]);
        }
    }

    private static function client(?string $clientId = null, ?string $clientSecret = null): Client{
        $settings = config('paypalapi');

        return new Client(
            new CurlTransport(),
            $clientId ?? $settings->public,
            $clientSecret ?? $settings->secret,
            defined('DEBUG') && DEBUG
        );
    }

    private static function approvalUrl(array $resource): ?string{
        foreach($resource['links'] ?? [] as $link) {
            if(in_array($link['rel'] ?? null, ['approve', 'payer-action'], true) && !empty($link['href'])) {
                return $link['href'];
            }
        }

        return null;
    }

    private static function money($amount): string{
        return number_format((float) $amount, 2, '.', '');
    }

    private static function canonicalMoney($amount): ?string{
        $amount = trim((string) $amount);

        if(!preg_match('/^\d+(?:\.\d{1,2})?$/D', $amount)) return null;

        return number_format((float) $amount, 2, '.', '');
    }
}
