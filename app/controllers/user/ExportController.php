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

use \Core\Helper;
use \Core\View;
use \Core\DB;
use \Core\Auth;
use \Core\Request;

class Export {
    private const EXPORT_BATCH_SIZE = 500;

    /**
     * Check if user can export
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     */
    public function __construct(){
        if(\Models\User::where('id', Auth::user()->rID())->first()->has('export') === false){
			return \Models\Plans::notAllowed();
		}
    }
    /**
     * Export Data
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param \Core\Request $request
     * @return void
     */
    public function links(Request $request){      

        if(Auth::user()->teamPermission('export') == false){
			return Helper::redirect()->to(route('dashboard'))->with('danger', e('You do not have this permission. Please contact your team administrator.'));
		}  

        $ownerId = Auth::user()->rID();
        $defaultDomain = config('url');

        self::downloadCsv(
            'MyLinks-'.date('d-m-Y').'.csv',
            ['Short URL', 'Long URL', 'Campaign', 'Date', 'Clicks', 'Unique Clicks'],
            static fn(int $offset, int $limit): array => DB::url()
                ->select('id')
                ->select('domain')
                ->select('alias')
                ->select('custom')
                ->select('url')
                ->select('bundle')
                ->select('date')
                ->select('click')
                ->select('uniqueclick')
                ->where('userid', $ownerId)
                ->whereNull('qrid')
                ->whereNull('profileid')
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->limit($limit)
                ->offset($offset)
                ->findArray(),
            static function(array $links) use ($defaultDomain): iterable {
                $campaignNames = self::campaignNamesForLinks($links);

                foreach($links as $link){
                    yield self::linkCsvRow($link, $campaignNames, $defaultDomain);
                }
            }
        );
    }
    /**
     * Export Single Stats
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param integer $id
     * @return void
     */
    public function single(int $id){

        if(Auth::user()->teamPermission('export') == false){
			return Helper::redirect()->to(route('dashboard'))->with('danger', e('You do not have this permission. Please contact your team administrator.'));
		}  
        $ownerId = Auth::user()->rID();

        if(!$url = DB::url()
            ->select('alias')
            ->select('custom')
            ->select('domain')
            ->where('id', $id)
            ->where('userid', $ownerId)
            ->first()
        ) {
            return Helper::redirect()->back()->with('danger', e('Link does not exist.'));
        }

        $link = [
            'alias' => $url->alias,
            'custom' => $url->custom,
            'domain' => $url->domain,
        ];
        $defaultDomain = config('url');

        self::downloadCsv(
            'ReportLink_'.Helper::dtime('now', 'd-m-Y').'.csv',
            ['Short URL', 'Date', 'City', 'Country', 'Browser', 'Platform', 'Language', 'Domain', 'Referer'],
            static fn(int $offset, int $limit): array => DB::stats()
                ->where('urluserid', $ownerId)
                ->where('urlid', $id)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->limit($limit)
                ->offset($offset)
                ->findArray(),
            static function(array $stats) use ($link, $defaultDomain): iterable {
                foreach($stats as $row){
                    yield self::statsCsvRow($row, $link, $defaultDomain);
                }
            }
        );
    }

