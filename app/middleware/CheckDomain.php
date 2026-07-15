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
use Core\Helper;
use Core\View;

final class CheckDomain extends Middleware {    
	/**
	 * Check pointed domain
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 */
	public function handle(Request $request) {

        $currenturi = trim(str_replace($request->path(), '', $request->uri(false)), '/');    

        $currenturinoscheme = str_replace(['https://', 'http://'], '', $currenturi);

        if(!in_array(str_replace(['https://', 'http://'], '', config('url')), [$currenturinoscheme, str_replace('www.', '', $currenturinoscheme)])){
            
            $host = \idn_to_utf8(Helper::parseUrl($request->host(), 'host'));

            if($domain = \Core\DB::domains()->whereRaw("domain = ? OR domain = ?", ["http://".$host, "https://".$host])->first()){
                if($domain->redirect){
                    header("Location: {$domain->redirect}");
                    exit;
                }
            }
            
            $domains_names = explode("\n", config('domain_names'));
            $domains_names = array_map('trim', $domains_names);
            
            if(in_array($currenturi, $domains_names)){
                header("Location: ".config('url'));
                exit;
            }

            View::set('title', e('Great! Your domain is working.'));
            View::with('gates.domain')->extend('layouts.auth'); 
            exit;
        }

        return true;
	}
} 