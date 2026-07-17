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
use Core\Request;

class Integrations {

    /**
     * INtegrations
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.3
     * @return void
     */
    public function index(Request $request, string $provider){

		if($class = $this->integrations($provider)){
            if(isset($class[0]) && \method_exists($class[0], $class[1])) return call_user_func($class, $request);
		}

        return stop(404);
    }
    /**
     * Integrations
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.3
     * @param [type] $name
     * @return void
     */
    public function integrations($name = null){

        $list = [			 
			'slack' => [self::class, 'slack'],
            'zapier' => [self::class, 'zapier'],
            'wordpress' => [self::class, 'wordpress'],
            'plugin' => [self::class, 'plugin'],
            'shortcuts' => [self::class, 'shortcuts']
		];

		if($extended = \Core\Plugin::dispatch('integrations.extend')){
			foreach($extended as $fn){
				$list = array_merge($list, $fn);
			}
		}

		if(isset($list[$name])) return $list[$name];

		return $list;
    }
    /**
     * Slack Integration
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.3
     * @return void
     */
    public static function slack(){

        if(!config('slackclientid') || !config('slacksecretid')){
            stop(404);
        }

        View::set('title', e('Slack Integration'));
        
        $slack = new \Helpers\Slack(config('slackclientid'), config('slacksecretid'), route('user.slack'));

        return View::with('integrations.slack', compact('slack'))->extend('layouts.dashboard');
    }    
    /**
     * Zapier Integration
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.3
     * @return void
     */
    public static function zapier(){

        View::set('title', e('Zapier Integration'));
    
        return View::with('integrations.zapier')->extend('layouts.dashboard');
    }    
    /**
     * WordPress Integration
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.3
     * @return void
     */
    public static function wordpress(){

        if(!config('api') || !user()->has('api')) return \Models\Plans::notAllowed();

        View::set('title', e('WordPress Integration'));

        \Helpers\CDN::load('hljs');
        View::push('<script>hljs.highlightAll();</script>','custom')->tofooter();
    
        return View::with('integrations.wordpress')->extend('layouts.dashboard');
    } 
    /**
     * Shortcuts Integration
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.3
     * @return void
     */
    public static function shortcuts(){
        
        if(!config('api') || !user()->has('api')) return \Models\Plans::notAllowed();

        View::set('title', e('Shortcuts Integration'));
    
        return View::with('integrations.shortcuts')->extend('layouts.dashboard');
    }
    /**
     * WP Plugin
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.3
     * @return void
     */
    public function plugin(){
        
        if(!config('api') || !user()->has('api')) return \Models\Plans::notAllowed();

        $plugin = file_get_contents(STORAGE."/app/wpplugin.php");

		$plugin = str_replace("__URL__", config('url'), $plugin);
		$plugin = str_replace("__AUTHOR__", config('title'), $plugin);
		$plugin = str_replace("__API__", route('api.url.create'), $plugin);
		$plugin = str_replace("__KEY__", user()->api, $plugin);


        $this->deliverPluginArchive($plugin);
    }

    /**
     * Generate, stream, and remove a user-specific WordPress plugin archive.
     */
    protected function deliverPluginArchive(string $plugin): void
    {
        $path = $this->createPluginArchive($plugin);

        try {
            $this->streamPluginArchive($path);
        } finally {
            $this->removePluginArchive($path);
        }
    }

    /**
     * Create the archive in an atomically allocated private temporary file.
     */
    protected function createPluginArchive(string $plugin): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ilangin-wordpress-');

        if ($path === false) {
            throw new \RuntimeException('Plugin archive temporary file could not be created.');
        }

        $zip = new \ZipArchive();
        $isOpen = false;

        try {
            if (!chmod($path, 0600)) {
                throw new \RuntimeException('Plugin archive permissions could not be secured.');
            }

            if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Plugin archive could not be opened.');
            }

            $isOpen = true;

            if (!$zip->addFromString('plugin.php', $plugin)) {
                throw new \RuntimeException('Plugin file could not be added to the archive.');
            }

            $closed = $zip->close();
            $isOpen = false;

            if (!$closed) {
                throw new \RuntimeException('Plugin archive could not be finalized.');
            }

            return $path;
        } catch (\Throwable $exception) {
            if ($isOpen) {
                $zip->close();
            }

            $this->removePluginArchive($path);

            throw $exception;
        }
    }

    /**
     * Stream the generated archive without allowing browser or proxy caching.
     */
    protected function streamPluginArchive(string $path): void
    {
        $stream = fopen($path, 'rb');

        if ($stream === false) {
            throw new \RuntimeException('Plugin archive could not be opened for download.');
        }

        try {
            $metadata = fstat($stream);

            if ($metadata === false || !isset($metadata['size'])) {
                throw new \RuntimeException('Plugin archive size could not be determined.');
            }

            foreach ($this->pluginDownloadHeaders((int) $metadata['size']) as $header) {
                header($header, true);
            }

            while (!feof($stream)) {
                $chunk = fread($stream, 8192);

                if ($chunk === false) {
                    throw new \RuntimeException('Plugin archive could not be streamed.');
                }

                if ($chunk !== '') {
                    echo $chunk;
                }
            }
        } finally {
            fclose($stream);
        }
    }

    protected function pluginDownloadHeaders(int $size): array
    {
        return [
            'Content-Disposition: attachment; filename="linkshortenershortcode.zip"',
            'Content-Type: application/zip',
            'Content-Length: '.$size,
            'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma: no-cache',
            'Expires: 0',
            'X-Content-Type-Options: nosniff',
        ];
    }

    protected function removePluginArchive(string $path): void
    {
        if (is_file($path) && !unlink($path)) {
            throw new \RuntimeException('Plugin archive temporary file could not be removed.');
        }
    }
}
