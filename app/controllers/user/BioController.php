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
use Core\Response;
use Core\DB;
use Core\Auth;
use Core\Helper;
use Core\View;
use Models\User;

class Bio {
    
    use \Traits\Links;

    /**
     * Verify Permission
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     */
    public function __construct(){

        if(User::where('id', Auth::user()->rID())->first()->has('bio') === false){
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
        $bios = [];

        $count = DB::profiles()->where('userid', Auth::user()->rID())->count();

        $total = Auth::user()->hasLimit('bio');

        foreach(DB::profiles()->where('userid', Auth::user()->rID())->orderByDesc('id')->paginate(15) as $bio){
            $bio->data = json_decode($bio->data);
            
            if($bio->urlid && $url = DB::url()->where('id', $bio->urlid)->first()){
                $bio->views = $url->click;
                $bio->url =  \Helpers\App::shortRoute($url->domain, $bio->alias);
                $bio->status = $url->status;
            }
            
            $bio->channels = \Core\DB::tochannels()->join(DBprefix.'channels', [DBprefix.'tochannels.channelid' , '=', DBprefix.'channels.id'])->where(DBprefix.'tochannels.itemid', $bio->id)->where('type', 'bio')->findMany();

            $bios[] = $bio;
        }
        $user = Auth::user();
        if(isset($user->profiledata) && $data = json_decode($user->profiledata)){

            if($request->importoldbio == 'true'){
                return $this->importBio();
            }

            View::push('<script>$(".col-md-9").prepend("<div class=\"card\"><div class=\"card-body text-center\">'.e('We have detected that you have an old bio page. Do you want to import it?<br><br><a href=\"?importoldbio=true\" class=\"btn btn-primary\">'.e('Import').'</a>').'</div></div>")</script>', 'custom')->toFooter();
        }

        View::set('title', e('Bio Pages'));
        
        View::push(assets('frontend/libs/clipboard/dist/clipboard.min.js'), 'js')->toFooter();

        return View::with('bio.index', compact('bios', 'count', 'total'))->extend('layouts.dashboard');
    }

     /**
     * Create Bio
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param \Core\Request $request
     * @return void
     */
    public function create(){

        if(Auth::user()->teamPermission('bio.create') == false){
			return Helper::redirect()->to(route('bio'))->with('danger', e('You do not have this permission. Please contact your team administrator.'));
		}

        $count = DB::profiles()->where('userid', Auth::user()->rID())->count();

        $total = Auth::user()->hasLimit('bio');

        \Models\Plans::checkLimit($count, $total);

        $domains = [];
        foreach(\Helpers\App::domains() as $domain){
            $domains[] = $domain;
        }    
        
        View::set('title', e('Create Bio'));

        \Helpers\CDN::load('spectrum');
        
        View::push('<script>var biolang = '.json_encode([
                'icon' => e('Icon'),
                'text' => e('Text'),
                'description' => e('Description'),
                'link' => e('Link'),
                'color' => e('Color'),
                'bg' => e("Background"),
                'style' => e('Style'),
                'rectangular' => e('Rectangular'),
                'rounded' => e('Rounded'),
                'transparent' => e('Transparent'),
                'email' => e('Email'),
                'amount' => e('Amount'),
                'currency' => e('Currency'),
                'phone' => e('Phone'),
                'file' => e('Image'),
                'fname' => e('First Name'),
                'lname' => e('Last Name'),
                'phone' => e('Phone'),
                'site' => e('Site'),
                'address' => e('Address'),
                'city' => e('City'),
                'state' => e('State'),
                'country' => e('Country'),
                'solid' => e('Solid'),
                'dotted' => e('Dotted'),
                'dashed' => e('Dashed'),
                'height' => e('Height'),
                'animation' => e('Animation'),
                'shake' => e('Shake'),
                'scale' => e('Scale'),
                'jello' => e('Jello'),
                'vibrate' => e('Vibrate'),
                'wobble' => e('Wobble'),
                'none' => e('None'),
        ]).';
        </script>', 'custom')->toHeader();
        
        View::push(assets('frontend/libs/fontawesome-picker/dist/css/fontawesome-iconpicker.min.css'))->toHeader();
        View::push(assets('frontend/libs/fontawesome-picker/dist/js/fontawesome-iconpicker.min.js'), 'script')->toFooter();
        View::push(assets('frontend/libs/font-selector/jquery.fontselect.min.css'))->toHeader();
        View::push(assets('animate.min.css'))->toHeader();
        View::push(assets('frontend/libs/font-selector/jquery.fontselect.min.js'), 'script')->toFooter();

        View::push(assets('bio.min.js'), 'script')->toFooter();

        View::push('<style>#preview .card{ 
            background: #fff;
        }
        #preview .card .btn-custom{
            background: #000;
            color: #fff;
        }
        #preview .card .btn-custom:hover{
            opacity: 0.8;
        }
        </style>', 'custom')->toHeader();

        \Helpers\CDN::load('simpleeditor');

        View::push("<script>                        
                        var texteditor = CKEDITOR.replace('editor');
                        $('input[name=icon]').iconpicker();
                    </script>", "custom")->toFooter();

        return View::with('bio.new', compact('domains'))->extend('layouts.dashboard');
    }
    /**
     * Save Biolink
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.3.2
     * @param \Core\Request $request
     * @return void
     */
    public function save(Request $request){

        if(Auth::user()->teamPermission('bio.create') == false){
			return Response::factory(['error' => true, 'message' => e('You do not have this permission. Please contact your team administrator.'),'token' => csrf_token()])->json();
		}

        $count = DB::profiles()->where('userid', Auth::user()->rID())->count();

        $total = Auth::user()->hasLimit('bio');

        \Models\Plans::checkLimit($count, $total);        

        $user = Auth::user();

        if(!$request->name) return Response::factory(['error' => true, 'message' => e('Please enter a name for your profile.'), 'token' => csrf_token()])->json();
    
        $data = [];

        if(!$request->data){
            return Response::factory(['error' => true, 'message' => e('Please add at least one link.'), 'token' => csrf_token()])->json();
        }

        if($request->custom){			
			if(strlen($request->custom) < 3){
				 return Response::factory(['error' => true, 'message' =>e('Custom alias must be at least 3 characters.'), 'token' => csrf_token()])->json();
                
			}elseif($this->wordBlacklisted($request->custom)){
				 return Response::factory(['error' => true, 'message' =>e('Inappropriate aliases are not allowed.'), 'token' => csrf_token()])->json();

			}elseif(DB::url()->where('custom', Helper::slug($request->custom))->whereRaw('(domain = ? OR domain = ?)', [$request->domain, ''])->first()){
				 return Response::factory(['error' => true, 'message' =>e('That alias is taken. Please choose another one.'), 'token' => csrf_token()])->json();

			}elseif(DB::url()->where('alias', Helper::slug($request->custom))->whereRaw('(domain = ? OR domain = ?)', [$request->domain, ''])->first()){
				 return Response::factory(['error' => true, 'message' =>e('That alias is taken. Please choose another one.'), 'token' => csrf_token()])->json();

			}elseif($this->aliasReserved($request->custom)){
				 return Response::factory(['error' => true, 'message' =>e('That alias is reserved. Please choose another one.'), 'token' => csrf_token()])->json();

			}elseif(!$user->pro && $this->aliasPremium($request->custom)){
				 return Response::factory(['error' => true, 'message' =>e('That is a premium alias and is reserved to only pro members.'), 'token' => csrf_token()])->json();
			}
		}

        if($image = $request->file('avatar')){
            
            if(!$image->mimematch || !in_array($image->ext, ['jpg', 'png', 'jpeg']) || $image->sizekb > 500) return Response::factory(['error' => true, 'message' => e('Avatar must be either a PNG or a JPEG (Max 500kb).'), 'token' => csrf_token()])->json();

            $filename = "profile_avatar".Helper::rand(6).$image->name;

			$request->move($image, appConfig('app.storage')['profile']['path'], $filename);

            $data['avatar'] = $filename;
        }

        $data['avatarenabled'] = $request->avatarenabled ? 1 : 0;

        if($image = $request->file('bgimage')){
            
            if(!$image->mimematch || !in_array($image->ext, ['jpg', 'png', 'jpeg']) || $image->sizekb > 1000) return Response::factory(['error' => true, 'message' => e('Background must be either a PNG or a JPEG (Max 1mb).'), 'token' => csrf_token()])->json();

            $filename = "profile_imagebg".Helper::rand(6).$image->name;

			$request->move($image, appConfig('app.storage')['profile']['path'], $filename);

            $data['bgimage']= $filename;
        }
        $urlids = [];

        foreach($request->data as $key => $value){
            
            if($value['type'] == 'link'){

                if(!$this->validate(clean($value['link'])) || !$this->safe($value['link']) || $this->phish($value['link']) || $this->virus($value['link'])) continue;

                $url = DB::url()->create();
                $url->url = clean($value['link']);
                $url->custom = null;
                $url->alias = null;
                $url->userid = $user->rID();
                $url->date = Helper::dtime();
                $url->save();
                $value['urlid'] = $url->id;
                $urlids[] = $url->id;
            }

            if($value['type'] == 'image' && $image = $request->file($key)){

                if(!$image->mimematch || !in_array($image->ext, ['jpg', 'png', 'jpeg']) || $image->sizekb > 500) return Response::factory(['error' => true, 'message' => e('Image must be either a PNG or a JPEG (Max 500kb).'), 'token' => csrf_token()])->json();

                $filename = "profile_imagetype".Helper::rand(6).$image->name;
    
                $request->move($image, appConfig('app.storage')['profile']['path'], $filename);
    
                $value['image'] = $filename;
            }

            if($value['type'] == 'product' && $image = $request->file($key)){
                if(!$image->mimematch || !in_array($image->ext, ['jpg', 'png', 'jpeg']) || $image->sizekb > 500) return Response::factory(['error' => true, 'message' => e('Image must be either a PNG or a JPEG (Max 500kb).'), 'token' => csrf_token()])->json();

                $filename = "profile_producttype".Helper::rand(6).$image->name;
    
                $request->move($image, appConfig('app.storage')['profile']['path'], $filename);
    
                $value['image'] = $filename;
            }

            $data['links'][$key] = in_array($value['type'], ['html', 'text']) ? array_map(function($value){
                return Helper::clean($value, 3, false, '<strong><i><a><b><u><img><iframe><ul><ol><li><p>');
            }, $value) :  array_map('clean', $value);
        }
        
        foreach($request->social as $key => $value){
            $data['social'][$key] = clean($value);
        }        

        $data['style']['bg'] = clean($request->bg);

        $data['style']['font'] = clean($request->fonts);

        $data['style']['gradient'] = array_map('clean', $request->gradient);

        $data['style']['buttoncolor'] = clean($request->buttoncolor);
        $data['style']['buttontextcolor'] = clean($request->buttontextcolor);
        $data['style']['textcolor'] = clean($request->textcolor);        
        $data['style']['custom'] = Helper::clean($request->customcss, 3);

        $alias = $request->custom ? Helper::slug($request->custom) : $this->alias();

        $url = DB::url()->create();
        $url->userid = $user->rID();
        $url->url = '';
        if($request->domain && $this->validateDomainNames(trim($request->domain), Auth::user(), false)){
            $url->domain = clean($request->domain);
        }
        $url->meta_title = clean($request->title);
        $url->meta_description = clean($request->description);
        $url->custom = $alias;
        $url->date = Helper::dtime();

        if($request->pass){
            $url->pass = clean($request->pass);
        }
                
        $url->save();

        $profile = DB::profiles()->create();        
        $profile->userid = $user->rID();
        $profile->alias = $alias;
        $profile->urlid = $url ? $url->id : null;
        $profile->name = clean($request->name);
        $profile->data = json_encode($data);
        $profile->status = 1;
        $profile->created_at = Helper::dtime();
        $profile->save();

        if(!empty($urlids) && is_array($urlids)){
            DB::url()->where_in('id', $urlids)->update(['profileid' => $profile->id]);
        }

        if($url){
            $url->profileid = $profile->id;
            $url->save();
        }

        return Response::factory(['error' => false, 'message' => e('Profile has been successfully created.'), 'token' => csrf_token(), 'html' => '<script>window.location="'.route('bio').'"</script>'])->json();
    }
    /**
     * Delete Profile
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param [type] $id
     * @return void
     */
    public function delete(int $id, string $nonce){
        
        \Gem::addMiddleware('DemoProtect');

        if(Auth::user()->teamPermission('bio.delete') == false){
			return Helper::redirect()->to(route('bio'))->with('danger', e('You do not have this permission. Please contact your team administrator.'));
		}

        if(!Helper::validateNonce($nonce, 'bio.delete')){
            return Helper::redirect()->back()->with('danger', e('An unexpected error occurred. Please try again.'));
        }

        if(!$bio = DB::profiles()->where('id', $id)->where('userid', Auth::user()->rID())->first()){
            return back()->with('danger', e('Profile does not exist.'));
        }

        $bio->delete();

        if($url = DB::url()->where('profileid', $id)->where('userid', Auth::user()->rID())->first()){
            $this->deleteLink($url->id);
        }
        return back()->with('success', e('Profile has been successfully deleted.'));
    }
    /**
     * Edit bio Link
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.3.2
     * @param integer $id
     * @return void
     */
    public function edit(Request $request, int $id){

        if(Auth::user()->teamPermission('bio.edit') == false){
			return Helper::redirect()->to(route('bio'))->with('danger', e('You do not have this permission. Please contact your team administrator.'));
		}

        if(!$bio = DB::profiles()->where("userid", Auth::user()->rID())->where('id', $id)->first()){
            return back()->with('danger', e('Profile does not exist.'));
        }

        $domains = [];
        foreach(\Helpers\App::domains() as $domain){
            $domains[] = $domain;
        }   

        $url = DB::url()->first($bio->urlid);

        $bio->data = json_decode($bio->data);
        $bio->responses = json_decode($bio->responses);

        if($request->downloadqr){
            if(in_array($request->downloadqr, ['png', 'pdf', 'svg'])){
                
                $data = \Helpers\QR::factory(\Helpers\App::shortRoute($url->domain, $url->alias.$url->custom), 1000)->format($request->downloadqr);

                return \Core\File::contentDownload('Bio-Qr-'.$bio->alias.'.'.$data->extension(), function() use ($data) {
                    return $data->string();
                });
            }
        }  
        
        if($request->newsletterdata){
			$emails = $bio->responses->newsletter;
			\Core\File::contentDownload('emails.csv', function() use ($emails){
				echo "ID, Email\n";
				foreach($emails as $i => $email){					
					echo ($i+1).",{$email}\n";
				}
			});
			exit;
		}

        View::set('title', e('Update Bio').' '.$bio->name);

        \Helpers\CDN::load('spectrum');
        View::push(assets('frontend/libs/clipboard/dist/clipboard.min.js'), 'js')->toFooter();
        View::push('<script>
            
            var appurl = "'.config('url').'";

            var biolang = '.json_encode([
                'icon' => e('Icon'),
                'text' => e('Text'),
                'description' => e('Description'),
                'link' => e('Link'),
                'color' => e('Color'),
                'bg' => e("Background"),
                'style' => e('Style'),
                'rectangular' => e('Rectangular'),
                'rounded' => e('Rounded'),
                'transparent' => e('Transparent'),
                'email' => e('Email'),
                'amount' => e('Amount'),
                'currency' => e('Currency'),
                'phone' => e('Phone'),
                'file' => e('Image'),
                'fname' => e('First Name'),
                'lname' => e('Last Name'),
                'phone' => e('Phone'),
                'site' => e('Site'),
                'address' => e('Address'),
                'city' => e('City'),
                'state' => e('State'),
                'country' => e('Country'),
                'solid' => e('Solid'),
                'dotted' => e('Dotted'),
                'dashed' => e('Dashed'),
                'stats' => e('View Stats'),
                'height' => e('Height'),
                'animation' => e('Animation'),
                'shake' => e('Shake'),
                'scale' => e('Scale'),
                'jello' => e('Jello'),
                'vibrate' => e('Vibrate'),
                'wobble' => e('Wobble'),
                'none' => e('None'),
        ]).';
        </script>', 'custom')->toHeader();
        \Helpers\CDN::load('simpleeditor');
        
        View::push(assets('frontend/libs/fontawesome-picker/dist/css/fontawesome-iconpicker.min.css'))->toHeader();
        View::push(assets('frontend/libs/fontawesome-picker/dist/js/fontawesome-iconpicker.min.js'), 'script')->toFooter();
        View::push(assets('animate.min.css'))->toHeader();
        View::push(assets('frontend/libs/font-selector/jquery.fontselect.min.css'))->toHeader();
        View::push(assets('frontend/libs/font-selector/jquery.fontselect.min.js'), 'script')->toFooter();

        View::push("<script>                        
                        var texteditor = CKEDITOR.replace('editor');
                        $('input[name=icon]').iconpicker();                        
                    </script>", "custom")->toFooter();

        View::push(assets('bio.min.js'), 'script')->toFooter();

        View::push('<script> var biodata = '.json_encode($bio->data->links).'; bioupdate();</script>', 'custom')->toFooter();
        View::push('<script>$(document).ready(function() { changeTheme("'.$bio->data->style->bg.'","'.($bio->data->style->gradient->start ?? '').'","'.($bio->data->style->gradient->stop ?? '').'","'.$bio->data->style->buttoncolor.'","'.$bio->data->style->buttontextcolor.'","'.$bio->data->style->textcolor.'") } ); </script>', 'custom')->toFooter();
        

        View::push('<style>            
            #preview .card{ 
                background: '.$bio->data->style->bg.';
                background:linear-gradient(135deg, '.$bio->data->style->gradient->start.' 0%, '.$bio->data->style->gradient->stop.' 100%);
                color: '.$bio->data->style->textcolor.';
                '.(isset($bio->data->bgimage) && $bio->data->bgimage ? 'background-image: url('.uploads($bio->data->bgimage, 'profile').') !important;background-size:cover !important':'').'
            }
            #preview .card h3{
                color: '.$bio->data->style->textcolor.';
            }
            #preview .card .btn-custom{
                background: '.$bio->data->style->buttoncolor.';
                color: '.$bio->data->style->buttontextcolor.';
            }
            #preview .card .btn-custom:hover{
                opacity: 0.8;
            }
        </style>', 'custom')->toHeader();        

        return View::with('bio.edit', compact('bio', 'domains', 'url'))->extend('layouts.dashboard');

    }  
    /**
     * Update BioPage
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.3.2
     * @param \Core\Request $request
     * @param integer $id
     * @return void
     */
    public function update(Request $request, int $id){
        
        \Gem::addMiddleware('DemoProtect');

        if(Auth::user()->teamPermission('bio.edit') == false){
			return Response::factory(['error' => true, 'message' => e('You do not have this permission. Please contact your team administrator.'), 'token' => csrf_token()])->json();
		}

        if(!$profile = DB::profiles()->where('id', $id)->where('userid', Auth::user()->rID())->first()){
            return Response::factory(['error' => true, 'message' => e('Profile does not exist.')])->json();
        }
        
        $user = Auth::user();

        if(!$request->name) return Response::factory(['error' => true, 'message' => e('Please enter a name for your profile.'), 'token' => csrf_token()])->json();
    
        $data = json_decode($profile->data, true);

        if(!$request->data){
            return Response::factory(['error' => true, 'message' => e('Please add at least one link.'), 'token' => csrf_token()])->json();
        }

        $url = DB::url()->first($profile->urlid);

        if($request->custom && $request->custom != $profile->alias){		
            if(strlen($request->custom) < 3){
                return Response::factory(['error' => true, 'message' =>e('Custom alias must be at least 3 characters.'), 'token' => csrf_token()])->json();
                
            }elseif($this->wordBlacklisted($request->custom)){
                return Response::factory(['error' => true, 'message' =>e('Inappropriate aliases are not allowed.'), 'token' => csrf_token()])->json();

            }elseif(DB::url()->where('custom', Helper::slug($request->custom))->whereRaw('(domain = ? OR domain = ?)', [$url->domain, ''])->first()){
                return Response::factory(['error' => true, 'message' =>e('That alias is taken. Please choose another one.'), 'token' => csrf_token()])->json();

            }elseif(DB::url()->where('alias', Helper::slug($request->custom))->whereRaw('(domain = ? OR domain = ?)', [$url->domain, ''])->first()){
                return Response::factory(['error' => true, 'message' =>e('That alias is taken. Please choose another one.'), 'token' => csrf_token()])->json();

            }elseif($this->aliasReserved($request->custom)){
                return Response::factory(['error' => true, 'message' =>e('That alias is reserved. Please choose another one.'), 'token' => csrf_token()])->json();

            }elseif(!$user->pro() && $this->aliasPremium($request->custom)){
                return Response::factory(['error' => true, 'message' =>e('That is a premium alias and is reserved to only pro members.'), 'token' => csrf_token()])->json();
            }                     

            $profile->alias = Helper::slug($request->custom);

            $url->alias = $profile->alias;
        }

        $url->pass = clean($request->pass);

        if($request->pixels){            
            $url->pixels = $request->pixels && $user && $user->has('pixels') ? clean(implode(",", $request->pixels)) : null;
        }

        $url->meta_title = clean($request->title);
        $url->meta_description = clean($request->description);

        $url->save();
        
        $data['avatarenabled'] = $request->avatarenabled;

        if($image = $request->file('avatar')){
            
            if(!$image->mimematch || !in_array($image->ext, ['jpg', 'png', 'jpeg']) || $image->sizekb > 500) return Response::factory(['error' => true, 'message' => e('Avatar must be either a PNG or a JPEG (Max 500kb).'), 'token' => csrf_token()])->json();

            $filename = "profile_avatar".Helper::rand(6).$image->name;

            $request->move($image, appConfig('app.storage')['profile']['path'], $filename);

            if(isset($data['avatar']) && $data['avatar']){
                unlink(appConfig('app.storage')['profile']['path']."/".$data['avatar']);
            }

            $data['avatar']= $filename;
        }  


        if($image = $request->file('bgimage')){
            
            if(!$image->mimematch || !in_array($image->ext, ['jpg', 'png', 'jpeg']) || $image->sizekb > 1000) return Response::factory(['error' => true, 'message' => e('Background must be either a PNG or a JPEG (Max 1mb).'), 'token' => csrf_token()])->json();

            $filename = "profile_imagebg".Helper::rand(6).$image->name;

			$request->move($image, appConfig('app.storage')['profile']['path'], $filename);

            if(isset($data['bgimage']) && $data['bgimage']){
                unlink(appConfig('app.storage')['profile']['path']."/".$data['bgimage']);
            }

            $data['bgimage'] = $filename;
        }
        
        $links = [];

        $old = $data;

        foreach($data['links'] as $id => $olddata){
            if($olddata['type'] != 'link') continue;
            $links[$olddata['link']] = $olddata['urlid'];

        }

        $data['links'] = [];
        foreach($request->data as $key => $value){
            if($value['type'] == 'link'){
                if(isset($links[$value['link']])){
                    $value['urlid'] = $links[$value['link']];
                } else {

                    if(!$this->validate(clean($value['link'])) || !$this->safe($value['link']) || $this->phish($value['link']) || $this->virus($value['link'])) continue;                    

                    $newlink = DB::url()->create();
                    $newlink->url = clean($value['link']);
                    $newlink->userid = $user->rID();
                    $newlink->alias = null;
                    $newlink->custom = null;
                    $newlink->save();
                    $value['urlid'] = $newlink->id;
                }
            }

            if($value['type'] == 'image'){
                            
                if($image = $request->file($key)){

                    if(!$image->mimematch || !in_array($image->ext, ['jpg', 'png', 'jpeg']) || $image->sizekb > 500) return Response::factory(['error' => true, 'message' => e('Image must be either a PNG or a JPEG (Max 500kb).'), 'token' => csrf_token()])->json();

                    $filename = "profile_imagetype".Helper::rand(6).$image->name;
        
                    $request->move($image, appConfig('app.storage')['profile']['path'], $filename);
        
                    $value['image'] = $filename;
                } else {
                    $value['image'] = $old['links'][$key]['image'];
                }                
            }
            
            if($value['type'] == 'product'){
                if($image = $request->file($key)){
                    if(!$image->mimematch || !in_array($image->ext, ['jpg', 'png', 'jpeg']) || $image->sizekb > 500) return Response::factory(['error' => true, 'message' => e('Image must be either a PNG or a JPEG (Max 500kb).'), 'token' => csrf_token()])->json();

                    $filename = "profile_producttype".Helper::rand(6).$image->name;
        
                    $request->move($image, appConfig('app.storage')['profile']['path'], $filename);
        
                    $value['image'] = $filename;
                } else {
                    $value['image'] = $old['links'][$key]['image'];
                }
            }


            $data['links'][$key] = in_array($value['type'], ['html', 'text']) ? array_map(function($value){
                return Helper::clean($value, 3, false, '<strong><i><a><b><u><img><iframe><ul><ol><li><p>');
            }, $value) :  array_map('clean', $value);
        }

        foreach($request->social as $key => $value){
            $data['social'][$key] = clean($value);
        }

        $data['style']['bg'] = clean($request->bg);
        $data['style']['font'] = clean($request->fonts);
        $data['style']['gradient'] = array_map('clean', $request->gradient);

        $data['style']['buttoncolor'] = clean($request->buttoncolor);
        $data['style']['buttontextcolor'] = clean($request->buttontextcolor);
        $data['style']['textcolor'] = clean($request->textcolor);        
        
        $data['style']['custom'] = Helper::clean($request->customcss, 3);

        $profile->userid = $user->rID();
        $profile->name = clean($request->name);
        $profile->data = json_encode($data);
        $profile->save();

        return Response::factory(['error' => false, 'message' => e('Profile has been successfully updated.'), 'token' => csrf_token()])->json();
    }
    /**
     * Set bio as default
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param integer $id
     * @return void
     */
    public function default(int $id){

        if(Auth::user()->teamPermission('bio.edit') == false){
			return Helper::redirect()->to(route('bio'))->with('danger', e('You do not have this permission. Please contact your team administrator.'));
		}
        
        $user = Auth::user();

        if(!$profile = DB::profiles()->where('id', $id)->where('userid', $user->rID())->first()){
            return Helper::redirect()->back()->with('danger', e('Profile does not exist.'));
        }

        $user->defaultbio = $profile->id;
        $user->save();

        if($user->public){
            return Helper::redirect()->back()->with('success', e('Profile has been set as default and can now be access via your profile page.'));
        } else {
            return Helper::redirect()->back()->with('info', e('Profile has been set as default and can now be access via your profile page. Your profile setting is currently set on private.'));
        }        
    }
    /**
     * Import Old Bio
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public function importBio(){

        if(Auth::user()->teamPermission('bio.create') == false){
			return Helper::redirect()->to(route('bio'))->with('danger', e('You do not have this permission. Please contact your team administrator.'));
		}

        \Gem::addMiddleware('DemoProtect');

        $user = Auth::user();

        $old = json_decode($user->profiledata);

        $data = [];

        foreach($old->links as $link){
            if(!isset($link->link) || empty($link->link)) continue;
            if(!$url = DB::url()->where('userid', $user->id)->where('url', $link->link)->first()){
                $url = DB::url()->create();
                $url->url = $link->link;
                $url->custom = 'P'.Helper::rand(3).'M'.Helper::rand(3);
                $url->type = 'direct';
                $url->userid = $user->id;
                $url->date = Helper::dtime();
                $url->save();
            }
    
            $data['links'][Helper::slug($link->link)] = ['text' => $link->text, 'link' => $link->link, 'urlid' => $url->id, 'type' => 'link'];
        }

        $data["social"] = ["facebook" => "","twitter" => "","instagram" => "","tiktok" => "","linkedin" => ""];

        $data["style"] = ["bg" => "#FDBB2D","gradient" => ["start" => "#0072ff","stop" => "#00c6ff"],"buttoncolor" => "#ffffff","buttontextcolor" => "#00c6ff","textcolor" => "#ffffff"];

        $profile = DB::profiles()->create();

        $alias = $this->alias();

        $url = DB::url()->create();
        $url->userid = $user->rID();
        $url->url = '';
        $url->domain = clean($request->domain);
        $url->alias = $alias;
        $url->date = Helper::dtime();
        $url->save();

        $profile = DB::profiles()->create();        
        $profile->userid = $user->rID();
        $profile->alias = $alias;
        $profile->urlid = $url ? $url->id : null;
        $profile->name = clean($old->name);
        $profile->data = json_encode($data);
        $profile->status = 1;
        $profile->created_at = Helper::dtime();
        $profile->save();
        $url->profileid = $profile->id;
        $url->save();

        $user->defaultbio = $profile->id;
        $user->profiledata = null;
        $user->save();
        
        return Helper::redirect()->back()->with('success', 'Migration complete.');
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
        if(Auth::user()->teamPermission('bio.edit') == false){
			return Helper::redirect()->to(route('bio'))->with('danger', e('You do not have this permission. Please contact your team administrator.'));
		}
        
        $user = Auth::user();

        $count = DB::profiles()->where('userid', Auth::user()->rID())->count();

        $total = Auth::user()->hasLimit('bio');

        \Models\Plans::checkLimit($count, $total);
        
        if(!$profile = DB::profiles()->where('id', $id)->where('userid', $user->rID())->first()){
            return Helper::redirect()->back()->with('danger', e('Profile does not exist.'));
        }

        $url = DB::url()->first($profile->urlid);

        $new = DB::profiles()->create();

        $new->name = $profile->name.' ('.e('Copy').')';
        $new->alias = $this->alias();
        $new->userid = $user->rID();

        $newurl = DB::url()->create();
        $newurl->userid = $user->rID();
        $newurl->url = '';
        $newurl->domain = $url->domain;
        $newurl->alias = $new->alias;
        $newurl->date = Helper::dtime();
        $newurl->save();

        $new->urlid = $newurl->id;
        $new->data = $profile->data;
        $new->created_at = Helper::dtime();

        $new->save();

        $newurl->profileid = $new->id;
        $newurl->save();

        return Helper::redirect()->back()->with('success', e('Item has been successfully duplicated.'));
    }
}