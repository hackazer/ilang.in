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

namespace User;

use Core\DB;
use Core\View;
use Core\Request;
use Core\Auth;
use Core\Response;
use Core\Helper;
use Core\Email;
use Helpers\Emails;
use Models\User;

class Verification {	
    /**
     * Get verified page 
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public function index(){

        View::set('title', e('Get Verified'));

        $user = Auth::user();

        $verifications = DB::verification()->where('userid', $user->id)->orderByDesc('created_at')->find();

        $user->address = json_decode($user->address);

        return View::with('user.verification', compact('user', 'verifications'))->extend('layouts.dashboard');
    }
    /**
     * Verify
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.5
     * @param \Core\Request $request
     * @return void
     */
    public function verify(Request $request){

        $user = Auth::user();

        if(DB::verification()->where('userid', $user->id)->where('status', 0)->first()){
            return Helper::redirect()->back()->with('danger', e('You already requested a verification. As soon as we verify the document, we will let you know.'));
        }

        if(!$file = $request->file('file')){
            return back()->with('danger', e('Please upload a document so we can verify you.'));
        }     

        if(!$request->billingname || !$request->address || !$request->city || !$request->state || !$request->zip || !$request->country){
            return back()->with('danger', e('Please fill everything so we can verify you.'));
        }   
            
        if(!$file->mimematch || !in_array($file->ext, ['jpg', 'pdf'])) return Helper::redirect()->back()->with('danger', e('Document must be either a PDF or a JPG (Max 2MB).'));

        if($file->sizekb > 2048) return Helper::redirect()->back()->with('danger', e('Document must be either a PDF or a JPG (Max 2MB)'));

        $filename = md5(time().Helper::rand(32)).'.'.$file->ext;

        $request->move($file, appConfig('app.storage')['files']['path'], $filename);

        $user->address = json_encode([
            "name"  	=>	$request->billingname ? Helper::RequestClean($request->billingname) : '',
            "company" 	=>	$request->company ? Helper::RequestClean($request->company) : '',
            "address" 	=>	Helper::RequestClean($request->address),
            "city" 		=>	Helper::RequestClean($request->city), 
            "state" 	=>	Helper::RequestClean($request->state),
            "zip" 		=>	Helper::RequestClean($request->zip),
            "country" 	=>	Helper::RequestClean($request->country)
        ]);

        $user->save();

        $verification = DB::verification()->create();
        $verification->userid = $user->id;
        $verification->file = $filename;
        $verification->status = 0;
        $verification->created_at = Helper::dtime();
        $verification->save();

        Emails::notifyAdmin([
            'subject' => 'New Verification Request', 
            'message' => 'A customer ('.$user->email.') just requested a verification. You can now review the request in the admin panel.'
        ]);

        return back()->with('success', e('Thank you. We will process your document as soon as possible and verify you.'));
    }
}