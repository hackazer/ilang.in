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

use Core\View;
use Core\Helper;
use Core\Request;
use Core\Response;
use Core\DB;
use Models\User;
use Helpers\Emails;
use Helpers\Payments\Nowpayments\Client as NowPaymentsClient;
use Helpers\Payments\Nowpayments\Credentials as NowPaymentsCredentials;
use Helpers\Payments\Nowpayments\CurlTransport as NowPaymentsTransport;
use Helpers\Payments\Nowpayments\Reconciler as NowPaymentsReconciler;

class Cron {
    use Traits\Links;

    private const URL_SCAN_LIMIT = 500;
    private const URL_SCAN_INTERVAL_SECONDS = 3600;

    public function nowpayments(string $token){
        if(!hash_equals(md5('nowpayments'.AuthToken), $token)) return null;

        $stored = config('nowpayments');
        if(!$stored || empty($stored->enabled) || empty($stored->reconciliation_enabled)) return null;

        try {
            $settings = NowPaymentsCredentials::runtime($stored, static fn(string $secret): string => Helper::decrypt($secret));
            $client = new NowPaymentsClient(
                new NowPaymentsTransport(),
                (string) ($settings['api_key'] ?? ''),
                ($settings['environment'] ?? 'sandbox') === 'production' ? NowPaymentsClient::PRODUCTION_URL : NowPaymentsClient::SANDBOX_URL
            );
            $result = (new NowPaymentsReconciler($client, $settings))->run(50);
            GemError::channel('Cron.nowpayments');
            GemError::toChannel('Cron.nowpayments', json_encode($result));
        } catch(\Throwable $exception) {
            GemError::log('NOWPayments reconciliation failed: '.$exception::class);
        }
    }

    /**
     * Check User Cron Jobs
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.2.1.1
     * @param string $token
     * @return void
     */
    public function user(string $token){
        
        if(!hash_equals(md5('user'.AuthToken), $token)) return null;

        if(!\Helpers\App::isExtended() || !config('pro')) return null;

        $i = 0;
        foreach(User::where('admin', 0)->where('pro', '1')->findMany() as $user){
            
			if($user->pro && strtotime($user->expiration) < time() || ($user->trial && strtotime('now') > strtotime($user->expiration))) {
                $user->pro = 0;
                $user->planid = null;
                $user->trial = 0;
                $user->save();
                if($user->email){
                    Emails::canceled($user);
                }                                       
                $i++;
			}
        }
        GemError::channel('Cron.users');
        GemError::toChannel('Cron.users', $i > 0 ? "{$i} users were downgraded.": "Nothing to report.");

    }
    /**
     * Remove Data
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.2.1.1
     * @param string $token
     * @return void
     */
    public function data(string $token){

        if(!hash_equals(md5('data'.AuthToken), $token)) return null;

        if(!config('pro')) return null;

        $ids = null;
        
        foreach(User::select('id')->select('planid')->where('admin', 0)->whereNotNull('planid')->whereNull('teamid')->findArray() as $user){

            if(!$plan = DB::plans()->where('id', $user['planid'])->first()) continue;

            $retention = $plan->retention;
            
            if($retention == 0) continue;
            
            $cutoff = date('Y-m-d 00:00:00', strtotime("-{$retention} days"));
            DB::stats()->where('urluserid', $user['id'])->whereLt('date', $cutoff)->deleteMany();
            $ids .= "#{$user['id']},";
        }

        GemError::channel('Cron.data');
        GemError::toChannel('Cron.data', $ids ? "Data for users {$ids} were removed.": "Nothing to report.");

    }
    /**
     * Check URLs
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.2.1.1
     * @param string $token
     * @return void
     */
    public function urls(string $token){
        
        if(!hash_equals(md5('url'.AuthToken), $token)) return null;

        $i = 0;
        
        foreach(self::safetyScanUrls() as $url){
            
            $detected = false;
            // Check blacklist domain
            if(!$url->qrid && !$url->profileid && ($this->domainBlacklisted($url->url) || $this->wordBlacklisted($url->url))){
                $detected = true;
            }

            // Check with Google Web Risk
            if(!$url->qrid && !$url->profileid && !$this->safe($url->url)) {
                $detected = true;
            }

            // Check with Phish
            if(!$url->qrid && !$url->profileid && $this->phish($url->url)) {
               $detected = true;
            }
            
            // Check with VirusTotal
            if(!$url->qrid && !$url->profileid && $this->virus($url->url)) {
                $detected = true;
            }

            if($detected){
                $url->status = 0;
                $url->save();
                
                if(DB::reports()->where('url', $url->url)->first()) continue;
        
                $report = DB::reports()->create();
                $report->url = \Helpers\App::shortRoute($url->domain, $url->alias.$url->custom);
                $report->type = "Disabled by cron";
                $report->email = "Cron Job";
                $report->bannedlink = $url->url;
                $report->status = 1;
                $report->ip = null;
                $report->date = Helper::dtime();
                $report->save();
                $i++;
            }
        }

        GemError::channel('Cron.urls');
        GemError::toChannel('Cron.urls', $i > 0 ? "{$i} urls were blocked.": "Nothing to report.");
    }

