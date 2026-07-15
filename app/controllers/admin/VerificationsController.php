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

namespace Admin;

use Core\DB;
use Core\View;
use Core\Request;
use Core\Auth;
use Core\Response;
use Core\Helper;
use Core\Email;
use Helpers\Emails;
use Models\User;

class Verifications {	
    /**
     * Get verified page 
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public function index(){

        View::set('title', e('Verifications'));

        $verifications = [];

        foreach(DB::verification()->orderByDesc('created_at')->paginate(15) as $verification){
            $verification->user = User::first($verification->userid);
            $verifications[] = $verification;
        }

        return View::with('admin.verifications.index', compact('verifications'))->extend('admin.layouts.main');
    }
    /**
     * Verify
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.5
     * @param integer $id
     * @return void
     */
    public function view(int $id){

        if(!$verification = DB::verification()->first($id)){
            return back()->with('danger', e('Verification request not found.'));
        }

        View::set('title', e('Verify User'));

        $user = User::first($verification->userid);
        $user->address = json_decode($user->address);

        return View::with('admin.verifications.view', compact('verification', 'user'))->extend('admin.layouts.main');
    }
    /**
     * Process Verification
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.5
     * @param \Core\Request $request
     * @param integer $id
     * @return void
     */
    public function process(Request $request, int $id){
        
        if(!$verification = DB::verification()->first($id)){
            return back()->with('danger', e('Verification request not found.'));
        }

        $user = User::first($verification->userid);
        
        $mailer = Emails::setup();

        $mailer->from([config('email'), config('title')])
               ->template(View::$path.'/email.php');

        if($request->action == "1"){
            $user->verified = 0;
            $user->save();

            $verification->status = 1;
            $verification->save();

            if($request->deletefile && file_exists(appConfig('app.storage')['files']['path'].'/'.$verification->file)){
                unlink(appConfig('app.storage')['files']['path'].'/'.$verification->file);
            }

            $mailer->to($user->email)
                    ->send([
                        'subject' => e('Your verification request has been rejected'),
                        'message' => function($template, $data) use ($array) {
                            if(config('logo')){
                                $title = '<img align="center" alt="'.config('title').'" border="0" class="center autowidth" src="'.uploads(config('logo')).'" style="text-decoration: none; -ms-interpolation-mode: bicubic; border: 0; height: auto; width: 100%; max-width: 166px; display: block;" title="'.config('title').'" width="166"/>';
                            } else {
                                $title = '<h3>'.config('title').'</h3>';
                            }
                            return Email::parse($template, ['content' => e('Thank you for taking the time to verify your account however at this moment we were not able to verify you. You can submit another document and request a new verification.'), 'brand' => $title]);
                        }
                    ]);

            return back()->with('success', e('Verification request has been rejected.'));
        }

        if($request->action == "2"){
            $user->verified = 1;
            $user->save();

            $verification->status = 2;
            $verification->save();

            if($request->deletefile && file_exists(appConfig('app.storage')['files']['path'].'/'.$verification->file)){
                unlink(appConfig('app.storage')['files']['path'].'/'.$verification->file);
            }

            $mailer->to($user->email)
            ->send([
                'subject' => e('Your verification request has been approved!'),
                'message' => function($template, $data) use ($array) {
                    if(config('logo')){
                        $title = '<img align="center" alt="'.config('title').'" border="0" class="center autowidth" src="'.uploads(config('logo')).'" style="text-decoration: none; -ms-interpolation-mode: bicubic; border: 0; height: auto; width: 100%; max-width: 166px; display: block;" title="'.config('title').'" width="166"/>';
                    } else {
                        $title = '<h3>'.config('title').'</h3>';
                    }
                    return Email::parse($template, ['content' => e('Thank you for taking the time to verify your account. Your verification request was approved!'), 'brand' => $title]);
                }
            ]);

            return back()->with('success', e('Verification request has been approved!'));
        }
    }
}