    /**
     * Export Campaign Stats
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param \Core\Request $request
     * @param integer $id
     * @return void
     */
    public function stats(Request $request){

        if(Auth::user()->teamPermission('export') == false){
			return Helper::redirect()->to(route('dashboard'))->with('danger', e('You do not have this permission. Please contact your team administrator.'));
		}  

        
        if(!$range = self::parseDateRange((string) $request->customreport)){
            return Helper::redirect()->back()->with('danger', e('Please specify a range.'));
        }

        $ownerId = Auth::user()->rID();
        $defaultDomain = config('url');

        self::downloadCsv(
            'ReportAll_'.Helper::dtime('now', 'd-m-Y').'.csv',
            ['Short URL', 'Date', 'City', 'Country', 'Browser', 'Platform', 'Language', 'Domain', 'Referer'],
            static function(int $offset, int $limit) use ($ownerId, $range): array {
                $query = DB::stats()->where('urluserid', $ownerId);

                return self::applyDateRange($query, $range)
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->limit($limit)
                    ->offset($offset)
                    ->findArray();
            },
            static function(array $stats) use ($ownerId, $defaultDomain): iterable {
                $links = self::linksForStats($stats, $ownerId);

                foreach($stats as $row){
                    if(!isset($links[$row['urlid']])) continue;

                    yield self::statsCsvRow($row, $links[$row['urlid']], $defaultDomain);
                }
            }
        );
    }  
    /**
     * Export Campaign Stats
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param \Core\Request $request
     * @param integer $id
     * @return void
     */
    public function campaign(Request $request, int $id){

        if(Auth::user()->teamPermission('export') == false){
			return Helper::redirect()->to(route('dashboard'))->with('danger', e('You do not have this permission. Please contact your team administrator.'));
		}  

        
        if(!\Core\Auth::user()->has('export')){
            return \Models\Plans::notAllowed();
        }

        if(!$range = self::parseDateRange((string) $request->customreport)){
            return Helper::redirect()->back()->with('danger', e('Please specify a range.'));
        }

        $ownerId = Auth::user()->rID();

        if(!$bundle = DB::bundle()->where('id', $id)->where('userid', $ownerId)->first()){
            return \Core\Response::factory('', 404)->json();
        }

        $prefix = defined('DBprefix') ? DBprefix : '';
        $statsTable = $prefix.'stats';
        $urlTable = $prefix.'url';
        $defaultDomain = config('url');

        self::downloadCsv(
            'ReportCampaign_'.Helper::dtime('now', 'd-m-Y').'.csv',
            ['Short URL', 'Date', 'City', 'Country', 'Browser', 'Platform', 'Language', 'Domain', 'Referer'],
            static function(int $offset, int $limit) use (
                $bundle,
                $ownerId,
                $range,
                $statsTable,
                $urlTable
            ): array {
                $query = DB::stats()
                    ->select($statsTable.'.id')
                    ->select($statsTable.'.date')
                    ->select($statsTable.'.city')
                    ->select($statsTable.'.country')
                    ->select($statsTable.'.browser')
                    ->select($statsTable.'.os')
                    ->select($statsTable.'.language')
                    ->select($statsTable.'.domain')
                    ->select($statsTable.'.referer')
                    ->select($urlTable.'.alias', 'url_alias')
                    ->select($urlTable.'.custom', 'url_custom')
                    ->select($urlTable.'.domain', 'url_domain')
                    ->join($urlTable, [$statsTable.'.urlid', '=', $urlTable.'.id'])
                    ->where($statsTable.'.urluserid', $ownerId)
                    ->where($urlTable.'.userid', $ownerId)
                    ->where($urlTable.'.bundle', $bundle->id);

                return self::applyDateRange($query, $range, $statsTable.'.date')
                    ->orderByDesc($statsTable.'.date')
                    ->orderByDesc($statsTable.'.id')
                    ->limit($limit)
                    ->offset($offset)
                    ->findArray();
            },
            static function(array $stats) use ($defaultDomain): iterable {
                foreach($stats as $row){
                    yield self::statsCsvRow($row, [
                        'alias' => $row['url_alias'],
                        'custom' => $row['url_custom'],
                        'domain' => $row['url_domain'],
                    ], $defaultDomain);
                }
            }
        );
    }

    private static function downloadCsv(
        string $filename,
        array $header,
        callable $loadBatch,
        callable $rowsForBatch
    ): void {
        $response = new \Core\Response('', 200, [
            'content-type' => 'text/csv',
            'content-disposition' => 'attachment;filename='.$filename,
        ]);
        $stream = fopen('php://output', 'wb');

        if($stream === false){
            throw new \RuntimeException('Unable to open CSV export stream.');
        }

        try {
            self::writeCsvRow($stream, $header);
            self::streamBatches($loadBatch, static function(array $batch) use ($stream, $rowsForBatch): void {
                foreach($rowsForBatch($batch) as $row){
                    self::writeCsvRow($stream, $row);
                }
            });
        } finally {
            fclose($stream);
        }

        $response->send();
    }