    /**
     * Fetch a deterministic, bounded URL safety-scan window.
     *
     * The first range starts at an hourly rotating ID pivot. If sparse IDs leave
     * the range short, a second indexed range wraps to the beginning of the table.
     *
     * @param callable|null $queryFactory Test seam returning an eligible URL query.
     * @param int|null $bucket Deterministic rotation bucket.
     * @param int|null $limit Maximum number of URLs to return.
     * @return list<object>
     */
    private static function safetyScanUrls(?callable $queryFactory = null, ?int $bucket = null, ?int $limit = null): array
    {
        $queryFactory ??= static fn() => DB::url()
            ->whereNull('qrid')
            ->whereNull('profileid')
            ->where('status', 1);

        $limit = max(1, min(self::URL_SCAN_LIMIT, $limit ?? self::URL_SCAN_LIMIT));
        $maxId = (int) $queryFactory()->max('id');

        if($maxId < 1) return [];

        $bucket ??= intdiv(time(), self::URL_SCAN_INTERVAL_SECONDS);
        $startId = self::safetyScanStartId($maxId, $bucket);

        $urls = self::resultArray(
            $queryFactory()
                ->whereGte('id', $startId)
                ->orderByAsc('id')
                ->limit($limit)
                ->findMany()
        );

        $remaining = $limit - count($urls);

        if($remaining > 0 && $startId > 1){
            $urls = array_merge(
                $urls,
                self::resultArray(
                    $queryFactory()
                        ->whereLt('id', $startId)
                        ->orderByAsc('id')
                        ->limit($remaining)
                        ->findMany()
                )
            );
        }

        return $urls;
    }

    /**
     * Map a rotation bucket across the full positive ID range without overflow.
     */
    private static function safetyScanStartId(int $maxId, int $bucket): int
    {
        if($maxId <= 1) return 1;

        $secret = defined('AuthToken') ? (string) AuthToken : 'cron-url-safety-scan';
        $bytes = unpack('C*', hash('sha256', $secret.':'.$bucket, true));
        $remainder = 0;

        foreach($bytes as $byte){
            for($bit = 0; $bit < 8; $bit++){
                $remainder = self::addModulo($remainder, $remainder, $maxId);
            }

            $remainder = self::addModulo($remainder, $byte % $maxId, $maxId);
        }

        return $remainder + 1;
    }

    /**
     * Add two non-negative modular values without overflowing PHP integers.
     */
    private static function addModulo(int $left, int $right, int $modulus): int
    {
        return $left >= $modulus - $right
            ? $left - ($modulus - $right)
            : $left + $right;
    }

    /** @return list<object> */
    private static function resultArray(iterable $rows): array
    {
        return is_array($rows) ? array_values($rows) : array_values(iterator_to_array($rows, false));
    }
    /**
     * Remind Users
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.2.1
     * @param string $days
     * @param string $token
     * @return void
     */
    public function remind(string $days, string $token){
        
        if(!hash_equals(md5('remind'.AuthToken), $token)) return null;

        $i = 0;
        
        foreach(User::where('admin', 0)->where('trial', 1)->findArray() as $user){

            if(date('d-m-Y') == date('d-m-Y', strtotime("-{$days} days", strtotime($user['expiration'])))){
                Emails::remind($user);
                $i++;
            }
        }

        GemError::channel('Cron.reminded');
        GemError::toChannel('Cron.reminded', $i > 0 ? "{$i} users were reminded.": "Nothing to report.");
    }
}
