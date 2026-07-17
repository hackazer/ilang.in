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

use Core\View;
use Core\DB;
use Core\Auth;
use Core\Helper;
use Core\Request;
use Core\Response;
use Helpers\CDN;
use Models\Url;

class Dashboard {

    /**
     * User Dashboard
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public function index(Request $request){

        if($request->success && $request->success == 'true') return Helper::redirect()->to(route('dashboard'))->with("info", e("Your payment was successfully made. Thank you."));

        $urls = self::withBundleNames(
            Url::recent()->where("userid", Auth::user()->rID())->orderByDesc('date')->limit(10)->findMany()
        );
                    
        $count = new \stdClass;

        $count->links = DB::url()->where('userid', Auth::user()->rID())->count();
        $count->linksToday = DB::url()->where('userid', Auth::user()->rID())->whereRaw('`date` >= CURDATE()')->count();

        $clicks = DB::url()->selectExpr('SUM(click) as click')->where('userid', Auth::user()->rID())->first();
        $count->clicks = $clicks->click ? $clicks->click : 0;

        $count->clicksToday = DB::stats()->whereRaw('date >= CURDATE()')->where('urluserid', Auth::user()->rID())->count();

        $recentActivity = self::withActivityRelations(
            DB::stats()->where('urluserid', Auth::user()->rID())->limit(10)->orderByDesc('date')->find()
        );
                
        View::set('title', e('Dashboard'));

        CDN::load('datetimepicker');
        CDN::load('autocomplete');

        View::push(assets('frontend/libs/clipboard/dist/clipboard.min.js'), 'js')->toFooter();

        return View::with('user.index', compact('urls', 'count', 'recentActivity'))->extend('layouts.dashboard');
    }
    /**
     * User's Links
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.4
     * @return void
     */
    public function links(Request $request){

        $urls = [];

        $title = e('Links');

        $query = Url::recent()->where("userid", Auth::user()->rID());

        if($request->campaign && is_numeric($request->campaign)){
            if($campaign = DB::bundle()->where('id', clean($request->campaign))->where('userid', Auth::user()->rID())->first()){
                $query->where('bundle', $request->campaign);
                $title = e('Campaign Links'). ' - '.$campaign->name;
            }
        }

        if($request->sort == "most"){
            $query->orderByDesc('click');
        }
        
        if($request->sort == "less"){
            $query->orderByAsc('click');
        }

        if(!$request->sort || $request->sort == "latest"){
            $query->orderByDesc('date');
        }

        if($request->sort == "old"){
            $query->orderByAsc('date');
        }

        if($request->pixel){
            $query->whereLike('pixels', '%'.clean($request->pixel).'%');
        }

        if($request->date) self::applyDateCutoff($query, (string) $request->date);
        $limit = 15;

        if($request->perpage && is_numeric($request->perpage) && $request->perpage > 15 && $request->perpage <= 100){
            $limit = $request->perpage;
        }
        $results = $query->paginate($limit);

        if($request->page > 1 && !$results) stop(404);

        $urls = self::withBundleNames($results);

        View::set('title', $title);

        View::push(assets('frontend/libs/clipboard/dist/clipboard.min.js'), 'js')->toFooter();
        CDN::load('datetimepicker');
        
        return View::with('user.links', compact('urls', 'title'))->extend('layouts.dashboard');
    }
    /**
     * Archived Links
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public function archived(){
        $urls = self::withBundleNames(
            Url::archived()->where("userid", Auth::user()->rID())->orderByDesc('date')->paginate(15, true)
        );

        $title = e('Archived Links');

        View::set('title', $title);
        View::push(assets('frontend/libs/clipboard/dist/clipboard.min.js'), 'js')->toFooter();

        return View::with('user.links', compact('urls', 'title'))->extend('layouts.dashboard');        
    }
    /**
     * Expired Links
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public function expired(){
        $urls = self::withBundleNames(
            Url::expired()->where("userid", Auth::user()->rID())->orderByDesc('date')->paginate(15, true)
        );

            
        $title = e('Expired Links');

        View::set('title', $title);
        View::push(assets('frontend/libs/clipboard/dist/clipboard.min.js'), 'js')->toFooter();

        return View::with('user.links', compact('urls', 'title'))->extend('layouts.dashboard');   
    }

    /**
     * Generate Clicks Graphs
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public function statsClicks(){

        $response = ['label' => e('Clicks')];

        $timestamp = strtotime('now');
        for ($i = 14; $i >= 0; $i--) {
            $d = $i;
            $timestamp = \strtotime("-{$d} days");            
            $response['data'][date('d F', $timestamp)] = 0;
        }
            

        $statsStart = date('Y-m-d 00:00:00', strtotime('-14 days'));

        $results = DB::stats()
                    ->selectExpr('COUNT(DATE(date))', 'count')
                    ->selectExpr('DATE(date)', 'date')
                    ->whereGte('date', $statsStart)
                    ->where('urluserid',Auth::user()->rID())
                    ->orderByDesc('date')
                    ->groupByExpr('DATE(date)')
                    ->findArray();

        foreach($results as $data){
            $response['data'][Helper::dtime($data['date'], 'd F')] = (int) $data['count'];
        }   
        
        return (new Response($response))->json(); 
    }
    /**
     * Refresh Links
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public function refresh(){
        $urls = self::withBundleNames(
            Url::recent()->where("userid", Auth::user()->rID())->orderByDesc('date')->paginate(15, true)
        );

        foreach($urls as $url){
            view('partials.links', compact('url'));
        }
    }
    /**
     * Refresh Archive
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public function refreshArchive(){
        $urls = self::withBundleNames(
            DB::url()->where("userid", Auth::user()->rID())->where('archived', 1)->orderByDesc('date')->paginate(15, true)
        );

        foreach($urls as $url){
            view('partials.links', compact('url'));
        }
    }

    /**
     * Search 
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param Request $request
     * @return void
     */
    public function search(Request $request){

        $urls =  [];
        
        echo "<script>$('#search button[type=submit]').addClass('d-none'); $('#search button[type=button]').removeClass('d-none');</script>";

        if(strlen($request->q) >= 3) {

            $urls = self::withBundleNames(Url::whereAnyIs([
                ['url' => "%{$request->q}%"],
                ['custom' => "%{$request->q}%"],
                ['alias' => "%{$request->q}%"],
                ['meta_title' => "%{$request->q}%"],
            ], 'LIKE ')->where('userid', Auth::user()->rID())->limit(10)->findMany());

            foreach($urls as $url){
                view('partials.links', compact('url'));
            }
       
        } else {
            return Response::factory('<p class="alert alert-danger p-3">'.e('Keyword must be more than 3 characters!').'</p><br>')->send();
        }       
    }
    /**
     * Affiliate
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @return void
     */
    public function affiliate(){
        
        if(!config('affiliate')->enabled) {
            stop(404);
        }

        View::set('title', e('Affiliate Referrals'));

        $user = Auth::user();

        View::push(assets('frontend/libs/clipboard/dist/clipboard.min.js'), 'js')->toFooter();

        $sales = DB::affiliates()->where('refid', $user->id)->orderByDesc('referred_on')->find();

        return View::with('user.affiliate', compact('user', 'sales'))->extend('layouts.dashboard');
    }
    /**
     * Fetch Single Link
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.5
     * @param \Core\Request $request
     * @return void
     */
    public function fetch(Request $request){

        if(is_numeric($request->id) && $url = Url::where('id', $request->id)->where("userid", Auth::user()->rID())->first()){
            if($url->bundle && $bundle = DB::bundle()->where('id', $url->bundle)->first()){
                $url->bundlename = $bundle ? $bundle->name : 'na';
            }
            view('partials.links', compact('url'));
        }
    }

