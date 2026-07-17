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

use Core\Request;
use Core\View;
use Core\Helper;
use Core\Auth;
use Core\DB;
use Helpers\AuthThrottle;
use Helpers\FacebookOAuth;
use Helpers\PasswordPolicy;
use Models\User;
use Models\Plans;

class Users {
    
    /** 
     * Regenerate Authentication Token 
     * @param bool
     */
    private $regenerateToken = false;

    private const THROTTLE_MESSAGE = 'Too many authentication attempts. Please try again in one hour.';

    private AuthThrottle $authThrottle;

    public function __construct(?AuthThrottle $authThrottle = null){
        $this->authThrottle = $authThrottle ?? new AuthThrottle();
    }

    /**
     * Login Page
     * 
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public function login(){
        
        View::set('title', e('Login to your account'));
        View::set("description","Login to your account and bookmark your favorite sites.");

        if(_STATE == 'DEMO') {
            View::push('<script>if(this != top){top.location.href = this.location.href;}</script>', 'custom')->toheader();
        }

        return View::with('auth.login')->extend('layouts.auth');
    }

    /**
     * Validate Login
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param \Core\Request $request
     * @return void
     */
    public function loginAuth(Request $request){
        
        if(is_null($request->email)) return Helper::redirect()->back()->with('danger', e('Please enter a valid email or username.'));
        
        if(is_null($request->password)) return Helper::redirect()->back()->with('danger', e('Wrong email and password combination.'));

        $identity = is_scalar($request->email) ? (string) $request->email : '';
        $ip = $request->ip();

        if($this->authThrottle->isBlocked(AuthThrottle::LOGIN_SCOPE, $identity, $ip)){
            return Helper::redirect()->back()->with('danger', e(self::THROTTLE_MESSAGE));
        }

        \Core\Plugin::dispatch('login.verify', $request);

        if(Helper::Email($request->email)){
            $user = User::where("email", $request->email)->first();
        } else {
            $user = User::where("username", $request->email)->first();
        }

        if(!$user){
            $this->authThrottle->recordFailure(AuthThrottle::LOGIN_SCOPE, $identity, $ip);
            return Helper::redirect()->back()->with('danger', e('Wrong email and password combination.'));
        }

        Helper::set("hashCost", 8);
        
        if(Helper::validatePass($request->password, $user->password)){

            $this->authThrottle->clear(AuthThrottle::LOGIN_SCOPE, $identity, $ip);

            \Core\Plugin::dispatch('login.verified', [$request, $user]);

            // Check if banned
            if($user->banned){
                return Helper::redirect()->back()->with("warning",e("You have been banned due to abuse. Please contact us for clarification."));
            }
            // Check if inactive
            if(!$user->active){
                return Helper::redirect()->back()->with("danger",e("You haven't activated your account. Please check your email for the activation link. If you haven't received any emails from us, please contact us."));
            }    
            
            // Check if expired
			if(strtotime($user->expiration) < time()){				
                $user->pro = 0;
                $user->save();
			}

			// If not pro set as free plan
			if(!$user->pro){
				if(is_null($user->planid) || $user->plan() ){
					if($plan = Plans::where("free", "1")->where('status', 1)->orderByDesc('id')->first()){
						$user->planid = $plan->id;
                        $user->save();
					}
				}
			}
            // Check 2FA
            if(!empty($user->secret2fa)) {
                $key = Helper::encrypt($user->secret2fa);
				$request->session('2FAKEY', $key);
				return Helper::redirect()->to(route('login.2fa'))->with("success", e("Please enter the 2FA access code to login."));
			}

            session_regenerate_id();    

            if($this->regenerateToken || empty($user->auth_key)){
                $newAuthKey = Helper::Encode($user->email.$user->id.uniqid().rand(0, 99999));
                $user->auth_key = $newAuthKey;
                $user->save();
            } 
            
            // Set Session
            $sessiondata = Helper::encrypt(json_encode(["loggedin" => true, "key" => $user->auth_key.$user->id]));
            
            if($request->rememberme){
              // Set Cookie for 14 days
              $request->cookie(Auth::COOKIE, $sessiondata, 14*24*60);
            }else{
              $request->session(Auth::COOKIE, $sessiondata);
            }

            if($location = $request->session('redirect')){
                $request->unset('redirect');
                return Helper::redirect()->to($location)->with('success', e('You have been successfully registered.'));
            }
            
            return Helper::redirect()->to(route('dashboard'));
        }

        $this->authThrottle->recordFailure(AuthThrottle::LOGIN_SCOPE, $identity, $ip);

        return Helper::redirect()->back()->with('danger', e('Wrong email and password combination.')); 
    }
    /**
     * Validate User 2FA
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public function login2FA(Request $request){

        if(!$request->session('2FAKEY')) return Helper::redirect()->to(route('login'));
        
        View::set('title', e("Enter your 2FA access code"));

        View::push(assets('frontend/libs/imask/imask.min.js'), 'js')->tofooter();
        View::push(assets('input-mask.min.js'), 'js')->tofooter();

        return View::with('auth.2fa')->extend('layouts.auth');
    }
    /**
     * Validate 2FA
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param \Core\Request $request
     * @return void
     */
    public function login2FAValidate(Request $request){

        if(!$request->session('2FAKEY')) return Helper::redirect()->to(route('login'));
        
        $key = Helper::decrypt($request->session('2FAKEY'));

        if(!$user = DB::user()->where('secret2fa', $key)->first()){
            return Helper::redirect()->to(route('login'))->with("danger", e("Invalid token. Please try again."));
        }

        $identity = isset($user->id) ? (string) $user->id : (string) $user->email;
        $ip = $request->ip();

        if($this->authThrottle->isBlocked(AuthThrottle::TWO_FACTOR_SCOPE, $identity, $ip)){
            return Helper::redirect()->back()->with('danger', e(self::THROTTLE_MESSAGE));
        }

        $request->secret = is_scalar($request->secret)
            ? str_replace(' ', '', (string) $request->secret)
            : '';

        if(strlen($request->secret) != 6){
            $this->authThrottle->recordFailure(AuthThrottle::TWO_FACTOR_SCOPE, $identity, $ip);
            return Helper::redirect()->back()->with("danger", e("Invalid token. Please try again."));
        }

        $gAuth = new \Helpers\GoogleAuthenticator();

        if(!$gAuth->checkCode($user->secret2fa, $request->secret)){
            $this->authThrottle->recordFailure(AuthThrottle::TWO_FACTOR_SCOPE, $identity, $ip);
            return Helper::redirect()->back()->with("danger", e("Invalid token. Please try again."));
        }

        $this->authThrottle->clear(AuthThrottle::TWO_FACTOR_SCOPE, $identity, $ip);

        session_regenerate_id();

        if($this->regenerateToken){            
            $newAuthKey = Helper::Encode($user->email.$user->id.uniqid().rand(0, 99999));
            $user->auth_key = $newAuthKey;
            $user->save();
        }    

        // Set Session
        $sessiondata = Helper::encrypt(json_encode(["loggedin" => true, "key" => $user->auth_key.$user->id]));
        
        $request->cookie(Auth::COOKIE, $sessiondata, 14*24*60);
        
        if($location = $request->session('redirect')){
            $request->unset('redirect');
            return Helper::redirect()->to($location)->with('success', e('You have been successfully logged.'));
        }

        return Helper::redirect()->to(route('dashboard'));
    }

