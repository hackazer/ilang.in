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
use Core\Email;

class Stripe{

	/**
	 * Generate Payment Form
	 *
	 * @author GemPixel <https://gempixel.com> 
	 * @version 6.0
	 * @return void
	 */
	public static function settings(){

		$config = config('stripe');

		if(!$config && !isset($config->enabled)){
					
			$settings = DB::settings()->create();

			$settings->config = 'stripe';
			$settings->var = json_encode(['enabled' => config('pt') == 'stripe', 'secret' => config('stsk'), 'public' => config('stpk'), 'sig' => config('stripesig')]);
			$settings->save();
			$config = json_decode($settings->var);
		}

		$html = '<div class="form-group">
					<label for="stripe[enabled]" class="form-label">'.e('Stripe Payments').'</label>
					<div class="form-check form-switch">
						<input class="form-check-input" type="checkbox" data-binary="true" id="stripe[enabled]" name="stripe[enabled]" value="1" '.($config->enabled ? 'checked':'').' data-toggle="togglefield" data-toggle-for="stripeholder">
						<label class="form-check-label" for="stripe[enabled]">'.e('Enable').'</label>
					</div>
					<p class="form-text">'.e('Collect payments securely with Stripe.').'</p>
				</div>
				<div id="stripeholder" class="toggles '.(!$config->enabled ? 'd-none' : '') .'">
					<div class="form-group">
						<label class="form-label">'.e('Checkout').'</label>
						<label class="form-check">
							<input type="radio" class="form-check-input" name="stripe[type]" value="default" '.(!isset($config->type) || $config->type == 'default' ? 'checked' :'').'>
							<span class="form-check-label">
								'.e('Built-in Checkout').'
							</span>
						</label>
						<label class="form-check">
							<input type="radio" class="form-check-input" name="stripe[type]" value="stripe" '.(isset($config->type) && $config->type == 'stripe' ? 'checked' :'').'>
							<span class="form-check-label">
								'.e('Stripe Hosted Checkout').'
							</span>
						</label>
						<p class="form-text">'.e('Choose between built-in checkout or Stripe hosted checkout.').'</p>
					</div>
					<div class="form-group">
						<label for="stripe[public]" class="form-label">'.e('Stripe Publishable Key').'</label>
						<input type="text" class="form-control" name="stripe[public]" placeholder="" id="stripe[public]" value="'.$config->public.'">
						<p class="form-text">'.e('Get your stripe keys from here once logged in <a href="https://dashboard.stripe.com/account/apikeys" target="_blank">click here</a>').'</p>
					</div>
					<div class="form-group">
						<label for="stripe[secret]" class="form-label">'.e('Stripe Secret Key').'</label>
						<input type="text" class="form-control" name="stripe[secret]" placeholder="" id="stripe[secret]" value="'.$config->secret.'">
						<p class="form-text">'.e('Get your stripe keys from here once logged in <a href="https://dashboard.stripe.com/account/apikeys" target="_blank">click here</a>').'</p>
					</div>
					<div class="form-group">
						<label for="stripe[sig]" class="form-label">'.e('Webhook Signature Key').'</label>
						<input type="text" class="form-control" name="stripe[sig]" placeholder="whsec_..." id="stripe[sig]" value="'.$config->sig.'">
						<p class="form-text">'.e('Webhook signature is a security measure to verify the authenticity of the data incoming from Stripe. It is highly recommended that you add this for safety measure. You can find your key after adding a webhook. <a href="https://dashboard.stripe.com/account/webhooks" target="_blank">Click here to find your signature key.</a>').'</p>
					</div>
					<div class="form-group">
						<label for="webhook" class="form-label">'.e('Webhook URL').'</label>
						<input type="text" class="form-control" id="webhook" value="'.route('webhook', ['']).'" disabled>
						<p class="form-text">'.e('You can add your webhooks <a href="https://dashboard.stripe.com/account/webhooks" target="_blank">here</a>. For more info, please check the docs.').'</p>
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

		if(!config('stripe') || !config('stripe')->enabled || !config('stripe')->public || !config('stripe')->secret) {            
            return false;
        }
		
		$stripeConfig = config('stripe');

		if(!isset($stripeConfig->type) || $stripeConfig->type == "default"){
			View::push("<script type='text/javascript'>     
						var stripe = Stripe('".config('stripe')->public."');

						var elements = stripe.elements();
						var style = {
						base: {
								color: '#32325d',
								fontFamily: '\"Helvetica Neue\", Helvetica, sans-serif',
								fontSmoothing: 'antialiased',
								fontSize: '16px',
								'::placeholder': {
									color: '#aab7c4'
								}
							},
							invalid: {
								color: '#fa755a',
								iconColor: '#fa755a'
							}
						};
						var card = elements.create('card', {hidePostalCode: true, style: style});
						card.mount('#card-element');			
						elements.getElement('card').on('change', function(event) {
							var displayError = document.getElementById('card-errors');
							if (event.error) {
								displayError.textContent = event.error.message;
							} else {
								displayError.textContent = '';
							}
						});			
						$('button[type=submit]').click(function(e){
							if($('input[name=payment]:checked').val() == 'stripe') {
								e.preventDefault();         
								stripe.createToken(card).then(function(result) {                
									if (result.error) {
										var errorElement = document.getElementById('card-errors');
										errorElement.textContent = result.error.message;		    
									} else {
										$('#stripeToken').attr('name', 'stripeToken').val(result.token.id);
										$('form').submit();
									}
								});
							}
						});
						</script>", "custom")->tofooter();

			echo '<div id="stripe" class="paymentOptions"><script src="https://js.stripe.com/v3/"></script>
					<input type="hidden" id="stripeToken">
					<div class="form-group" id="stripeElement">
						<label for="card-element">
							'.e("Credit or debit card").'
						</label>
						<div id="card-element" class="border p-3 rounded mt-2"></div>
						<div id="card-errors" role="alert" class="text-danger pt=2"></div>
					</div></div>';
		} else {
			echo '<div id="stripe" class="paymentOptions"></div>';
		}
	}
	/**
	 * Payment
	 *
	 * @author GemPixel <https://gempixel.com> 
	 * @version 6.0
	 * @param Request $request
	 * @param integer $id
	 * @param string $type
	 * @return void
	 */
	public static function payment(Request $request, int $id, string $type){

		if(!config('stripe') || !config('stripe')->enabled || !config('stripe')->public || !config('stripe')->secret) {
            
            \GemError::log('Payment system "Stripe" not enabled or configured.');

            return back()->with('danger', e('An error ocurred, please try again. You have not been charged.'));
        }

	  	if(!$plan = DB::plans()->first($id)){
			return back()->with('danger', e('An error ocurred, please try again. You have not been charged.'));
	  	}			
		
		$term = e($plan->name);
		$text = e("First month");
		$price = $plan->price_monthly;
		$planid = $plan->slug."monthly";
	
		if($type == "yearly" && $plan->price_yearly){
			$term = e($plan->name);
			$text = e("First year");
			$price = $plan->price_yearly;
			$planid = $plan->slug."yearly";				
		}

		if($type == "lifetime" && $plan->price_lifetime){
			$term = e($plan->name);
			$text = e("Lifetime");
			$price = $plan->price_lifetime;
			$planid = $plan->slug."lifetime";			
		}

		$stripeConfig = config('stripe');

		$user = Auth::user();

		if(isset($stripeConfig->type) && $stripeConfig->type == "stripe") return self::paymentLink($request, $type, $plan, $user);
  
		if(!$request->stripeToken) return back()->with("warning", e("An error ocurred, please try again. You have not been charged."));
		
		$stripe = new \Stripe\StripeClient(config('stripe')->secret);	

		if(!$user->customerid){

			try {

				$customer = $stripe->customers->create([
					"email" => $user->email,
					"description" => "$term - $text for {$user->email}",
					"name" => clean($request->name),
					"address" => [
						"line1" => clean($request->address),
						"city" => clean($request->city),
						"country" => clean($request->country),
						"postal_code" => clean($request->zip),
						"state" => clean($request->state)
					],				  
					"source" => $request->stripeToken
				]);

			} catch(\Stripe\Exception\CardException $e) {
				
				\GemError::log('Stripe Card Error:'.$e->getMessage());
				return back()->with("danger", e($e->getMessage()));

			} catch(\Exception $e) {
				\GemError::log('Stripe Error: '.$e->getMessage());
				return back()->with("warning", e("An error ocurred, please try again. You have not been charged."));
			}

			if(!isset($customer->id)) return back()->with("warning", e("An error ocurred, please try again. You have not been charged."));

			$user->customerid = $customer->id;			
			$user->save();		  
		} else {
			try{
			
				$stripe->customers->update($user->customerid, ['source' => $request->stripeToken]);
	
			} catch(\Stripe\Exception\CardException $e) {
					
				\GemError::log('Stripe Card Error:'.$e->getMessage());
				return back()->with("danger", e($e->getMessage()));
	
			} catch(\Exception $e) {
				\GemError::log('Stripe Error: '.$e->getMessage());
				return back()->with("warning", e("An error ocurred, please try again. You have not been charged."));
			}
		}
  
		$uniqueid = Helper::rand(16);

		$sub = DB::subscription()->create();
		$sub->tid = null;
		$sub->userid = $user->id;
		$sub->plan = $type;
		$sub->planid = $plan->id;
		$sub->status = "Pending";
		$sub->amount = "0";
		$sub->date = Helper::dtime();
		$sub->expiry = Helper::dtime();
		$sub->lastpayment = Helper::dtime();
		$sub->data = NULL;
		$sub->uniqueid = $uniqueid;
		$sub->save();

		if($request->coupon && $coupon = DB::coupons()->where('code', clean($request->coupon))->first()){
			if(strtotime("now") < strtotime(date("Y-m-d 11:59:00", strtotime($coupon->validuntil)))) {
				$coupon->used++;
				$coupon->save();
				$price = round((1 - ($coupon->discount / 100)) * $price, 2);
				$sub->coupon = 1;
				$sub->save();
				$coupon->data = json_decode($coupon->data);
			}
		}

		if($tax = DB::taxrates()->whereRaw('countries LIKE ?', ["%".clean($request->country)."%"])->first()){
			$tax->data = json_decode($tax->data);
		}

		if($type == "lifetime"){
			
			if($tax){
                $price = round($price * (1+($tax->rate / 100)), 2);
            }

			try{

				$charge = $stripe->charges->create([
					'customer' => $user->customerid,
					'amount' => $price * 100,
					'currency' => strtolower(config('currency')),
					'description' =>  "$term - $text for {$user->email}",
				]);

				$charge->paymentmethod = 'Stripe';

				if($charge->status == 'succeeded'){
					$sub->status = 'Completed';
					$sub->amount = $price;
					$sub->expiry = Helper::dtime('+20 years');
					$sub->tid = $charge->id;
					$sub->data = json_encode($charge);
					$sub->save();
				}
				
				if($charge->status != 'succeeded'){
					return back()->with("warning", e("An error ocurred, please try again. You have not been charged."));
				}
				  
			} catch(\Stripe\Exception\CardException $e) {
				
				\GemError::log('Stripe Card Error:'.$e->getMessage());
				return back()->with("danger", e($e->getMessage()));

			} catch (\Exception $e) {
				
				\GemError::log('Stripe Error: '.$e->getMessage());

				return back()->with("warning", e("An error ocurred, please try again. You have not been charged."));
			}			
		
		} else {
		
			try {
				$intent = [
					"customer" => $user->customerid,
					"items" => [
								[
									"plan" => $planid,
								],
							]
				];

				if(isset($coupon) && $coupon){
					$intent["coupon"] = $coupon->data->stripe;
				}

				if(isset($tax) && $tax){
					$intent["default_tax_rates"] = [$tax->data->stripe];
				}
										
				$subscription = $stripe->subscriptions->create($intent);			
				$sub->tid = $subscription->id;
				$sub->save();
				if(!self::subscriptionGrantsEntitlement((string) $subscription->status)){
					return back()->with("warning", e("Your credit card was declined. Please check your credit card and try again later."));
				}
	
			} catch(\Stripe\Exception\CardException $e) {
				
				\GemError::log('Stripe Card Error:'.$e->getMessage());
				return back()->with("danger", e($e->getMessage()));

			} catch (\Exception $e) {

				\GemError::log('Stripe Error:'.$e->getMessage(), $intent);
				return back()->with("warning", e("An error ocurred, please try again. You have not been charged."));

			}				
		}	
		
		$user->last_payment = Helper::dtime();
		$user->expiration = $type == "lifetime" ? Helper::dtime('+20 years') : Helper::dtime();
		$user->pro = 1;
		$user->planid = $plan->id;
		$user->address = json_encode([
				"address" 	=>	clean($request->address),
				"city" 		=>	clean($request->city), 
				"state" 	=>	clean($request->state),
				"zip" 		=>	clean($request->zip),
				"country" 	=>	clean($request->country)
			]);
		$user->name = clean($request->name);
		$user->save();

		if($type === 'lifetime' && isset($charge) && !DB::payment()->where('tid', (string) $charge->id)->first()){
			$payment = DB::payment()->create();
			$payment->date = Helper::dtime();
			$payment->cid = $uniqueid;
			$payment->tid = (string) $charge->id;
			$payment->amount = $price;
			$payment->userid = $user->id;
			$payment->status = 'Completed';
			$payment->expiry = $user->expiration;
			$payment->data = json_encode($charge);
			$payment->save();
		}

		if(config('smtp')->user){
            $mailer = Email::factory('smtp', [
                'username' => config('smtp')->user,
                'password' => config('smtp')->pass,
                'host' => config('smtp')->host,
                'port' => config('smtp')->port
            ]);
        } else {
            $mailer = Email::factory();
        }

        $mailer->from([config('email'), config('title')])
               ->template(View::$path.'/email.php');

		$message = '<p><strong>Congrats! You have a new subscription from '.$user->email.'</strong></p>
			   <p><strong>Subscription - '.$term.' '.$text.'</strong>: '.str_replace('$', '&#36;', \Helpers\App::currency(config('currency'), $price)).'</p>

			   '.(isset($coupon) ? '
			   <p>
				   <strong>Coupon - '.$coupon->name.':</strong> -'.str_replace('$', '&#36;', \Helpers\App::currency(config('currency'), $price*($coupon->discount/100))).'
			   </p>': '').'
			   <p>
				   <strong>Total:</strong> '.str_replace('$', '&#36;', \Helpers\App::currency(config('currency'), (isset($coupon) ? $price*(1-($coupon->discount/100)) : $price))).'
			   </p>																												
			   <p>
				   Charged on '.date("d-m-Y  H:i:s").'
			   </p>';

        $mailer->to(config('email'))
                ->send([
                    'subject' => e('You have a new Subscriber'),
                    'message' => function($template, $data) use ($message) {
                        if(config('logo')){
                            $title = '<img align="center" alt="Image" border="0" class="center autowidth" src="'.uploads(config('logo')).'" style="text-decoration: none; -ms-interpolation-mode: bicubic; border: 0; height: auto; width: 100%; max-width: 166px; display: block;" title="Image" width="166"/>';
                        } else {
                            $title = '<h3>'.config('title').'</h3>';
                        }
                        return Email::parse($template, ['content' => $message, 'brand' => $title]);
                    }
                ]);
		

	  	return Helper::redirect()->to(route('confirmation', ['id' => $sub->id]))->with('success', e('You have been successfully subscribed.'));
	}
	/**
	 * Webhook
	 *
	 * @author GemPixel <https://gempixel.com> 
	 * @version 6.0
	 * @return void
	 */
	public static function webhook($request){
		if(!$request || !method_exists($request, 'isPost') || !$request->isPost()){
			if(!headers_sent()) header('Allow: POST');
			http_response_code(405);
			return null;
		}

		if(!config('stripe') || !config('stripe')->enabled || !config('stripe')->public || !config('stripe')->secret) {
            
            \GemError::log('Payment system "Stripe" not enabled or configured.');

			http_response_code(503);
            return null;
        }

		$stripeConfig = config('stripe');

		if(empty($stripeConfig->sig)){
			\GemError::log('Stripe Webhook: signing secret is not configured.');
			http_response_code(503);
			return null;
		}

		$payload = method_exists($request, 'getBody') ? $request->getBody() : file_get_contents("php://input");

		if(!$payload || empty($payload)) {
			http_response_code(400);
			return null;
		}

		$signature = method_exists($request, 'serverString')
			? $request->serverString('HTTP_STRIPE_SIGNATURE')
			: (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');

		try {
			$e = self::verifiedWebhookEvent($payload, $signature, (string) $stripeConfig->sig);
			$identity = self::webhookIdentity($e);
		} catch(\Stripe\Exception\SignatureVerificationException $exception) {
			\GemError::log('Stripe Webhook: signature verification failed.');
			http_response_code(400);
			return null;
		} catch(\UnexpectedValueException $exception) {
			\GemError::log('Stripe Webhook: invalid payload.');
			http_response_code(400);
			return null;
		}

		if(!self::isSupportedWebhookEventType((string) ($e->type ?? ''))){
			http_response_code(200);
			return null;
		}

		$ey = $e->data->object;

		$ey->paymentmethod = "Stripe";

		if($ey->object == "charge"){
			$pdo = DB::get_db();
			$lockName = self::webhookLockName($identity['event_id'], $identity['object_id']);
			self::acquireWebhookLock($pdo, $lockName);
			$successfulPayment = null;

			try {
			$pdo->beginTransaction();

			if(self::webhookAlreadyProcessed($identity['event_id'], $identity['object_id'])){
				$pdo->commit();
				http_response_code(200);
				return null;
			}

			if(!$user = DB::user()->where("customerid", $ey->customer)->first()){
				$pdo->commit();
				http_response_code(202);
				return print("User does not exist");
			}

			try {
				$subscription = self::resolveWebhookSubscription($ey, $user, (string) $stripeConfig->secret);
			} catch (\InvalidArgumentException $exception) {
				$pdo->commit();
				\GemError::log('Stripe Webhook: billing context mismatch.');
				http_response_code(422);
				return null;
			}

			if(!$subscription){
				$pdo->commit();
				http_response_code(202);
				return null;
			}

			if($ey->paid == true && $ey->status == "succeeded"){

				if($subscription->plan == "yearly"){

					$new_expiry = date("Y-m-d H:i:s", strtotime("+1 year", $e->created));

				}elseif($subscription->plan == "lifetime"){

					$new_expiry = date("Y-m-d H:i:s", strtotime("+20 years", $e->created));

				}else{

					$new_expiry = date("Y-m-d H:i:s", strtotime("+1 month", $e->created));
				}

				$payment = DB::payment()->create();
	    		$payment->date = Helper::dtime('now');
				$payment->cid = $identity['object_id'];
				$payment->tid = $identity['event_id'];
	    		$payment->amount =  $ey->amount / 100;
	    		$payment->userid =  $user->id;
	    		$payment->status = "Completed";
	    		$payment->expiry =  $new_expiry;
	    		$payment->data =  json_encode($ey);

				$payment->save();

				$amount = $subscription->amount + ($ey->amount / 100);

				$subscription->amount = $amount;
				$subscription->expiry = $new_expiry;
				$subscription->status = "Active";
				$subscription->save();

				$user->expiration = $new_expiry;
				$user->pro = 1;
				$user->planid = $subscription->planid;
				$user->save();

				$successfulPayment = [$user, $subscription->planid, $payment->id()];
	   			    		

			}elseif ($ey->status == "failed") {
				$payment = DB::payment()->create();
				$payment->date = Helper::dtime('now');
				$payment->cid = $identity['object_id'];
				$payment->tid = $identity['event_id'];
				$payment->amount =  $ey->amount / 100;
				$payment->userid =  $user->id;
				$payment->status = "Failed";
				$payment->data =  json_encode($ey);
				$payment->save();
				
				if(config('smtp')->user){
					$mailer = Email::factory('smtp', [
						'username' => config('smtp')->user,
						'password' => config('smtp')->pass,
						'host' => config('smtp')->host,
						'port' => config('smtp')->port
					]);
				} else {
					$mailer = Email::factory();
				}
		
				$mailer->from([config('email'), config('title')])
					   ->template(View::$path.'/email.php');
		
				$message = '<table><tbody><tr>
								<td>Subscription - '.$subscription->plan.'</td>
								<td class="alignright">'.str_replace('$', '&#36;', \Helpers\App::currency(config('currency'), $ey->amount / 100)).'</td>
							</tr>
							<tr class="soustotal">
								<td class="alignright" width="80%">Subtotal</td>
								<td class="alignright">'.str_replace('$', '&#36;', \Helpers\App::currency(config('currency'), $ey->amount / 100)).'</td>
							</tr>																												
							<tr class="total">
								<td class="alignright" width="80%">Failed on '.$ey->source->brand.' ('.$ey->source->last4.')</td>
							</tr></tbody></table>';
		
				$mailer->to(config('email'))
						->send([
							'subject' => e('Payment failed'),
							'message' => function($template, $data) use ($message) {
								if(config('logo')){
									$title = '<img align="center" alt="Image" border="0" class="center autowidth" src="'.uploads(config('logo')).'" style="text-decoration: none; -ms-interpolation-mode: bicubic; border: 0; height: auto; width: 100%; max-width: 166px; display: block;" title="Image" width="166"/>';
								} else {
									$title = '<h3>'.config('title').'</h3>';
								}
								return Email::parse($template, ['content' => $message, 'brand' => $title]);
							}
						]);	
			}
			$pdo->commit();
			} catch (\Throwable $exception) {
				if($pdo->inTransaction()) $pdo->rollBack();
				throw $exception;
			} finally {
				self::releaseWebhookLock($pdo, $lockName);
			}

			if($successfulPayment !== null){
				\Core\Plugin::dispatch('payment.success', $successfulPayment);
			}
		}
		http_response_code(200);
	}

	/**
	 * Verify and parse a Stripe webhook event.
	 *
	 * @throws \UnexpectedValueException
	 * @throws \Stripe\Exception\SignatureVerificationException
	 */
	public static function verifiedWebhookEvent(string $payload, string $signature, string $signingSecret): \Stripe\Event{
		if(trim($signingSecret) === ''){
			throw new \UnexpectedValueException('Stripe webhook signing secret is not configured.');
		}

		if(trim($payload) === ''){
			throw new \UnexpectedValueException('Stripe webhook payload is empty.');
		}

		return \Stripe\Webhook::constructEvent($payload, $signature, $signingSecret);
	}

	/**
	 * Return the provider identifiers used to deduplicate a webhook.
	 *
	 * @return array{event_id:string, object_id:string}
	 */
	public static function webhookIdentity(\Stripe\Event $event): array{
		$eventId = trim((string) ($event->id ?? ''));
		$objectId = trim((string) ($event->data->object->id ?? ''));

		if($eventId === '' || $objectId === ''){
			throw new \UnexpectedValueException('Stripe webhook provider identifiers are missing.');
		}

		return ['event_id' => $eventId, 'object_id' => $objectId];
	}

	/**
	 * Only events whose contained charge state is authoritative may mutate billing.
	 */
	public static function isSupportedWebhookEventType(string $eventType): bool{
		return in_array($eventType, ['charge.succeeded', 'charge.failed'], true);
	}

	public static function subscriptionGrantsEntitlement(string $status): bool{
		return $status === 'active';
	}

	public static function webhookChargeContextIsValid(int $chargeAmount, string $chargeCurrency, int $expectedAmount, string $expectedCurrency): bool{
		return $chargeAmount === $expectedAmount
			&& hash_equals(strtolower($expectedCurrency), strtolower($chargeCurrency));
	}

	public static function invoiceSubscriptionId(object $invoice): string{
		return self::stripeObjectId($invoice->parent->subscription_details->subscription ?? $invoice->subscription ?? null);
	}

	/**
	 * @return array{invoice_id:string, charge_id:string, payment_intent_id:string}
	 */
	public static function invoicePaymentContext(object $invoicePayment): array{
		return [
			'invoice_id' => self::stripeObjectId($invoicePayment->invoice ?? null),
			'charge_id' => self::stripeObjectId($invoicePayment->payment->charge ?? null),
			'payment_intent_id' => self::stripeObjectId($invoicePayment->payment->payment_intent ?? null),
		];
	}

	/**
	 * @return array{interval:string, start:int, end:int}
	 */
	public static function subscriptionBillingContext(object $subscription): array{
		$item = $subscription->items->data[0] ?? null;

		return [
			'interval' => trim((string) ($item->price->recurring->interval ?? $item->plan->interval ?? $subscription->plan->interval ?? '')),
			'start' => (int) ($item->current_period_start ?? $subscription->current_period_start ?? 0),
			'end' => (int) ($item->current_period_end ?? $subscription->current_period_end ?? 0),
		];
	}

	private static function resolveWebhookSubscription(object $charge, object $user, string $secret): ?object{
		$chargeId = trim((string) ($charge->id ?? ''));
		$chargeAmount = (int) ($charge->amount ?? -1);
		$chargeCurrency = trim((string) ($charge->currency ?? ''));
		$invoiceId = self::stripeObjectId($charge->invoice ?? null);
		$paymentIntentId = self::stripeObjectId($charge->payment_intent ?? null);
		$invoicePaymentContext = [
			'invoice_id' => '',
			'charge_id' => '',
			'payment_intent_id' => '',
		];

		if($chargeId === '' || $chargeAmount < 0 || $chargeCurrency === ''){
			throw new \InvalidArgumentException('Stripe charge context is incomplete.');
		}

		$stripe = new \Stripe\StripeClient($secret);

		if($invoiceId === '' && $paymentIntentId !== ''){
			$invoicePayments = $stripe->invoicePayments->all([
				'payment' => [
					'type' => 'payment_intent',
					'payment_intent' => $paymentIntentId,
				],
				'limit' => 1,
			]);
			$invoicePayment = $invoicePayments->data[0] ?? null;

			if(is_object($invoicePayment)){
				$invoicePaymentContext = self::invoicePaymentContext($invoicePayment);
				$invoiceId = $invoicePaymentContext['invoice_id'];
			}
		}

		if($invoiceId === ''){
			$subscription = DB::subscription()->where('userid', $user->id)->where('tid', $chargeId)->first();
			$expectedAmount = $subscription ? (int) round(((float) $subscription->amount) * 100) : -1;

			if(!$subscription || !self::webhookChargeContextIsValid($chargeAmount, $chargeCurrency, $expectedAmount, (string) config('currency'))){
				throw new \InvalidArgumentException('Stripe one-time charge does not match its local checkout.');
			}

			return $subscription;
		}

		$invoice = $stripe->invoices->retrieve($invoiceId, []);
		$providerSubscriptionId = self::invoiceSubscriptionId($invoice);
		$invoiceChargeId = $invoicePaymentContext['charge_id'] ?: self::stripeObjectId($invoice->charge ?? null);
		$invoicePaymentIntentId = $invoicePaymentContext['payment_intent_id'];
		$invoiceCustomerId = self::stripeObjectId($invoice->customer ?? null);
		$expectedAmount = (int) (($charge->paid ?? false) ? ($invoice->amount_paid ?? -1) : ($invoice->amount_due ?? -1));
		$expectedCurrency = (string) ($invoice->currency ?? '');

		if($providerSubscriptionId === ''
			|| ($invoiceChargeId !== '' && !hash_equals($chargeId, $invoiceChargeId))
			|| ($paymentIntentId !== '' && $invoicePaymentIntentId !== '' && !hash_equals($paymentIntentId, $invoicePaymentIntentId))
			|| ($invoiceCustomerId !== '' && !hash_equals((string) $user->customerid, $invoiceCustomerId))
			|| !self::webhookChargeContextIsValid($chargeAmount, $chargeCurrency, $expectedAmount, $expectedCurrency)
			|| !hash_equals(strtolower((string) config('currency')), strtolower($expectedCurrency))){
			throw new \InvalidArgumentException('Stripe invoice does not match the signed charge.');
		}

		return DB::subscription()
			->where('userid', $user->id)
			->where('tid', $providerSubscriptionId)
			->first();
	}

	private static function stripeObjectId(mixed $value): string{
		if(is_string($value)) return trim($value);
		if(is_object($value) && isset($value->id)) return trim((string) $value->id);

		return '';
	}

	/**
	 * MySQL advisory lock names are limited to 64 characters.
	 */
	public static function webhookLockName(string $eventId, string $objectId): string{
		return 'stripe-webhook:'.substr(hash('sha256', $eventId."\0".$objectId), 0, 48);
	}

	private static function acquireWebhookLock(\PDO $pdo, string $lockName): void{
		$statement = $pdo->prepare('SELECT GET_LOCK(:lock_name, 10)');
		$statement->execute(['lock_name' => $lockName]);

		if((int) $statement->fetchColumn() !== 1){
			throw new \RuntimeException('Unable to acquire Stripe webhook lock.');
		}
	}

	private static function releaseWebhookLock(\PDO $pdo, string $lockName): void{
		$statement = $pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
		$statement->execute(['lock_name' => $lockName]);
	}

	/**
	 * Check both the Stripe event and contained object before applying entitlement.
	 */
	public static function webhookAlreadyProcessed(string $eventId, string $objectId, ?callable $lookup = null): bool{
		$lookup ??= static function(string $column, string $value): bool{
			return (bool) DB::payment()->where($column, $value)->first();
		};

		return $lookup('tid', $eventId) || $lookup('cid', $objectId);
	}
	/**
	 * Create Plan
	 *
	 * @author GemPixel <https://gempixel.com> 
	 * @version 6.0
	 * @return void
	 */
	public static function createplan($plan){		

		$stripe = new \Stripe\StripeClient(config('stripe')->secret);

		try {
			$product = $stripe->products->create([
			  "name" => $plan->name,
			  "type" => "service",
			]);      
		} catch (\Exception $e) {
			back()->with('danger', $e->getMessage());
			exit;
		}

		try {
			$planMonthly = $stripe->plans->create([
				"amount" => $plan->price_monthly*100,
				"interval" => "month",
				"nickname" => "{$plan->name} - Monthly",
				"product" => $product->id,           
				"currency" => strtolower(config("currency")),
				"id" => $plan->slug."monthly"
			]);      
		} catch (\Exception $e) {
			back()->with('danger', $e->getMessage());
			exit;
		}
	  
		try {
			$planYearly = $stripe->plans->create([
				"amount" => $plan->price_yearly*100,
				"interval" => "year",
				"nickname" => "{$plan->name} - Yearly",
				"product" => $product->id,            
				"currency" => strtolower(config("currency")),
				"id" => $plan->slug."yearly"
			]);
			
		} catch (\Exception $e) {
			back()->with('danger', $e->getMessage());
			exit;         
		}

		return $product->id;
    }
	/**
	 * Update Plan
	 *
	 * @author GemPixel <https://gempixel.com> 
	 * @version 6.0
	 * @return void
	 */
	    public static function updateplan($request, $plan){

			$stripe = new \Stripe\StripeClient(config('stripe')->secret);
			$providerData = json_decode((string) $plan->data, true);
			$productid = $providerData['stripe'] ?? null;

			if(!$productid){
				$existingPrice = $plan->price_monthly ? $plan->slug."monthly" : $plan->slug."yearly";
				$existingPlan = $stripe->plans->retrieve($existingPrice);
				$productid = $existingPlan->product;
			}

		if($request->price_monthly != $plan->price_monthly){
			$oldplan = $stripe->plans->retrieve($plan->slug."monthly");
			$productid = $oldplan->product;
			$oldplan->delete();
		
			try {
				$planMonthly = $stripe->plans->create([
					"amount" => $request->price_monthly*100,
					"interval" => "month",
					"nickname" => "{$plan->name} - Monthly",
					"product" => $productid,
					"currency" => strtolower(config("currency")),
					"id" => $plan->slug."monthly"
				]);      
			} catch (\Exception $e) {
				back()->with('danger', $e->getMessage());
				exit;
			}		  			                
		}

		if($request->price_yearly != $plan->price_yearly){
			$oldplan = $stripe->plans->retrieve($plan->slug."yearly");
			$productid = $oldplan->product;
			$oldplan->delete();
		
			try {
				$planYearly = $stripe->plans->create([
					"amount" => $request->price_yearly*100,
					"interval" => "year",
					"nickname" => "{$plan->name} - Yearly",
					"product" => $productid,
					"currency" => strtolower(config("currency")),
					"id" => $plan->slug."yearly"
				]);      
			} catch (\Exception $e) {
				back()->with('danger', $e->getMessage());
				exit;
			}
		  			                
		}

		return $productid;
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

		$stripe = new \Stripe\StripeClient(config('stripe')->secret);

		try {
			
			$product = $stripe->products->retrieve($plan->data->stripe);

		} catch (\Exception $e) {
			
			$product = $stripe->products->create([
				"name" => $plan->name,
				"type" => "service",
			]);
		}		

		try {
			$planMonthly = $stripe->plans->create([
				"amount" => $plan->price_monthly*100,
				"interval" => "month",
				"nickname" => "{$plan->name} - Monthly",
				"product" => $product->id,           
				"currency" => strtolower(config("currency")),
				"id" => $plan->slug."monthly"
			]);      
		} catch (\Exception $e) {
			
		}
	  
		try {

			$planYearly = $stripe->plans->create([
				"amount" => $plan->price_yearly*100,
				"interval" => "year",
				"nickname" => "{$plan->name} - Yearly",
				"product" => $product->id,            
				"currency" => strtolower(config("currency")),
				"id" => $plan->slug."yearly"
			]);
			
		} catch (\Exception $e) {
			
		}

		return $product->id;
	}
	/**
	 * Cancel User Subscription
	 *
	 * @author GemPixel <https://gempixel.com> 
	 * @version 6.0
	 * @param [type] $user
	 * @param [type] $subscription
	 * @return void
	 */
	public static function cancel($user, $subscription){
	
		$stripe = new \Stripe\StripeClient(config('stripe')->secret);
		
		if(!$subscription->tid || empty($subscription->tid)) return null;

		try {

			$response = $stripe->subscriptions->retrieve($subscription->tid, []);			

		} catch (\Exception $e) {
			\GemError::log('Stripe Cancel Error: '.$e->getMessage(), ['userid' => $user->id]);
			return null;			
		}

		$billingContext = self::subscriptionBillingContext($response);

		if($billingContext['interval'] === 'year'){
			
			try{
				$invoices = $stripe->invoices->all(['subscription' => $subscription->tid, 'limit' => 1]);
			}catch (\Exception $e) {
				return null;			
			}

			$invoice = $invoices->data[0] ?? null;
			if(!is_object($invoice)) return null;

			$charge = self::stripeObjectId($invoice->charge ?? null);
			$paymentIntent = '';
			if($charge === ''){
				$invoicePayments = $stripe->invoicePayments->all(['invoice' => self::stripeObjectId($invoice), 'limit' => 1]);
				$invoicePayment = $invoicePayments->data[0] ?? null;
				if(is_object($invoicePayment)){
					$paymentContext = self::invoicePaymentContext($invoicePayment);
					$charge = $paymentContext['charge_id'];
					$paymentIntent = $paymentContext['payment_intent_id'];
				}
			}

			if(($charge === '' && $paymentIntent === '') || $billingContext['start'] <= 0 || $billingContext['end'] <= 0) return null;

			$amount = $invoice->total / 100;
			$start = $billingContext['start'];
			$end = $billingContext['end'];

			$yStart = date('Y', $start);
			$yEnd = date('Y', $end);

			$mStart = date('m', $start);
			$mEnd = date('m', $end);

			$diff = (($yEnd - $yStart) * 12) + ($mEnd - $mStart);

			$refund = round(($diff - 1) * $amount / 12, 2);

			$refundRequest = ['amount' => (int) round($refund * 100)];
			if($charge !== ''){
				$refundRequest['charge'] = $charge;
			}else{
				$refundRequest['payment_intent'] = $paymentIntent;
			}

			$refund = $stripe->refunds->create($refundRequest);

			$stripe->subscriptions->cancel($subscription->tid, []);
		
			return $refund;

		}else{
			try {
				$stripe->subscriptions->update($subscription->tid, ['cancel_at_period_end' => true]);
			}catch (\Exception $e) {
				return null;			
			}

			return null;
		}

		return null;
	}
	/**
	 * Create coupon
	 *
	 * @author GemPixel <https://gempixel.com> 
	 * @version 6.1
	 * @param $request
	 * @return void
	 */
	public static function createcoupon($request){
		
		$stripe = new \Stripe\StripeClient(config('stripe')->secret);

		try{
			
			$coupon = $stripe->coupons->create([
				'name'  => $request->name,
				'percent_off' => $request->discount,
				'duration' => 'repeating',
				'duration_in_months' => 12,
			]);

			return $coupon->id;

		}catch (\Exception $e) {
			\GemError::log('Stripe Coupon Error: '.$e->getMessage(), ['userid' => $user->id]);
			return null;			
		}
	}
	/**
	 * Create Tax
	 *
	 * @author GemPixel <https://gempixel.com> 
	 * @version 6.2
	 * @param [type] $request
	 * @return void
	 */
	public static function createtax($request){
		
		$stripe = new \Stripe\StripeClient(config('stripe')->secret);

		try{
			
			$rate = $stripe->taxRates->create([
				'display_name' => $request->name,
				'percentage' => $request->rate,
				'inclusive' => false,
			]);

			return $rate->id;

		}catch (\Exception $e) {
			\GemError::log('Stripe Rate Error: '.$e->getMessage());
			return null;			
		}
	}
	/**
	 * Create a Stripe Payment Link
	 *
	 * @author GemPixel <https://gempixel.com> 
	 * @version 6.3.3
	 * @param [type] $planid
	 * @param [type] $user
	 * @return void
	 */
	private static function paymentLink($request, $type, $plan, $user){
		
		$stripe = new \Stripe\StripeClient(config('stripe')->secret);	

		$term = e($plan->name);
		$text = e("First month");
		$price = $plan->price_monthly;
		$planid = $plan->slug."monthly";
	
		if($type == "yearly" && $plan->price_yearly){
			$term = e($plan->name);
			$text = e("First year");
			$price = $plan->price_yearly;
			$planid = $plan->slug."yearly";				
		}

		if($type == "lifetime" && $plan->price_lifetime){
			$term = e($plan->name);
			$text = e("Lifetime");
			$price = $plan->price_lifetime;
			$planid = $plan->slug."lifetime";			
		}

		if(!$user->customerid){

			try {

				$customer = $stripe->customers->create([
					"email" => $user->email,
					"description" => "$term - $text for {$user->email}",
					"name" => clean($request->name),
					"address" => [
						"line1" => clean($request->address),
						"city" => clean($request->city),
						"country" => clean($request->country),
						"postal_code" => clean($request->zip),
						"state" => clean($request->state)
					]
				]);

			} catch(\Exception $e) {
				\GemError::log('Stripe Error: '.$e->getMessage());
				return back()->with("warning", e("An error ocurred, please try again. You have not been charged."));
			}

			if(!isset($customer->id)) return back()->with("warning", e("An error ocurred, please try again. You have not been charged."));

			$user->customerid = $customer->id;			
			$user->save();		  
		}

		if($type == 'lifetime'){
			$checkout = [
				'success_url' => route('dashboard',['success' => 'true']),
				'cancel_url' => route('dashboard', ['success' => 'false']),
				'line_items' => [
					[
					  'price_data' => [
						  'currency' => strtolower(config('currency')),
						  'product_data' =>[
							  'name' => $term.'-'.$text,
						  ],
						  'unit_amount' => $price * 100
					  ],
					  'quantity' => 1,
					],
				],
				'mode' => 'payment',
				'customer' => $user->customerid
			];
		} else {
			$checkout = [
				'success_url' => route('dashboard',['success' => true]),
				'cancel_url' => route('dashboard', ['success' => false]),
				'line_items' => [
					[
					  'price' => $planid,
					  'quantity' => 1,
					],
				],
				'mode' => 'subscription',
				'customer' => $user->customerid
			];
		}

		$uniqueid = Helper::rand(16);

		$sub = DB::subscription()->create();
		$sub->tid = null;
		$sub->userid = $user->id;
		$sub->plan = $type;
		$sub->planid = $plan->id;
		$sub->status = "Pending";
		$sub->amount = "0";
		$sub->date = Helper::dtime();
		$sub->expiry = Helper::dtime();
		$sub->lastpayment = Helper::dtime();
		$sub->data = NULL;
		$sub->uniqueid = $uniqueid;
		$sub->save();

		if($request->coupon && $coupon = DB::coupons()->where('code', clean($request->coupon))->first()){
			
			$valid = true;

			if(strtotime("now") > strtotime(date("Y-m-d 11:59:00", strtotime($coupon->validuntil)))) $valid = false;

            if($coupon->maxuse > 0 && $coupon->used >= $coupon->maxuse) $valid = false;

			if($valid) {				
				$coupon->used++;
				$coupon->save();
				$price = round((1 - ($coupon->discount / 100)) * $price, 2);
				$sub->coupon = $coupon->id;
				$sub->save();
				$coupon->data = json_decode($coupon->data);

				$checkout['discounts'] = [[
					'coupon' => $coupon->data->stripe
				]];
			}
		}

		if($tax = DB::taxrates()->whereRaw('countries LIKE ?', ["%".clean($request->country)."%"])->first()){
			$tax->data = json_decode($tax->data);

			$checkout['line_items'][0]['tax_rates'] = [$tax->data->stripe];
		}	

		try{
			$session = $stripe->checkout->sessions->create($checkout);
		} catch(\Exception $e){
			\GemError::log('Stripe Error: '.$e->getMessage());
			return back()->with("warning", e("An error ocurred, please try again. You have not been charged."));
		}

		return Helper::redirect()->to($session->url);
	}
}