    private static function applyDateCutoff($query, ?string $value)
    {
        $value = trim((string) $value);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();

        if(
            !$date ||
            ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) ||
            $date->format('Y-m-d') !== $value
        ){
            return $query;
        }

        return $query->whereLt('date', $date->format('Y-m-d H:i:s'));
    }

    private static function withBundleNames(array $urls, ?callable $loadBundles = null): array
    {
        $bundleIds = [];

        foreach($urls as $url){
            if($url->bundle){
                $bundleIds[(string) $url->bundle] = $url->bundle;
            }
        }

        if(!$bundleIds) return $urls;

        $bundles = $loadBundles
            ? $loadBundles(array_values($bundleIds))
            : DB::bundle()->whereIn('id', array_values($bundleIds))->findMany();
        $bundlesById = [];

        foreach($bundles as $bundle){
            $bundlesById[(string) $bundle->id] = $bundle;
        }

        foreach($urls as $url){
            if($url->bundle && isset($bundlesById[(string) $url->bundle])){
                $url->bundlename = $bundlesById[(string) $url->bundle]->name;
            }
        }

        return $urls;
    }

    private static function withActivityRelations(
        array $recentActivity,
        ?callable $loadUrls = null,
        ?callable $loadQrs = null,
        ?callable $loadProfiles = null
    ): array {
        $urlIds = [];

        foreach($recentActivity as $stats){
            $urlIds[(string) $stats->urlid] = $stats->urlid;
        }

        if(!$urlIds) return $recentActivity;

        $urls = $loadUrls
            ? $loadUrls(array_values($urlIds))
            : DB::url()->whereIn('id', array_values($urlIds))->findMany();
        $urlsById = [];
        $qrUrlIds = [];
        $profileUrlIds = [];

        foreach($urls as $url){
            $urlsById[(string) $url->id] = $url;
            if($url->qrid) $qrUrlIds[(string) $url->id] = $url->id;
            if($url->profileid) $profileUrlIds[(string) $url->id] = $url->id;
        }

        $qrsByUrlId = [];
        if($qrUrlIds){
            $qrs = $loadQrs
                ? $loadQrs(array_values($qrUrlIds))
                : DB::qrs()->select('urlid')->select('name')->whereIn('urlid', array_values($qrUrlIds))->findMany();
            foreach($qrs as $qr){
                if(!isset($qrsByUrlId[(string) $qr->urlid])){
                    $qrsByUrlId[(string) $qr->urlid] = $qr;
                }
            }
        }

        $profilesByUrlId = [];
        if($profileUrlIds){
            $profiles = $loadProfiles
                ? $loadProfiles(array_values($profileUrlIds))
                : DB::profiles()->select('urlid')->select('name')->whereIn('urlid', array_values($profileUrlIds))->findMany();
            foreach($profiles as $profile){
                if(!isset($profilesByUrlId[(string) $profile->urlid])){
                    $profilesByUrlId[(string) $profile->urlid] = $profile;
                }
            }
        }

        foreach($recentActivity as $id => $stats){
            $urlId = (string) $stats->urlid;
            if(!isset($urlsById[$urlId])){
                unset($recentActivity[$id]);
                continue;
            }

            if(isset($qrsByUrlId[$urlId])) $stats->qr = $qrsByUrlId[$urlId]->name;
            if(isset($profilesByUrlId[$urlId])) $stats->profile = $profilesByUrlId[$urlId]->name;
            $stats->url = $urlsById[$urlId];
        }

        return $recentActivity;
    }
}