    /**
     * Register page
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public function register(){

        if(!config("user") || config("private") || config("maintenance")) return Helper::redirect()->to(route('login'))->with("danger", e("We are not accepting users at this time."));

        View::set('title', e('Register and manage your urls'));
        View::set("description", e('Register an account and gain control over your urls. Manage them, edit them or remove them without hassle.'));

        $page = DB::page()->where('category', 'terms')->first();

        return View::with('auth.register', compact('page'))->extend('layouts.auth');
    }

    /**
     * Validate Register
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param \Core\Request $request
     * @return void
     */
    public function registerValidate(Request $request){

        if(!config("user") || config("private") || config("maintenance")) return Helper::redirect()->to(route('login'))->with("danger", e("We are not accepting users at this time."));

        $request->save('email', $request->email);
        $request->save('username', $request->username);

        if(!$request->email || !$request->username || !$request->password) return Helper::redirect()->back()->with('danger', e('The email, the username and the password are required.'));

        $user = DB::user()->create();

        if(!$request->validate($request->email, 'email')) return Helper::redirect()->back()->with('danger', e('Please enter a valid email.'));
        
        if(DB::user()->where('email', $request->email)->first()) return Helper::redirect()->back()->with('danger', e('An account is already associated with this email.'));

        $user->email = Helper::RequestClean($request->email);

        if(!$request->validate($request->username, 'username')) return Helper::redirect()->back()->with('danger', e('Please enter a valid username.'));
        if(DB::user()->where('username', $request->username)->first()) return Helper::redirect()->back()->with('danger', e('Username already exists.'));

        $user->username = Helper::RequestClean($request->username);

        if(in_array($user->username, ['admin','moderator','owner','founder'])) return Helper::redirect()->back()->with('danger', e("This username cannot be used or already exists. Please choose another username"));
        
        if(!PasswordPolicy::allows($request->password)) return Helper::redirect()->back()->with('danger', e(PasswordPolicy::message()));

        if(strlen($request->password) > 64) return Helper::redirect()->back()->with('danger', e('Your password is too long. Passwords must be between 12 to 64 characters.'));

        if($request->password != $request->cpassword) return Helper::redirect()->back()->with('danger', e("Passwords don't match."));

        if(!$request->terms) return Helper::redirect()->back()->with('danger', e('You must agree to our terms of service.'));

        \Core\Plugin::dispatch('register.verify', $request);
        
        Helper::set("hashCost", 8);
        $user->password = Helper::Encode($request->password);

        $user->date = Helper::dtime();
        $user->api = md5(Helper::rand(32).time());
        $user->uniquetoken = Helper::rand(32);
        $user->public = 0;
        $user->auth_key = Helper::Encode($user->email.Helper::dtime());
        $user->active = config("user_activate") ? 0 : 1;   
        $user->save();
        $request->clear();
        
        if(config('affiliate')->enabled && $request->cookie('urid') && is_numeric($request->cookie('urid'))){
            $affiliate = DB::affiliates()->create();
            $affiliate->refid = clean($request->cookie('urid'));
            $affiliate->userid = $user->id;
            $affiliate->amount = "0.00";
            $affiliate->referred_on = $user->date;
            $affiliate->save();
        }

        if(config('user_activate')){

            \Helpers\Emails::renewEmail($user);            
            return Helper::redirect()->to(route('login'))->with('success', e("An email has been sent to activate your account. Please check your spam folder if you didn't receive it."));
        }
                
        \Helpers\Emails::registered($user);

        Auth::loginId($user->id);

        if($location = $request->session('redirect')){
            return Helper::redirect()->to($location)->with('success', e('You have been successfully registered.'));
        }

        return Helper::redirect()->to(route('dashboard'))->with('success', e('You have been successfully registered.'));
    }