    /**
     * @return array{string, string}|null
     */
    private static function parseDateRange(string $value): ?array
    {
        $range = explode(' - ', trim($value));

        if(count($range) !== 2) return null;

        $startValue = trim($range[0]);
        $endValue = trim($range[1]);
        $start = \DateTimeImmutable::createFromFormat('!m/d/Y', $startValue);
        $end = \DateTimeImmutable::createFromFormat('!m/d/Y', $endValue);

        if(
            !$start ||
            !$end ||
            $start->format('m/d/Y') !== $startValue ||
            $end->format('m/d/Y') !== $endValue ||
            $end < $start
        ) return null;

        return [
            $start->format('Y-m-d H:i:s'),
            $end->modify('+1 day')->format('Y-m-d H:i:s'),
        ];
    }

    private static function applyDateRange(object $query, array $range, string $column = 'date'): object
    {
        return $query
            ->whereGte($column, $range[0])
            ->whereLt($column, $range[1]);
    }

    /**
     * @param resource $stream
     */
    private static function writeCsvRow($stream, array $row): void
    {
        $row = array_map(static fn(mixed $value): mixed => self::safeCsvCell($value), $row);

        if(fputcsv($stream, $row, ',', '"', '') === false){
            throw new \RuntimeException('Unable to write CSV export row.');
        }
    }

    private static function safeCsvCell(mixed $value): mixed
    {
        if(!is_string($value)) return $value;

        if(preg_match('/^[\t\r]|^[\x00-\x20]*[=+\-@]/', $value) === 1){
            return "'".$value;
        }

        return $value;
    }

    private static function streamBatches(
        callable $loadBatch,
        callable $consumeBatch,
        int $batchSize = self::EXPORT_BATCH_SIZE
    ): void {
        if($batchSize < 1){
            throw new \InvalidArgumentException('Export batch size must be greater than zero.');
        }

        $offset = 0;

        while(true){
            $rows = $loadBatch($offset, $batchSize);
            $count = count($rows);

            if($count === 0) return;

            $consumeBatch($rows);
            $offset += $count;

            if($count < $batchSize) return;
        }
    }

    private static function campaignNamesForLinks(array $links, ?callable $loadCampaigns = null): array
    {
        $campaignIds = [];

        foreach($links as $link){
            if(!empty($link['bundle'])) $campaignIds[(string) $link['bundle']] = $link['bundle'];
        }

        if(!$campaignIds) return [];

        $campaigns = $loadCampaigns
            ? $loadCampaigns(array_values($campaignIds))
            : DB::bundle()
                ->select('id')
                ->select('name')
                ->whereIn('id', array_values($campaignIds))
                ->findArray();
        $names = [];

        foreach($campaigns as $campaign){
            $names[$campaign['id']] = $campaign['name'];
        }

        return $names;
    }

    private static function linksForStats(
        array $stats,
        int $ownerId,
        ?callable $loadLinks = null
    ): array {
        $linkIds = [];

        foreach($stats as $row){
            $linkIds[(string) $row['urlid']] = $row['urlid'];
        }

        if(!$linkIds) return [];

        $links = $loadLinks
            ? $loadLinks(array_values($linkIds), $ownerId)
            : DB::url()
                ->select('id')
                ->select('alias')
                ->select('custom')
                ->select('domain')
                ->where('userid', $ownerId)
                ->whereIn('id', array_values($linkIds))
                ->findArray();
        $linksById = [];

        foreach($links as $link){
            $linksById[$link['id']] = $link;
        }

        return $linksById;
    }

    private static function linkCsvRow(array $link, array $campaignNames, string $defaultDomain): array
    {
        return [
            ($link['domain'] ?: $defaultDomain).'/'.$link['alias'].$link['custom'],
            $link['url'],
            $campaignNames[$link['bundle']] ?? '',
            $link['date'],
            $link['click'],
            $link['uniqueclick'],
        ];
    }

    private static function statsCsvRow(array $stats, array $link, string $defaultDomain): array
    {
        return [
            ($link['domain'] ?: $defaultDomain).'/'.$link['alias'].$link['custom'],
            $stats['date'],
            $stats['city'],
            $stats['country'],
            $stats['browser'],
            $stats['os'],
            $stats['language'],
            $stats['domain'],
            $stats['referer'],
        ];
    }
}
