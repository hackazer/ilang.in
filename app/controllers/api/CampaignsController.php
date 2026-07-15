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

class Campaigns {

    /**
     * Check Permission
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.5
     */
    public function __construct(){

        if(!Auth::ApiUser()->has('bundle')){
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

        $campaigns = [];

        $user = Auth::ApiUser();

        $query = DB::bundle()->where('userid', $user->id);

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
    
        foreach($results as $campaign){

            $campaigns[] = [
                "id" => (int) $campaign->id,
                "name" => $campaign->name,
                "public" => $campaign->access == 'public',
                "rotator" => $campaign->slug ? route('campaign', [$campaign->slug]) : false,
                "list" => $campaign->slug ? route('campaign.list', [$user->username, $campaign->slug.'-'.$campaign->id]) : false,
                "views" => (int) $campaign->view
            ];
        }

        return Response::factory(['error' => 0, 'data' => ['result' => $total, 'perpage' => $limit, 'currentpage' => $page, 'nextpage' => $max == 0 || $page == $max ? null : $page+1, 'maxpage' => $max, 'campaigns' => $campaigns]])->json();

    }
    /**
     * Create a campaign
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.5
     * @param \Core\Request $request
     * @return void
     */
    public function create(Request $body){

        $user = Auth::ApiUser();
        
        $request = $body->getJSON();

        if(!isset($request->name) || strlen(clean($request->name)) < 2){
            return Response::factory(['error' => 1, 'message' => e("Campaign name cannot be empty and must have at least 2 characters.")])->json();
        }
        
        if(DB::bundle()->where('name', clean($request->name))->where('userid', $user->rID())->first()){
            return Response::factory(['error' => 1, 'message' => e("You already have a campaign with that name.")])->json();
        }

        $slug = Helper::rand(6);

        if(isset($request->slug)){
            $slug = Helper::slug($request->slug);
            if(DB::bundle()->where("slug", $slug)->first()){
                return Response::factory(['error' => 1, 'message' => e("This slug is currently not available. Please choose another one.")])->json();
            }
        }

        $campaign = DB::bundle()->create();

        $campaign->name   = ucfirst(clean($request->name));
        $campaign->slug   = $slug;
        $campaign->access = isset($request->public) && $request->public ? 'public' : "private";
        $campaign->userid = $user->rID();
        $campaign->date   = Helper::dtime();
		$campaign->save();

        return Response::factory([
                                    'error' => 0, 
                                    'id' => (int) $campaign->id, 
                                    'campaign' => $campaign->name, 
                                    "public" => $campaign->access == 'public',
                                    "rotator" => $campaign->slug ? route('campaign', [$campaign->slug]) : false,
                                    "list" => $campaign->slug ? route('campaign.list', [$user->username, $campaign->slug.'-'.$campaign->id]) : false,
                                ])->json();
    }
    /**
     * Update a campaign
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

        if(!$campaign = DB::bundle()->where('userid', $user->rID())->where('id', $id)->first()){
            return Response::factory(['error' => 1, 'message' => 'Campaign not found. Please try again.'])->json();
        }             

        if(isset($request->name) && strlen($request->name) > 2){
            $campaign->name  = ucfirst(clean($request->name));
        }

        if(isset($request->slug) && !empty($request->slug)){
            $slug = Helper::slug($request->slug);
            if(DB::bundle()->where("slug", $slug)->first()){
                return Response::factory(['error' => 1, 'message' => e("This slug is currently not available. Please choose another one.")])->json();
            }
            $campaign->slug = $slug;
        }

        if(isset($request->public)){
            $campaign->access = $request->public ? 'public' : 'private';

        }

		$campaign->save();

        return Response::factory([
                                    'error' => 0, 
                                    'id' => (int) $campaign->id, 
                                    'campaign' => $campaign->name, 
                                    "public" => $campaign->access == 'public',
                                    "rotator" => $campaign->slug ? route('campaign', [$campaign->slug]) : false,
                                    "list" => $campaign->slug ? route('campaign.list', [$user->username, $campaign->slug.'-'.$campaign->id]) : false,
                                ])->json();
    }
    /**
     * Delete a campaign
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.5
     * @param integer $id
     * @return void
     */
    public function delete(int $id){

        $user = Auth::ApiUser();

        if(!$campaign = DB::bundle()->where('userid', $user->rID())->where('id', $id)->first()){
            return Response::factory(['error' => 1, 'message' => 'Campaign not found. Please try again.'])->json();
        }             

        $campaign->delete();

        return Response::factory(['error' => 0, 'message' => 'Campaign has been deleted successfully.'])->json(); 
    }
    /**
     * Assign a link to a campaign
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.5
     * @param integer $campaignid
     * @param integer $linkid
     * @return void
     */
    public function assign(int $campaignid, int $linkid){
        
        $user = Auth::ApiUser();

        if(!$campaign = DB::bundle()->where('userid', $user->rID())->where('id', $campaignid)->first()){
            return Response::factory(['error' => 1, 'message' => 'Campaign not found. Please try again.'])->json();
        } 

        if(!$url = DB::url()->where('userid', $user->rID())->where('id', $linkid)->first()){
            return Response::factory(['error' => 1, 'message' => 'Link not found. Please try again.'])->json();
        }

        $url->bundle = $campaign->id;

        $url->save();

        return Response::factory(['error' => 0, 'message' => 'Link successfully added to the campaign.'])->json(); 
    }
}