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
namespace Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;
use Core\Helper;

final class ShortenThrottle extends Middleware {

    /**
     * Rate limiter
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.4
     */
    private static $ratelimiter = [5, 1];

    /**
     * Throttle API
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param \Core\Request $request
     * @return void
     */
    public function handle(Request $request){

        if(CACHE === false) return true;
        
        if(!$key = $request->session('throttlekey')){
            $key = Helper::rand(12);
            $request->session('throttlekey', $key);
        }                    

        $count = Helper::cacheGet('shorten'.$key);

        if($count === null){
            $count = 0;
            Helper::cacheSet('shorten'.$key, 0,  60 * self::$ratelimiter[1]);
        }               

        $expiry = Helper::cacheExpiry('shorten'.$key);
        
        $response = new Response();
        $response->setHeader(['X-RateLimit-Limit', self::$ratelimiter[0]]);
        $response->setHeader(['X-RateLimit-Remaining', self::$ratelimiter[0] - ($count+1)]);
        $response->setHeader(['X-RateLimit-Reset', $expiry->getTimestamp()]);
        
        if($count > 0 && $count >= self::$ratelimiter[0]) {      
            $diff = $expiry->getTimestamp() - (new \DateTime('now'))->getTimestamp();
            $response->setBody(['error' => 429, 'message' => 'Too Many Requests. Please retry later.', 'Retry-After' => $diff])->json();
            exit;
        }    
        Helper::cacheUpdate('shorten'.$key, $count + 1);
    }
}