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

use Core\Request;
use Core\DB;
use Core\Auth;
use Core\Helper;
use Core\View;
use Models\User;

class QR {

    /**
     * Generate QR
     *
     * @author GemPixel <https://gempixel.com> 
     * @version 6.0
     * @param string $alias
     * @return void
     */
    public function generate(string $alias){

        if(!$qr = DB::qrs()->where('alias', $alias)->first()){
            die();
        }
        
        $qr->data = json_decode($qr->data);

        if($qr->urlid && $url = DB::url()->where('id', $qr->urlid)->first()){        
            $data = ['type' => 'link', 'data' =>  \Helpers\App::shortRoute($url->domain, $url->alias.$url->custom)];
        } else {        
            $data = ['type' => $qr->data->type, 'data' => $qr->data->data];
        }

        try {

            if($qr->filename && file_exists(View::storage($qr->filename, 'qr'))){
                header('Location: '.uploads($qr->filename, 'qr'));
                exit;
            }

            $margin = isset($qr->data->margin) && is_numeric($qr->data->margin) && $qr->data->margin <= 10 ? $qr->data->margin : 0;

            $data = \Helpers\QR::factory($data, 400, $margin)->format('png');

            if(isset($qr->data->gradient)){
                if(isset($qr->data->eyecolor) && $qr->data->eyecolor){
                    $qr->data->gradient[] = $qr->data->eyecolor;
                }

                $data->gradient(...$qr->data->gradient);

            } else {
                $data->color($qr->data->color->fg, $qr->data->color->bg, $qr->data->eyecolor ?? null);
            }

            if(isset($qr->data->matrix)){
                $data->module($qr->data->matrix);
            }

            if(isset($qr->data->eye)){
                $data->eye($qr->data->eye);
            }
            

            if(isset($qr->data->definedlogo) && $qr->data->definedlogo){
                $data->withLogo(PUB.'/static/images/'.$qr->data->definedlogo, ($margin > 0) ? (80 - $margin*4) : 80);
            }  

            if(isset($qr->data->custom) && $qr->data->custom && file_exists(View::storage($qr->data->custom, 'qr'))){
                $data->withLogo(View::storage($qr->data->custom, 'qr'), ($margin > 0) ? (80 - $margin*4) : 80);
            }

            $directory = appConfig('app.storage')['qr']['path'];
            $filename = self::cacheFilename((string) $qr->alias);
            $encodedData = json_encode($qr->data);

            self::publishCacheFile(
                $directory,
                $filename,
                static function(string $temporaryPath) use ($data): void {
                    $data->create('file', $temporaryPath);
                },
                static function() use ($qr, $filename, $encodedData): void {
                    $qr->filename = $filename;
                    $qr->data = $encodedData;
                    $qr->save();
                }
            );
            
            $data->create('raw');

        } catch(\Exception $e){
            return \Core\Response::factory($e->getMessage())->send();
        }
    }

    /**
     * Build a stable cache filename shared by concurrent requests.
     */
    private static function cacheFilename(string $alias): string {

        $safeAlias = preg_replace('/[^A-Za-z0-9_-]+/', '-', $alias);
        $safeAlias = trim((string) $safeAlias, '-');
        $safeAlias = substr($safeAlias ?: 'qr', 0, 80);

        return $safeAlias.'-'.substr(hash('sha256', $alias), 0, 16).'.png';
    }

