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

use Core\Request;
use Core\DB;
use Core\Auth;
use Core\Helper;
use Core\View;
use Models\User;

class QR {
    
    use \Traits\Links;

    /**
     * Verify Permission
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     */
    public function __construct(){

        if(User::where('id', Auth::user()->rID())->first()->has('qr') === false){
			return \Models\Plans::notAllowed();
		}
    }
    /**
     * QR Generator
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param \Core\Request $request
     * @return void
     */
    public function index(Request $request){

        $qrs = [];

        $count = DB::qrs()->where('userid', Auth::user()->rID())->count();

        $total = Auth::user()->hasLimit('qr');

        foreach(DB::qrs()->where('userid', Auth::user()->rID())->orderByDesc('id')->paginate(12) as $qr){
            $qr->data = json_decode($qr->data);
            
            if($qr->urlid && $url = DB::url()->where('id', $qr->urlid)->first()){
                $qr->scans = $url->click;
            }
            $qr->channels = \Core\DB::tochannels()->join(DBprefix.'channels', [DBprefix.'tochannels.channelid' , '=', DBprefix.'channels.id'])->where(DBprefix.'tochannels.itemid', $qr->id)->where('type', 'qr')->findMany();

            $qrs[] = $qr;
        }

        View::set('title', e('QR Codes'));

        return View::with('qr.index', compact('qrs', 'count', 'total'))->extend('layouts.dashboard');
    }
    /**
     * Create QR Code
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param \Core\Request $request
     * @return void
     */
    public function create(Request $request){        

        if(Auth::user()->teamPermission('qr.create') == false){
			return back()->with('danger', e('You do not have this permission. Please contact your team administrator.'));
		}

        $count = DB::qrs()->where('userid', Auth::user()->rID())->count();

        $total = Auth::user()->hasLimit('qr');

        \Models\Plans::checkLimit($count, $total);
        
        View::set('title', e('Create QR'));

        \Helpers\CDN::load("spectrum");
		
		View::push('<script type="text/javascript">																			    						    				    
						$("#bg").spectrum({
					        color: "rgb(255,255,255)",					        
					        preferredFormat: "rgb",
						});	
                        $("#fg").spectrum({
					        color: "rgb(0,0,0)",					        
					        preferredFormat: "rgb"
						});
                    </script>', 'custom')->tofooter();  

        if(\Helpers\QR::hasImagick()){
            View::push('<script type="text/javascript">
                            $("#gbg").spectrum({
                                color: "rgb(255,255,255)",                                
                                preferredFormat: "rgb"
                            });	
                            $("#gfg").spectrum({
                                color: "rgb(0,0,0)",                                
                                preferredFormat: "rgb"
                            });
                            $("#gfgs").spectrum({
                                color: "rgb(0,0,0)",                                
                                preferredFormat: "rgb"
                            });
                            $("#eyecolor").spectrum({
                                preferredFormat: "rgb",
                                allowEmpty:true                            
                            });
                        </script>', 'custom')->tofooter();                 
        }

        if($request->link){
            View::push('<script type="text/javascript">
                            $(document).ready(function(){
                                $("a[href=#link]").click();
                            });
                        </script>', 'custom')->tofooter();
        }
                
        $domains = [];
        foreach(array_reverse(\Helpers\App::domains(), true) as $domain){
            $domains[] = $domain;
        }  
        
        return View::with('qr.new', compact('domains'))->extend('layouts.dashboard');
    }
    /**
     * Preview QR Codes
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param \Core\Request $request
     * @return void
     */
    public function preview(Request $request){
        
        if(!$request->name) return \Core\Response::factory('<div class="alert alert-danger p-3">'.e('Please enter a name for your QR code.').'</div>')->send(); 

        if(!\Helpers\QR::typeExists($request->type)) return \Core\Response::factory('<div class="alert alert-danger p-3">'.e('Invalid QR format or missing data').'</div>')->send(); 
 
        try{

            if($request->type == "file"){
                try{
                    
                    \Helpers\QR::validateFile();

                    $request->type = "text";
                    $request->text = "Preview not available for file uploads. You can save the QR code to create it.";

                } catch(\Exception $e){
                    return \Core\Response::factory('<div class="alert alert-danger p-3">'.$e->getMessage().'</div>')->send(); 
                }
            }
            $margin = is_numeric($request->margin) && $request->margin <= 10 ? $request->margin : 0;

            $data = \Helpers\QR::factory($request, 1000, $margin)->format('png');
            
            if($request->mode == 'gradient'){
                $data->gradient([$request->gradient['start'], $request->gradient['stop']], $request->gradient['bg'], $request->gradient['direction'], $request->eyecolor ?? null);
            } else {
                $data->color($request->fg, $request->bg, $request->eyecolor ?? null);
            }

            if($request->matrix){
                $data->module($request->matrix);
            }

            if($request->eye){
                $data->eye($request->eye);
            }

            if($request->selectlogo){
                $data->withLogo(PUB.'/static/images/'.$request->selectlogo.'.png', 150);
            }

            if($image = $request->file('logo')){
                $data->withLogo($image->location, 150);
            }

            $qr = $data->create('uri');

        } catch(\Exception $e){
            return \Core\Response::factory('<div class="alert alert-danger p-3">'.$e->getMessage().'</div>')->send();
        }

        $response = '<img src="'.$qr.'" class="img-responsive w-100 mw-50">';

        return \Core\Response::factory($response)->send();
    }
    /**
     * Generate and Save QR Code
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.3
     * @param \Core\Request $request
     * @return void
     */
    public function save(Request $request){

        if(Auth::user()->teamPermission('qr.create') == false){
			return back()->with('danger', e('You do not have this permission. Please contact your team administrator.'));
		}
    
        if(!\Helpers\QR::typeExists($request->type)) return back()->with('danger',  e('Invalid QR format or missing data'));

        if(!$request->name) return back()->with('danger', e('Please enter a name for your QR code.'));

        $count = DB::qrs()->where('userid', Auth::user()->rID())->count();

        $total = Auth::user()->hasLimit('qr');

        \Models\Plans::checkLimit($count, $total);
        try{
            if($request->type == 'file'){
            
                $input = call_user_func([\Helpers\QR::class, 'type'.ucfirst($request->type)]);
                $data = uploads('qr/files/'.$input);
    
            }else {
                $input = $request->{$request->type} ? $request->{$request->type} : $request->text;
                $data = call_user_func([\Helpers\QR::class, 'type'.ucfirst($request->type)], clean($input));
            }  
        }  catch(\Exception $e){
            return back()->with('danger',  $e->getMessage());
        }

        $qrdata = [];

        $qrdata['type'] = clean($request->type);

        $qrdata['data'] = $input;

        if($request->mode == 'gradient'){
            $qrdata['gradient'] = [
                [clean($request->gradient['start']), clean($request->gradient['stop'])], 
                clean($request->gradient['bg']), 
                clean($request->gradient['direction'])
            ];
        } else {
            $qrdata['color'] = ['bg' => clean($request->bg), 'fg' => clean($request->fg)];
        }


        if($request->selectlogo){
            $qrdata['definedlogo'] = $request->selectlogo.'.png';
        }
        

        if($image = $request->file('logo')){
            
            if(!$image->mimematch || !in_array($image->ext, ['jpg', 'png'])) return Helper::redirect()->back()->with('danger', e('Logo must be either a PNG or a JPEG (Max 500kb).'));

            $filename = "qr_logo".Helper::rand(6).$image->name;

			$request->move($image, appConfig('app.storage')['qr']['path'], $filename);

            $qrdata['custom'] = $filename;
        }

        if($request->matrix){
            $qrdata['matrix'] = clean($request->matrix);
        }

        if($request->eye){
            $qrdata['eye'] = clean($request->eye);
            $qrdata['eyecolor'] = $request->eyecolor ?? null;
        }

        if($request->margin && is_numeric($request->margin) && $request->margin <= 10){
            $qrdata['margin'] = $request->margin;
        }

        $url = null;
        $alias = \substr(md5(Auth::user()->rID().$data.Helper::rand(12)), 0, 8);

        if(!in_array($request->type, ['text', 'sms','wifi','staticvcard'])){                        
            $url = DB::url()->create();
            $url->userid = Auth::user()->rID();
            $url->url = $data;
            $url->alias = \substr(md5(Auth::user()->rID().$data), 0, 6);

            if($request->domain && $this->validateDomainNames(trim($request->domain), Auth::user(), false)){
                $url->domain = clean($request->domain);
            }

            $url->date = Helper::dtime();
            $url->save();
        }

        $qr = DB::qrs()->create();        
        $qr->userid = Auth::user()->rID();
        $qr->alias = $alias;
        $qr->urlid = $url ? $url->id : null;
        $qr->name = clean($request->name);
        $qr->data = json_encode($qrdata);
        $qr->status = 1;
        $qr->created_at = Helper::dtime();
        $qr->save();

        if($url){
            $url->qrid = $qr->id;
            $url->save();
        }
        
        return Helper::redirect()->to(route('qr.edit', [$qr->id]))->with('success',  e('QR Code has been successfully generated.'));
    }   
    /**
     * Edit QR
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param integer $id
     * @return void
     */
    public function edit(int $id){

        if(Auth::user()->teamPermission('qr.edit') == false){
			return back()->with('danger', e('You do not have this permission. Please contact your team administrator.'));
		}

        if(!$qr = DB::qrs()->where('id', $id)->where('userid', Auth::user()->rID())->first()){
            return back()->with('danger', 'QR does not exist.');
        }    
        
        $qr->data = json_decode($qr->data);
        
        $url = null;
        if($qr->urlid){
            $url = DB::url()->first($qr->urlid);
        }

        \Helpers\CDN::load("spectrum");
		
		View::push('<script type="text/javascript">																			    						    				    
						$("#bg").spectrum({
					        color: "'.(isset($qr->data->color->bg) ? $qr->data->color->bg : 'rba(255,255,255)').'",
					        showInput: true,
					        preferredFormat: "rgb"
						});	
                        $("#fg").spectrum({
					        color: "'.(isset($qr->data->color->fg) ? $qr->data->color->fg : 'rgb(0,0,0)').'",
					        showInput: true,
					        preferredFormat: "rgb"
						});
                    </script>', 'custom')->tofooter(); 

        if(\Helpers\QR::hasImagick()){
            View::push('<script type="text/javascript">
                            $("#gbg").spectrum({
                                color: "'.(isset($qr->data->gradient) ? $qr->data->gradient[1] : 'rgb(255,255,255)').'",
                                showInput: true,
                                preferredFormat: "rgb"
                            });	
                            $("#gfg").spectrum({
                                color: "'.(isset($qr->data->gradient) ? $qr->data->gradient[0][0] : 'rgb(0,0,0)').'",
                                showInput: true,
                                preferredFormat: "rgb"
                            });
                            $("#gfgs").spectrum({
                                color: "'.(isset($qr->data->gradient) ? $qr->data->gradient[0][1] : 'rgb(0,0,0)').'",
                                showInput: true,
                                preferredFormat: "rgb"
                            });
                            $("#eyecolor").spectrum({
                                color: "'.(isset($qr->data->eyecolor) ? $qr->data->eyecolor : '').'",
                                preferredFormat: "rgb",
                                allowEmpty:true                            
                            });
                        </script>', 'custom')->tofooter();                 
        }

        View::set('title', e("Edit QR").' '. $qr->name);

        $domains = false;
        if(!in_array($qr->data->type, ['text', 'sms','wifi','staticvcard'])){      
            $domains = [];
            foreach(array_reverse(\Helpers\App::domains(), true) as $domain){
                $domains[] = $domain;
            }  
        }
        return View::with('qr.edit', compact('qr', 'url', 'domains'))->extend('layouts.dashboard');
    }    
    /**
     * Update QR
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.3.2
     * @param \Core\Request $request
     * @param integer $id
     * @return void
     */
    public function update(Request $request, int $id){

        if(Auth::user()->teamPermission('qr.edit') == false){
			return back()->with('danger', e('You do not have this permission. Please contact your team administrator.'));
		}

        \Gem::addMiddleware('DemoProtect');
        

        if(!$qr = DB::qrs()->where('id', $id)->where('userid', Auth::user()->rID())->first()){
            return back()->with('danger', 'QR does not exist.');
        }

        if(!$request->name) return back()->with('danger', e('Please enter a name for your QR code.'));

        $qr->data = json_decode($qr->data);

        try{

            if($request->type == 'file'){
                            
                if($file = $request->file('file')){            
                    
                    if(file_exists(appConfig('app.storage')['qr']['path'].'/files/'.$qr->data->data)){
                        unlink( appConfig('app.storage')['qr']['path'].'/files/'.$qr->data->data);
                    }

                    $input = call_user_func([\Helpers\QR::class, 'type'.ucfirst($request->type)]);
                    $data = uploads('qr/files/'.$input);                                
                } else {
                    $input = $qr->data->data;
                    $data = uploads('qr/files/'.$input); 
                }

            }else {
                
                $input = $request->{$request->type} ? $request->{$request->type} : $request->text;
                $data = call_user_func([\Helpers\QR::class, 'type'.ucfirst($request->type)], clean($input));
                
            }  
        } catch(\Exception $e){
            return Helper::redirect()->to(route('qr.edit', [$qr->id]))->with('danger',  $e->getMessage());
        }    

        $qr->data->data = $input;

        if($request->mode == 'gradient'){
            unset($qr->data->color);
            $qr->data->gradient = [
                [clean($request->gradient['start']), clean($request->gradient['stop'])], 
                clean($request->gradient['bg']), 
                clean($request->gradient['direction'])
            ];
        } else {
            unset($qr->data->gradient);
            $qr->data->color = ['bg' => clean($request->bg), 'fg' => clean($request->fg)];
        }


        if($request->selectlogo){
            $qr->data->definedlogo = $request->selectlogo.'.png';
        }
        

        if($image = $request->file('logo')){
            
            if(!$image->mimematch || !in_array($image->ext, ['jpg', 'png'])) return Helper::redirect()->back()->with('danger', e('Logo must be either a PNG or a JPEG (Max 500kb).'));

            $filename = "qr_logo".Helper::rand(6).$image->name;

			$request->move($image, appConfig('app.storage')['qr']['path'], $filename);
            
            unlink( appConfig('app.storage')['qr']['path'].'/'.$qr->data->custom);

            $qr->data->custom = $filename;
        }

        if($request->matrix){
            $qr->data->matrix = clean($request->matrix);
        }

        if($request->eye){
            $qr->data->eye = clean($request->eye);
            $qr->data->eyecolor = clean($request->eyecolor);
        }

        if($request->margin && is_numeric($request->margin) && $request->margin <= 10){
            $qr->data->margin = $request->margin;
        }
        
        if($qr->urlid && $url = DB::url()->where('id', $qr->urlid)->first()){
            $url->url = $data;
            
            if($request->domain && $this->validateDomainNames(trim($request->domain), Auth::user(), false)){
                $url->domain = clean($request->domain);
            }

            $url->save();
        }

        $qr->name = clean($request->name);
        $qr->data = json_encode($qr->data);

        $qr->save();     

        unlink( appConfig('app.storage')['qr']['path'].'/'.$qr->filename);
        
        return Helper::redirect()->to(route('qr.edit', [$qr->id]))->with('success',  e('QR Code has been successfully updated.'));
    }
    /**
     * Delete QR
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param integer $id
     * @param string $nonce
     * @return void
     */
    public function delete(int $id, string $nonce){

        if(Auth::user()->teamPermission('qr.delete') == false){
			return back()->with('danger', e('You do not have this permission. Please contact your team administrator.'));
		}

        \Gem::addMiddleware('DemoProtect');

        if(!Helper::validateNonce($nonce, 'qr.delete')){
            return Helper::redirect()->back()->with('danger', e('An unexpected error occurred. Please try again.'));
        }

        if(!$qr = DB::qrs()->where('id', $id)->where('userid', Auth::user()->rID())->first()){
            return back()->with('danger', 'QR does not exist.');
        }
        
        unlink( appConfig('app.storage')['qr']['path'].'/'.$qr->filename);

        $qr->delete();

        if($url = DB::url()->where('qrid', $id)->where('userid', Auth::user()->rID())->first()){
            $this->deleteLink($url->id);
        }
        
        return back()->with('success', e('QR has been successfully deleted.'));
    }
    /**
     * Duplicate
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.4
     * @param integer $id
     * @return void
     */
    public function duplicate(int $id){
        if(Auth::user()->teamPermission('qr.edit') == false){
			return Helper::redirect()->to(route('qr'))->with('danger', e('You do not have this permission. Please contact your team administrator.'));
		}
        
        $user = Auth::user();

        $count = DB::qrs()->where('userid', Auth::user()->rID())->count();

        $total = Auth::user()->hasLimit('qr');

        \Models\Plans::checkLimit($count, $total);
        
        if(!$qr = DB::qrs()->where('id', $id)->where('userid', Auth::user()->rID())->first()){
            return back()->with('danger', 'QR does not exist.');
        }

        $newurl = null;
        
        $alias = \substr(md5(Auth::user()->rID().Helper::rand(12)), 0, 8);

        if($qr->urlid){
            $url = DB::url()->first($qr->urlid);
            $newurl = DB::url()->create();
            $newurl->userid = Auth::user()->rID();
            $newurl->url = $qr->data;
            $newurl->alias = $alias;
            $newurl->date = Helper::dtime();
            $newurl->save();
        }

        $new = DB::qrs()->create();        
        $new->userid = Auth::user()->rID();
        $new->alias = $alias;
        $new->urlid = $newurl ? $newurl->id : null;
        $new->name = $qr->name.' ('.e('Copy').')';
        $new->data = $qr->data;
        $new->status = 1;
        $new->created_at = Helper::dtime();
        $new->save();

        if($newurl){
            $newurl->qrid = $qr->id;
            $newurl->save();
        }

        return Helper::redirect()->back()->with('success', e('Item has been successfully duplicated.'));
    }
}