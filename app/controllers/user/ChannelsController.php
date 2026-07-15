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

use Core\Helper;
use Core\View;
use Core\DB;
use Core\Auth;
use Core\Request;
use Core\Email;
use Core\Response;
use Models\User;

class Channels {     

    /**
     * Verify Permission
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     */
    public function __construct(){

        if(User::where('id', Auth::user()->rID())->first()->has('channels') === false){
			return \Models\Plans::notAllowed();
		}
    }
    /**
     * Channels
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.4
     * @return void
     */
    public function index(){

        View::set('title', e('Channels'));

        \Helpers\CDN::load('spectrum');

        $starred = [];
        foreach(DB::channels()->where('userid', user()->rID())->where('starred', 1)->orderByAsc('name')->findMany() as $item){
            $item->count = DB::tochannels()->where('channelid', $item->id)->where('userid', Auth::user()->rID())->count();
            $starred[] = $item;
        }

        $channels = []; 
        foreach(DB::channels()->where('userid', user()->rID())->orderByAsc('name')->paginate(12) as $item){
            $item->count = DB::tochannels()->where('channelid', $item->id)->where('userid', Auth::user()->rID())->count();
            $channels[] = $item;
        }
        
        return View::with('user.channels', compact('starred', 'channels'))->extend('layouts.dashboard');
    }
    /**
     * Single Channel
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.4
     * @param \Core\Request $request
     * @param integer $id
     * @return void
     */
    public function channel(Request $request, int $id){

        $user = Auth::user();

        if(!$channel = DB::channels()->where('userid', $user->rID())->where('id', $id)->first()){
            stop(404);
        }

        $items = [];
        
        foreach(DB::tochannels()->where('userid', $user->rID())->where('channelid', $channel->id)->orderByDesc('id')->paginate(15) as $item){
            if($item->type == 'bio'){
                
                if(!$data = DB::profiles()->where('userid', $user->rID())->where('id', $item->itemid)->first()) continue;

                $url = DB::url()->first($data->urlid);

                $items[] = [
                    'type' => 'bio',
                    'id' => $data->id,
                    'title' => Helper::truncate($data->name, 30),
                    'preview' => \Helpers\App::shortRoute($url->domain, $data->alias),
                    'link' => \Helpers\App::shortRoute($url->domain, $data->alias),
                    'urlid' => $data->urlid,
                    'date' => Helper::dtime($data->created_at, 'Y-m-d'),
                    'item' => $item->id
                ];

            } elseif($item->type == 'qr'){
                if(!$data = DB::qrs()->where('userid', $user->rID())->where('id', $item->itemid)->first()) continue;
                
                $url = null;

                if($data->urlid){
                    $url = DB::url()->first($data->urlid);
                }                

                $items[] = [
                    'type' => 'qr',
                    'id' => $data->id,
                    'title' => Helper::truncate($data->name, 30),
                    'preview' => route('qr.generate', $data->alias),
                    'link' =>  route('qr.generate', $data->alias),
                    'urlid' => $data->urlid,
                    'date' => Helper::dtime($data->created_at, 'Y-m-d'),
                    'item' => $item->id
                ];

            } else {
                if(!$data = DB::url()->where('userid', $user->rID())->where('id', $item->itemid)->first()) continue;

                $items[] = [
                    'type' => 'links',
                    'id' => $data->id,
                    'title' => $data->meta_title ? Helper::truncate($data->meta_title, 30) :  Helper::truncate($data->url, 30),
                    'preview' => $data->url,
                    'urlid' => $data->id,
                    'link' => \Helpers\App::shortRoute($data->domain, $data->custom.$data->alias),
                    'date' => Helper::dtime($data->date, 'Y-m-d'),
                    'item' => $item->id
                ];
            }
        }

        View::push(assets('frontend/libs/clipboard/dist/clipboard.min.js'), 'js')->toFooter();

        \Helpers\CDN::load('spectrum');

        View::set('title', $channel->name.' '.e('Channel'));
        
        return View::with('user.channel', compact('channel', 'items'))->extend('layouts.dashboard');
    }
    /**
     * Create a channel
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.4
     * @param \Core\Request $request
     * @return void
     */
    public function save(Request $request){

        $user = Auth::user();
        
        if($user->teamPermission("bundle.create") === false){
            return Helper::redirect()->back()->with("danger", e("You do not have this permission. Please contact your team administrator."));
        }

        $count = DB::channels()->where('userid', Auth::user()->rID())->count();

        $total = Auth::user()->hasLimit('channels');

        \Models\Plans::checkLimit($count, $total);      

        if(!$request->name || strlen(clean($request->name)) < 2){
            return back()->with("danger", e("Channel name cannot be empty and must have at least 2 characters."));
        }
        
        $channel = DB::channels()->create();

        $channel->name   = clean($request->name);
        $channel->description   = clean($request->description);
        $channel->color   = Helper::truncate(clean($request->color), 7, '');
        $channel->starred = clean($request->starred) == '1' ? 1 : 0;
        $channel->userid = $user->rID();
        $channel->created_at   = Helper::dtime();
		$channel->save();

        return back()->with("success", e("Channel was successfully created. You may start adding links to it now."));
    }
    /**
     * Update channel
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.4
     * @param \Core\Request $request
     * @param integer $id
     * @return void
     */
    public function update(Request $request, int $id){
        $user = Auth::user();
        
        if($user->teamPermission("bundle.edit") === false){
            return Helper::redirect()->back()->with("danger", e("You do not have this permission. Please contact your team administrator."));
        }

        if(!$channel = DB::channels()->where('id', $id)->where('userid', $user->rID())->first()){
            return Helper::redirect()->back()->with("danger", e("Channel does not exist"));
        }


        if(!$request->newname || strlen(clean($request->newname)) < 2){
            return back()->with("danger", e("Channel name cannot be empty and must have at least 2 characters."));
        }    

        $channel->name   = clean($request->newname);
        $channel->description   = clean($request->newdescription);
        $channel->color   = Helper::truncate(clean($request->newcolor), 7, '');
        $channel->starred = clean($request->newstarred) == '1' ? 1 : 0;
		$channel->save();
        
        return back()->with("success", e("Channel was updated successfully."));
    }
    /**
     * Delete a channel
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.4
     * @param \Core\Request $request
     * @param integer $id
     * @return void
     */
    public function delete(Request $request, int $id, string $nonce){

        if(Auth::user()->teamPermission('bundle.delete') == false){
			return back()->with('danger', e('You do not have this permission. Please contact your team administrator.'));
		}

        \Gem::addMiddleware('DemoProtect');

        if(!Helper::validateNonce($nonce, 'channel.delete')){
            return Helper::redirect()->back()->with('danger', e('An unexpected error occurred. Please try again.'));
        }

        $user = Auth::user();

        if(!$channel = DB::channels()->where('id', $id)->where('userid', $user->rID())->first()){
            return Helper::redirect()->back()->with("danger", e("Channel does not exist"));
        }

        DB::tochannels()->where("channelid", $channel->id)->where('userid', $user->rID())->deleteMany();

        $channel->delete();

        return back()->with('success', e('Channel has been successfully deleted.'));
    }