    /**
     * Forgot Password page
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public function forgot(){
        
        View::set('title', e("Reset Password"));
        View::set('description', e("If you forgot your password, you can request a link to reset your password."));

        return View::with('auth.forgot')->extend('layouts.auth');
    }

    /**
     * Validate and send new password link
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param \Core\Request $request
     * @return void
     */
    public function forgotSend(Request $request){

        if(!$request->validate($request->email, 'email')) return back()->with('danger', e('Please enter a valid email.')); 

        if($user = DB::user()->where('email', clean($request->email))->first()){
            
            $user->uniquetoken = Helper::rand(32);
            $user->save();        
            
            \Helpers\Emails::reset($user);
        }

        return Helper::redirect()->to(route('login'))->with("success", e("If an active account is associated with this email, you should receive an email shortly."));
    }

    /**
     * Reset Password
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param string $token
     * @return void
     */
    public function reset(string $token){
        
        $data = explode('-', clean($token));

        if(!isset($data[1])) return Helper::redirect()->to(route('forgot'))->with("danger", e("Token has expired, please request another link."));

        $unique = $data[0];

        $expiry = $data[1];        

        if(!hash_equals(md5(AuthToken.": Expires on".strtotime(date('Y-m-d'))), $expiry)){
            return Helper::redirect()->to(route('forgot'))->with("danger", e("Token has expired, please request another link."));
        }

        if(!$user = DB::user()->where('uniquetoken', $unique)->first()){
            return Helper::redirect()->to(route('forgot'))->with("danger", e("Token has expired, please request another link."));
        }

        View::set('title', e("Reset Password"));

        return View::with('auth.reset', compact('token'))->extend('layouts.auth');
    }
   /**
    * Change Password
    *
    * @author GemPixel <https://gempixel.com> 
    * @version 6.0
    * @param \Core\Request $request
    * @param string $token
    * @return void
    */
    public function resetChange(Request $request, string $token){
        $data = explode('-', clean($token));

        if(!isset($data[1])) return Helper::redirect()->to(route('forgot'))->with("danger", e("Token has expired, please request another link."));

        $unique = $data[0];

        $expiry = $data[1];

        if(!hash_equals(md5(AuthToken.": Expires on".strtotime(date('Y-m-d'))), $expiry)){
            return Helper::redirect()->to(route('forgot'))->with("danger", e("Token has expired, please request another link."));
        }

        if(!$user = DB::user()->where('uniquetoken', $unique)->first()){
            return Helper::redirect()->to(route('forgot'))->with("danger", e("Token has expired, please request another link."));
        }

        if(!PasswordPolicy::allows($request->password)) return Helper::redirect()->back()->with('danger', e(PasswordPolicy::message()));

        if($request->password != $request->cpassword) return Helper::redirect()->back()->with('danger', e("Passwords don't match."));

        if(Helper::validatePass($request->password, $user->password)){
            return Helper::redirect()->back()->with('danger', e("Your new password cannot be the same as the old password."));
        }

        Helper::set("hashCost", 8);        
        $user->password = Helper::Encode($request->password);
        $user->auth_key = Helper::Encode($user->email.Helper::dtime());
        $user->uniquetoken = Helper::rand(32);
        $user->save();

        \Helpers\Emails::passwordChanged($user);
        
        return Helper::redirect()->to(route('login'))->with("success", e("Your password has been changed."));
    }

