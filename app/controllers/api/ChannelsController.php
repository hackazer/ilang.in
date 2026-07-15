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

namespace API;

use Core\Helper;
use Core\Request;
use Core\Response;
use Core\DB;
use Core\Auth;
use Models\User;

class Channels {

    /**
     * Check Permission
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.5
     */
    public function __construct(){

        if(!Auth::ApiUser()->has('channels')){
            die(Response::factory(['error' => 1, 'message' => 'You do not have permission to access this endpoint.'], 403)->json());
        }        
    }
    /**
     * List Campaigns
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.5
     * @param \Core\Request $request
     * @return void
     */
    public function get(Request $request){

        $channels = [];

        $user = Auth::ApiUser();

        $query = DB::channels()->where('userid', $user->id);

        $page = (int) currentpage();

        $limit = 15;

        if( $request->limit && \is_numeric($request->limit) ){                    
            $limit = (int) $request->limit;
        } 

        $total = $query->count();

        $results = $query->limit($limit)->offset(($page-1)*$limit)->findMany();
        
        if(($total % $limit)<>0) {
            $max = floor($total/$limit)+1;
        } else {
            $max = floor($total/$limit);
        }  
    
        foreach($results as $channel){

            $channels[] = [
                "id" => (int) $channel->id,
                "name" => $channel->name,
                "description" => $channel->description,
                "color" => $channel->color,
                "starred" => $channel->starred == '1'
            ];
        }

        return Response::factory(['error' => 0, 'data' => ['result' => $total, 'perpage' => $limit, 'currentpage' => $page, 'nextpage' => $max == 0 || $page == $max ? null : $page+1, 'maxpage' => $max, 'channels' => $channels]])->json();

    }
    /**
     * Single Channel 
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.5
     * @param \Core\Request $request
     * @param integer $id
     * @return void
     */
    public function single(Request $request, int $id){

        $user = Auth::ApiUser();

        if(!$channel = DB::channels()->where('userid', $user->rID())->where('id', $id)->first()){
            return Response::factory(['error' => 1, 'message' => 'Channel not found. Please try again.'])->json();
        }

        $items = [];


        $query = DB::tochannels()->where('userid', $user->rID())->where('channelid', $channel->id);

        $page = (int) currentpage();

        $limit = 15;

        if($request->limit && \is_numeric($request->limit) ){                    
            $limit = (int) $request->limit;
        } 

        $total = $query->count();

        $results = $query->limit($limit)->offset(($page-1)*$limit)->findMany();
        
        if(($total % $limit)<>0) {
            $max = floor($total/$limit)+1;
        } else {
            $max = floor($total/$limit);
        }  
        
        foreach($results as $item){
            if($item->type == 'bio'){
                
                if(!$data = DB::profiles()->where('userid', $user->rID())->where('id', $item->itemid)->first()) continue;

                $url = DB::url()->first($data->urlid);

                $items[] = [
                    'type' => 'bio',
                    'id' => (int) $data->id,
                    'title' => Helper::truncate($data->name, 30),
                    'preview' => \Helpers\App::shortRoute($url->domain, $data->alias),
                    'link' => \Helpers\App::shortRoute($url->domain, $data->alias),
                    'date' => Helper::dtime($data->created_at, 'Y-m-d'),
                ];

            } elseif($item->type == 'qr'){
                if(!$data = DB::qrs()->where('userid', $user->rID())->where('id', $item->itemid)->first()) continue;
                
                $url = null;

                if($data->urlid){
                    $url = DB::url()->first($data->urlid);
                }                

                $items[] = [
                    'type' => 'qr',
                    'id' => (int) $data->id,
                    'title' => Helper::truncate($data->name, 30),
                    'preview' => route('qr.generate', $data->alias),
                    'link' =>  route('qr.generate', $data->alias),
                    'date' => Helper::dtime($data->created_at, 'Y-m-d'),
                ];

            } else {
                if(!$data = DB::url()->where('userid', $user->rID())->where('id', $item->itemid)->first()) continue;

                $items[] = [
                    'type' => 'links',
                    'id' => (int) $data->id,
                    'title' => $data->meta_title ? Helper::truncate($data->meta_title, 30) :  Helper::truncate($data->url, 30),
                    'preview' => $data->url,
                    'link' => \Helpers\App::shortRoute($data->domain, $data->custom.$data->alias),
                    'date' => Helper::dtime($data->date, 'Y-m-d'),
                ];
            }
        }

        return Response::factory([
                                    'error' => 0, 
                                    'data' => [
                                        'result' => $total, 
                                        'perpage' => $limit, 
                                        'currentpage' => $page, 
                                        'nextpage' => $max == 0 || $page == $max ? null : $page+1, 
                                        'maxpage' => $max, 
                                        'items' => $items
                                    ]
                                ])->json();
    }
    /**
     * Create a channel
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.5
     * @param \Core\Request $request
     * @return void
     */
    public function create(Request $body){

        $user = Auth::ApiUser();

        $count = DB::channels()->where('userid', $user->rID())->count();

        $total = $user->hasLimit('channels');

        if($total != 0 && $count > $total){
            return Response::factory(['error' => 1, 'message' => 'You have reached your limit.'])->json();
        }  
        
        $request = $body->getJSON();

        if(!isset($request->name) || strlen(clean($request->name)) < 2){
            return Response::factory(['error' => 1, 'message' => 'Channel name cannot be empty and must have at least 2 characters.'])->json();
        }
        
        if(DB::channels()->where('userid', $user->rID())->where('name', clean($request->name))->first()){
            return Response::factory(['error' => 1, 'message' => 'You already have a channel with this name.'])->json();
        }

        $channel = DB::channels()->create();

        $channel->name   = clean($request->name);
        $channel->description  = isset($request->description) ? clean($request->description) : '';
        $channel->color   = isset($request->color) ? Helper::truncate(clean($request->color), 7, '') : '#' . substr(md5(mt_rand()), 0, 6);
        $channel->starred = isset($request->starred)  && $request->starred ? 1 :  0;
        $channel->userid = $user->rID();
        $channel->created_at   = Helper::dtime();
		$channel->save();

        return Response::factory([
                                    'error' => 0, 
                                    "id" => (int) $channel->id,
                                    "name" => $channel->name,
                                    "description" => $channel->description,
                                    "color" => $channel->color,
                                    "starred" => $channel->starred == '1'
                                ])->json();
    }
    /**
     * Update a channel
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.5
     * @param \Core\Request $body
     * @param integer $id
     * @return void
     */
    public function update(Request $body, int $id){
        
        $user = Auth::ApiUser();

        $request = $body->getJSON();

        if(!$channel = DB::channels()->where('userid', $user->rID())->where('id', $id)->first()){
            return Response::factory(['error' => 1, 'message' => 'Channel not found. Please try again.'])->json();
        }             

        if(isset($request->name) && strlen($request->name) > 2){
            $channel->name  = ucfirst(clean($request->name));
        }

        if(isset($request->description) && strlen($request->description) > 2){
            $channel->description  = clean($request->description);
        }

        if(isset($request->color)){
            $channel->color = Helper::truncate(clean($request->color), 7, '');
        }

        if(isset($request->starred)){
            $channel->starred = $request->starred ? '1' : '0';
        }

		$channel->save();

        return Response::factory([
                                    'error' => 0, 
                                    "id" => (int) $channel->id,
                                    "name" => $channel->name,
                                    "description" => $channel->description,
                                    "color" => $channel->color,
                                    "starred" => $channel->starred == '1'
                                ])->json();
    }
    /**
     * Delete a channel
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.5
     * @param integer $id
     * @return void
     */
    public function delete(int $id){

        $user = Auth::ApiUser();

        if(!$channel = DB::channels()->where('id', $id)->where('userid', $user->rID())->first()){
            return Response::factory(['error' => 1, 'message' => 'Channel not found. Please try again.'])->json();
        }

        DB::tochannels()->where("channelid", $channel->id)->where('userid', $user->rID())->deleteMany();

        $channel->delete();

        return Response::factory(['error' => 0, 'message' => 'Channel has been deleted successfully.'])->json(); 
    }
    /**
     * Assign a link to a channel
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.5
     * @param integer $channelid
     * @param integer $itemid
     * @return void
     */
    public function assign(int $channelid, string $type, int $itemid){
        
        $user = Auth::ApiUser();

        if(!$channel = DB::channels()->where('userid', $user->rID())->where('id', $channelid)->first()){
            return Response::factory(['error' => 1, 'message' => 'Channel not found. Please try again.'])->json();
        } 

        $type = in_array($type, ['links', 'bio', 'qr']) ? $type : 'links';

		if(!$itemid) return Response::factory(['error' => 1, 'message' => 'You need to select at least 1 link.'])->json();

        if(DB::tochannels()->where('type', $type)->where('userid', $user->rID())->where('channelid', $channelid)->where('itemid', $itemid)->first()) {
            return Response::factory(['error' => 1, 'message' => 'This item is already added to the selected channel.'])->json();
        }

        if($type == 'bio'){
            if(!$url = DB::profiles()->where('userid', $user->rID())->where('id', $itemid)->first()) {
                return Response::factory(['error' => 1, 'message' => 'Bio Page does not exist. Please try again.'])->json();
            }
        } elseif($type == 'qr'){
            if(!$url = DB::qrs()->where('userid', $user->rID())->where('id', $itemid)->first()) {
                return Response::factory(['error' => 1, 'message' => 'QR Code does not exist. Please try again.'])->json();
            }
        } else {
            if(!$url = DB::url()->where('userid', $user->rID())->where('id', $itemid)->first()) {
                return Response::factory(['error' => 1, 'message' => 'Link does not exist. Please try again.'])->json();
            }
        }

        $tochannel = DB::tochannels()->create();

        $tochannel->userid = $user->rID();
        $tochannel->channelid = $channel->id;
        $tochannel->itemid = $itemid;
        $tochannel->type = $type;
        $tochannel->save();				
	
        return Response::factory(['error' => 0, 'message' => 'Link successfully added to the channel.'])->json(); 
    }
}