    /**
     * Persist and publish a complete cache file without exposing partial output.
     *
     * The deterministic final path makes concurrent output interchangeable.
     * Temporary files are created in the destination directory so rename stays
     * atomic on the same filesystem. A complete concurrent winner is reused.
     * Persistence happens first so a failed database write cannot orphan output.
     *
     * @param callable(string): void $render
     * @param callable(): void $persist
     */
    private static function publishCacheFile(string $directory, string $filename, callable $render, callable $persist): void {

        $directory = rtrim($directory, '/\\');

        if(!is_dir($directory)){
            throw new \RuntimeException('QR cache directory is not available.');
        }

        if($filename !== basename($filename)){
            throw new \RuntimeException('Invalid QR cache filename.');
        }

        $finalPath = $directory.DIRECTORY_SEPARATOR.$filename;

        $persist();

        clearstatcache(true, $finalPath);

        if(!self::isCompleteCacheFile($finalPath)){
            $temporaryPath = @tempnam($directory, '.qr-');

            if($temporaryPath === false){
                throw new \RuntimeException('Unable to create a temporary QR cache file.');
            }

            if(realpath(dirname($temporaryPath)) !== realpath($directory)){
                @unlink($temporaryPath);
                throw new \RuntimeException('Temporary QR cache file is on a different filesystem.');
            }

            try {
                $render($temporaryPath);
                clearstatcache(true, $temporaryPath);

                if(!self::isCompleteCacheFile($temporaryPath)){
                    throw new \RuntimeException('QR cache generation produced an empty file.');
                }

                if(!@chmod($temporaryPath, 0644)){
                    throw new \RuntimeException('Unable to set QR cache file permissions.');
                }

                if(!@rename($temporaryPath, $finalPath)){
                    clearstatcache(true, $finalPath);

                    if(!self::isCompleteCacheFile($finalPath)){
                        throw new \RuntimeException('Unable to publish the QR cache file.');
                    }
                }
            } finally {
                if(is_file($temporaryPath)){
                    @unlink($temporaryPath);
                }
            }
        }
    }

    /**
     * Determine whether a cache path contains publishable output.
     */
    private static function isCompleteCacheFile(string $path): bool {

        return is_file($path) && filesize($path) > 0;
    }

    /**
	 * Download QR
	 *
	 * @author GemPixel <https://gempixel.com> 
	 * @version 6.0
	 * @param \Core\Request $request
	 * @param string $alias
	 * @param string $format
	 * @param integer $size
	 * @return void
	 */
	public function download(Request $request, string $alias, string $format, int $size = 300){
		
        if(!$qr = DB::qrs()->where('alias', $alias)->first()){
            stop(404);
        }
        
        $qr->data = json_decode($qr->data);

        if($qr->urlid && $url = DB::url()->where('id', $qr->urlid)->first()){        
            $data = ['type' => 'link', 'data' =>  \Helpers\App::shortRoute($url->domain, $url->alias.$url->custom)];
        } else {        
            $data = ['type' => $qr->data->type, 'data' => $qr->data->data];
        }
		
        $qrsize = 300;

        $margin = isset($qr->data->margin) && is_numeric($qr->data->margin) && $qr->data->margin <= 10 ? $qr->data->margin : 0;

		if(is_numeric($size) && $size > 50 && $size <= 1000) $qrsize = $size;
		
		$data = \Helpers\QR::factory($data, $qrsize, $margin)->format($format);

        if(isset($qr->data->gradient)){
            
            if(isset($qr->data->eyecolor) && $qr->data->eyecolor){
                $qr->data->gradient[] = $qr->data->eyecolor;
            }

            $data->gradient(...$qr->data->gradient);
        } else {
            $data->color($qr->data->color->fg, $qr->data->color->bg, $qr->data->eyecolor ?? null);
        }

        if($qr->data->matrix){
            $data->module($qr->data->matrix);
        }

        if($qr->data->eye){
            $data->eye($qr->data->eye);
        }
        $baseLogoSize = ($margin > 0) ? (80 - $margin*4) : 80;

        if(isset($qr->data->definedlogo) && $qr->data->definedlogo){
            $data->withLogo(PUB.'/static/images/'.$qr->data->definedlogo, $baseLogoSize * $qrsize/300);
        }  

        if(isset($qr->data->custom) && $qr->data->custom && file_exists(View::storage($qr->data->custom, 'qr'))){
            $data->withLogo(View::storage($qr->data->custom, 'qr'), $baseLogoSize);
        }

		return \Core\File::contentDownload('QR-code-'.$alias.'.'.$data->extension(), function() use ($data) {
			return $data->string();
		});
	}
}