    /**
	 * Add to channel
	 *
	 * @author GemPixel <https://gempixel.com> 
	 * @version 6.4
	 * @param Core\Request $request
	 * @param string $type
	 * @return void
	 */
	public function addto(Request $request, $type = 'links', $id = null){

		if(!$request->channels) return Response::factory(['error' => true, 'message' => e('You need to select at least 1 channel.'), 'token' => csrf_token()])->json();			
		
		$user =  Auth::user();

		$type = in_array($type, ['links', 'bio', 'qr']) ? $type : 'links';

		$ids = json_decode(html_entity_decode($request->channelids));

		if(!$ids){
			return Response::factory(['error' => true, 'message' => e('You need to select at least 1 link.'), 'token' => csrf_token()])->json();
		}

		foreach($ids as $id){

			foreach($request->channels as $channelid){
				
				if(!$channel = DB::channels()->where('id', $channelid)->where('userid', $user->rID())->first()) continue;

				if(DB::tochannels()->where('type', $type)->where('userid', $user->rID())->where('channelid', $channelid)->where('itemid', $id)->first()) continue;

				if($type == 'bio'){
					if(!$url = DB::profiles()->where('userid', $user->rID())->where('id', $id)->first()) continue;
				} elseif($type == 'qr'){
					if(!$url = DB::qrs()->where('userid', $user->rID())->where('id', $id)->first()) continue;
				} else {
					if(!$url = DB::url()->where('userid', $user->rID())->where('id', $id)->first()) continue;
				}

				$tochannel = DB::tochannels()->create();

				$tochannel->userid = $user->rID();
				$tochannel->channelid = $channel->id;
				$tochannel->itemid = $id;
				$tochannel->type = $type;
				$tochannel->save();				
			}

		}

		return Response::factory(['error' => false, 'message' => e('Selected items have been added to the {c} channel.', null, ['c' => $channel->name]), 'token' => csrf_token(), 'html' => '<script>refreshlinks()</script>'])->json();
	}
    /**
     * Remove item from channel
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.4
     * @param integer $channel
     * @param integer $id
     * @return void
     */
    public function removefrom(int $channel, string $type, int $id){
        if(Auth::user()->teamPermission('bundle.edit') == false){
			return back()->with('danger', e('You do not have this permission. Please contact your team administrator.'));
		}

        \Gem::addMiddleware('DemoProtect');

        $type = in_array($type, ['links', 'bio', 'qr']) ? $type : 'links';

        $user = Auth::user();

        if(!$channel = DB::channels()->where('id', $channel)->where('userid', $user->rID())->first()){
            return Helper::redirect()->back()->with("danger", e("Channel does not exist"));
        }

        DB::tochannels()->where("itemid", $id)->where('type', $type)->where('channelid', $channel->id)->where('userid', $user->rID())->deleteMany();

        return back()->with('success', e('Item has been removed from the channel.'));        
    }
}