   /**
    * Activate Account
    *
    * @author GemPixel <https://gempixel.com> 
    * @version 6.0
    * @param string $token
    * @return void
    */
    public function activate(string $token){

        if(!$user = DB::user()->where('uniquetoken', clean($token))->first()){
            return Helper::redirect()->to(route('forgot'))->with("danger", e("Token has expired, please request another link."));
        }

        if(!$user->active){
            $user->active = 1;
            $user->uniquetoken = Helper::rand(32);
            $user->save();
            \Helpers\Emails::activate($user);
        }

        return Helper::redirect()->to(route('login'))->with("success", e("Your email has been successfully verified."));
    }

     /**
     * Invited
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param string $token
     * @return void
     */
    public function invited(string $token){
        
        if(!$user = DB::user()->where('uniquetoken', clean($token))->first()){
            return Helper::redirect()->to(route('login'))->with("danger", e("The invitation link has expired or is currently unavailable. Please contact administrator."));
        }

        View::set('title', e("Join Team"));

        $page = DB::page()->where('category', 'terms')->first();

        return View::with('auth.invite', compact('token', 'page', 'user'))->extend('layouts.auth');
    }
    /**
     * Accept Invitation
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param \Core\Request $request
     * @param string $token
     * @return void
     */
    public function acceptInvitation(Request $request, string $token){

        if(!$user = DB::user()->where('uniquetoken', clean($token))->first()){
            return Helper::redirect()->to(route('login'))->with("danger", e("The invitation link has expired or is currently unavailable. Please contact administrator."));
        }

        if(!$request->validate($request->username, 'username')) return Helper::redirect()->back()->with('danger', e('Please enter a valid username.'));

        if(DB::user()->where('username', $request->username)->first()) return Helper::redirect()->back()->with('danger', e('Username already exists.'));

        $user->username = Helper::RequestClean($request->username);

        if(in_array($user->username, ['admin','moderator','owner','founder'])) return Helper::redirect()->back()->with('danger', e("This username cannot be used or already exists. Please choose another username"));
        
        if(!PasswordPolicy::allows($request->password)) return Helper::redirect()->back()->with('danger', e(PasswordPolicy::message()));

        if($request->password != $request->cpassword) return Helper::redirect()->back()->with('danger', e("Passwords don't match."));

        if(!$request->terms) return Helper::redirect()->back()->with('danger', e('You must agree to our terms of service.'));
        
        Helper::set("hashCost", 8);

        $user->password = Helper::Encode($request->password);
        $user->date = Helper::dtime();
        $user->uniquetoken = Helper::rand(32);
        $user->active = 1;   
        $user->save();

        return Helper::redirect()->to(route('login'))->with('success', e('Your account has been successfully activated.'));
    }
    /**
     * Logout User
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public function logout(){
        Auth::logout();
        return Helper::redirect()->to(route('login'))->with('success', e('You have been successfully logged out.'));
    }

    /**
    * Login with Facebook
    *
    * @author GemPixel <https://gempixel.com> 
    * @version 6.0
    * @param \Core\Request $request
    * @return void
    */
    public function loginWithFacebook(Request $request){

        if(!config("fb_connect") || empty(config("facebook_app_id")) || empty(config("facebook_secret"))) return Helper::redirect()->to(route('login'))->with("danger", e("Sorry, Facebook connect is not available right now."));
        
        if($request->error) return Helper::redirect()->to(route('login'))->with("danger", e("You must grant access to this application to use your facebook account."));

        $oauth = new FacebookOAuth(
            (string) config("facebook_app_id"),
            (string) config("facebook_secret"),
            route('login.facebook')
        );

        $code = is_scalar($request->code) ? (string) $request->code : '';
        $state = is_scalar($request->state) ? (string) $request->state : '';

        if($code === '') {
            return Helper::redirect()->to($oauth->authorizationUrl());
        }

        try {
            $accessToken = $oauth->exchangeCode($code, $state);
            $response = $oauth->user($accessToken);
        } catch(\RuntimeException $e) {
            GemError::log('Facebook Auth: '.$e->getMessage());
            return Helper::redirect()->to(route('login'))->with("danger", e("An error has occurred. Please try again later."));
        }

        $email = is_scalar($response['email'] ?? null) ? (string) $response['email'] : '';
        $facebookId = is_scalar($response['id'] ?? null) ? (string) $response['id'] : '';

        if($email === '') return Helper::redirect()->to(route('login'))->with("danger", e("You must grant permission to this application to use your profile information."));
        
        // Check if email is already taken
        if(DB::user()->whereRaw("(auth != 'facebook' OR auth IS NULL)")->where('email', $email)->first()){
            return Helper::redirect()->to(route('login'))->with("danger", e("The email linked to your account has been already used. If you have used that, please login to your existing account otherwise please contact us.")); 
        }

        // Let's see if the user is registered
        if($user = DB::user()->where('auth', 'facebook')->whereAnyIs([['email' => $email], ['auth_id'=> $facebookId]])->first()){

            // Check Auth Key: If empty generate one
            if(empty($user->auth_key)){	
                $user->auth_key = Helper::Encode(Helper::rand(12));
                // Update database
                $user->save();
            }
            // Insert AuthID
            if(empty($user->auth_id) && $facebookId){
                // Update database
                $user->auth_id = $facebookId;
                $user->save();
            }

            // Check if banned
            if($user->banned){
                return Helper::redirect()->to(route('login'))->with("warning", e("You have been banned due to abuse. Please contact us for clarification."));
            }
            // Check if inactive
            if(!$user->active){
                return Helper::redirect()->to(route('login'))->with("danger", e("You haven't activated your account. Please check your email for the activation link. If you haven't received any emails from us, please contact us."));
            }

        }else{		      	
            // Let's register the user
            $auth_key = Helper::Encode(Helper::rand(12));
            
            $user = DB::user()->create();

            $user->email = Helper::clean($email,3,TRUE);
            $user->username = "";
            $user->password = Helper::Encode(Helper::rand(12));
            $user->date = Helper::dtime();
            $user->auth = "facebook";
            $user->auth_id = $facebookId ? clean($facebookId) : "";
            $user->api = Helper::rand(16);
            $user->auth_key = $auth_key;
            $user->uniquetoken = Helper::rand(32);

            \Helpers\Emails::registered($user);
            
            $user->save();
        }
        
        \Core\Auth::loginId($user->id);

        return Helper::redirect()->to(route('dashboard'))->with("success", e("Welcome! You have been successfully logged in."));
    }
    /**
     * Login with Twitter
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param \Core\Request $request
     * @return void
     */
    public function loginWithTwitter(Request $request){        
        if(!config("tw_connect") || empty(config("twitter_key")) || empty(config("twitter_secret"))) return Helper::redirect()->to(route('login'))->with("danger", e("Sorry, Twitter connect is not available right now."));

        // Check for error
        if($request->denied) return Helper::redirect()->to(route('login'))->with("danger", e("You must grant permission to this application to use your twitter account."));

        // Attempt to login
        if($request->oauth_verifier && $request->session('oauth_token') && $request->session('oauth_token_secret')){
           
            $twitteroauth = new \Abraham\TwitterOAuth\TwitterOAuth(config("twitter_key"), config("twitter_secret"), $request->session('oauth_token'), $request->session('oauth_token_secret'));

            $tw = $twitteroauth->oauth("oauth/access_token", ["oauth_verifier" => $request->oauth_verifier]);

            $twitteroauth = new \Abraham\TwitterOAuth\TwitterOAuth(config("twitter_key"), config("twitter_secret"), $tw['oauth_token'], $tw['oauth_token_secret']);

            $userInfo = $twitteroauth->get("account/verify_credentials", ['oauth_token' => $tw['oauth_token'], 'include_entities' => true, 'skip_status' => true, 'include_email' => true]);

            $userId = $userInfo && isset($userInfo->id) ? $userInfo->id : $tw['user_id'];

            if(!$userId) return Helper::redirect()->to(route('login'))->with("danger", e("And error occurred, please try again later."));

            if($userInfo && isset($userInfo->email) && DB::user()->whereRaw("(auth != 'twitter' OR auth IS NULL)")->where('email', $userInfo->email)->first()){
                return Helper::redirect()->to(route('login'))->with("danger", e("The email linked to your account has been already used. If you have used that, please login to your existing account otherwise please contact us.")); 
            }

            // Let's see if the user is registered
            if($user = DB::user()->where('auth', 'twitter')->where('auth_id', $userId)->first()){

                // Check Auth Key: If empty generate one
                if(empty($user->auth_key)){	
                    $user->auth_key = Helper::Encode(Helper::rand(12));
                    // Update database
                    $user->save();
                }

                // Check if banned
                if($user->banned){
                    return Helper::redirect()->to(route('login'))->with("warning", e("You have been banned due to abuse. Please contact us for clarification."));
                }
                // Check if inactive
                if(!$user->active){
                    return Helper::redirect()->to(route('login'))->with("danger", e("You haven't activated your account. Please check your email for the activation link. If you haven't received any emails from us, please contact us."));
                }
            } else {
                // Let's register the user
                $auth_key = Helper::Encode(Helper::rand(12));
                
                $user = DB::user()->create();

                $user->email = $userInfo && isset($userInfo->email) ? $userInfo->email : '';
                $user->username = "";
                $user->password = Helper::Encode(Helper::rand(12));
                $user->date = Helper::dtime();
                $user->auth = "twitter";
                $user->auth_id = $userId;
                $user->api = Helper::rand(16);
                $user->auth_key = $auth_key;
                $user->uniquetoken = Helper::rand(32);

                if(isset($userInfo->profile_image_url) && !empty($userInfo->profile_image_url)){
                    $file = \Core\File::factory($userInfo->profile_image_url, 'avatar');
                    $user->avatar = Helper::rand(16).$file->getFilename();
                    $file->copy($user->avatar);
                }
                
                \Helpers\Emails::registered($user);

                $user->save();
            }
            
            \Core\Auth::loginId($user->id);

            return Helper::redirect()->to(route('dashboard'))->with("success", e("Welcome! You have been successfully logged in."));
        }

        // The TwitterOAuth instance          
        $twitteroauth = new \Abraham\TwitterOAuth\TwitterOAuth(config("twitter_key"), config("twitter_secret"));

        try{
            
            $request_token = $twitteroauth->oauth("oauth/request_token", ["oauth_callback" => route('login.twitter')]);

        } catch(\Exception $e){
            
            GemError::log('Twitter Auth: '.$e->getMessage()."\n".$e->getTraceAsString());
            return Helper::redirect()->to(route('login'))->with("danger", e("Sorry, Twitter connect is not available right now."));
        }

        // Requesting authentication tokens, the parameter is the URL we will be redirected to  
        

        // Saving them into the session  
        $request->session('oauth_token', $request_token['oauth_token']);
        $request->session('oauth_token_secret', $request_token['oauth_token_secret']);

        // If everything goes well..  
        if($twitteroauth->getLastHttpCode() == 200){  
            // Let's generate the URL and redirect  
            $url = $twitteroauth->url("oauth/authorize", ["oauth_token" => $request_token['oauth_token']]); 
            return Helper::redirect()->to($url);

        } else { 
            return Helper::redirect()->to(route('login'))->with('danger', e('An error has occurred! Please make sure that you have set up this application as instructed.'));  
        }	
    }
    /**
     * Login with Google
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param \Core\Request $request
     * @return void
     */
    public function loginWithGoogle(Request $request){

        if(!config("gl_connect") || empty(config("google_cid")) || empty(config("google_cs"))) return Helper::redirect()->to(route('login'))->with("danger", e("Sorry, Google connect is not available right now."));

        $google = new \Helpers\GoogleAuth(config("google_cid"), config("google_cs"), route('login.google'));

        if(!$request->code){
            return Helper::redirect()->to($google->redirectURI($request));
        }

        try{

            $google->getAccessToken($request);

        } catch(\Exception $e){

            GemError::log('Google Auth: '.$e->getMessage()."\n".$e->getTraceAsString());
            return Helper::redirect()->to(route('login'))->with("danger", e("Sorry, Google connect is not available right now."));
            
        }
        
        $userInfo = $google->getUser();

        if(!isset($userInfo->id) || !isset($userInfo->email)) return Helper::redirect()->to(route('login'))->with("danger", e("You must grant permission to this application to use your Google account."));

        if(isset($userInfo->email) && DB::user()->whereRaw("(auth != 'google' OR auth IS NULL)")->where('email', $userInfo->email)->first()){
            return Helper::redirect()->to(route('login'))->with("danger", e("The email linked to your account has been already used. If you have used that, please login to your existing account otherwise please contact us.")); 
        }

        // Let's see if the user is registered
        if($user = DB::user()->where('auth', 'google')->where('auth_id', $userInfo->id)->first()){

            // Check Auth Key: If empty generate one
            if(empty($user->auth_key)){	
                $user->auth_key = Helper::Encode(Helper::rand(12));
                // Update database
                $user->save();
            }

            // Check if banned
            if($user->banned){
                return Helper::redirect()->to(route('login'))->with("warning", e("You have been banned due to abuse. Please contact us for clarification."));
            }
            // Check if inactive
            if(!$user->active){
                return Helper::redirect()->to(route('login'))->with("danger", e("You haven't activated your account. Please check your email for the activation link. If you haven't received any emails from us, please contact us."));
            }
        } else {
            // Let's register the user
            $auth_key = Helper::Encode(Helper::rand(12));
            
            $user = DB::user()->create();

            $user->email = isset($userInfo->email) ? $userInfo->email : '';
            $user->name = isset($userInfo->name) ? $userInfo->name : '';
            $user->username = "";
            $user->password = Helper::Encode(Helper::rand(12));
            $user->date = Helper::dtime();
            $user->auth = "google";
            $user->auth_id = $userInfo->id;
            $user->api = Helper::rand(16);
            $user->auth_key = $auth_key;
            $user->uniquetoken = Helper::rand(32);

            if(isset($userInfo->picture) && !empty($userInfo->picture)){
                $user->avatar = Helper::rand(16).'.jpg';
                \Core\File::factory($userInfo->picture, 'avatar')
                            ->copy($user->avatar);
            }

            \Helpers\Emails::registered($user);

            $user->save();
        }
        
        \Core\Auth::loginId($user->id);

        return Helper::redirect()->to(route('dashboard'))->with("success", e("Welcome! You have been successfully logged in."));
    }
    /**
     * SSO Login
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.4.1
     * @param [type] $token
     * @return void
     */
    public function sso($token){
        
        $data = explode('-', clean($token));

        if(!isset($data[1])) return Helper::redirect()->to(route('login'))->with("danger", e("Token has expired, please login manually."));

        $unique = $data[0];

        $expiry = $data[1];        

        if(!hash_equals(md5(AuthToken.": Expires on".strtotime(date('Y-m-d H'))), $expiry)){
            return Helper::redirect()->to(route('login'))->with("danger", e("Token has expired, please login manually"));
        }

        if(!$user = DB::user()->where('uniquetoken', $unique)->first()){
            return Helper::redirect()->to(route('login'))->with("danger", e("Token has expired, please login manually"));
        }

        if(empty($user->auth_key)){
            $newAuthKey = Helper::Encode($user->email.$user->id.uniqid().rand(0, 99999));
            $user->auth_key = $newAuthKey;
        } 

        $user->uniquetoken = strtolower(Helper::rand(32));
        $user->save();

        \Core\Auth::loginId($user->id);

        return Helper::redirect()->to(route('dashboard'))->with("success", e("Welcome! You have been successfully logged in."));        
    }
    /**
     * Return to old session
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.4
     * @return void
     */
    public function return(Request $request){
        
        if(!$id = \Helpers\App::loggedAs()){
            stop(404);
        }

        if(!$user = DB::user()->where('id', $id)->where('admin', 1)->first()){
            stop(404);
        }

        $request->unset('logged_as');

        \Core\Auth::logout();

        \Core\Auth::loginId($user->id);

        return Helper::redirect()->to(route('admin'))->with("success", e("Welcome! You have been successfully logged in."));    
    }
}
