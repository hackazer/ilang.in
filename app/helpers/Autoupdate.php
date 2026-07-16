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

namespace Helpers;

class AutoUpdate {

	/**
	 * Constant
	 */
	const latestVersion = "1.0";
	const serverURL = "https://cdn.gempixel.com/updater";
	/**
	 * Private
	 * @var null
	 */
	private $endpoint = NULL;	
	private $purchaseKey = NULL;
	private $error = NULL;
	private $archivePath = NULL;

	/**
	 * [__construct description]
	 * @author KBRmedia <https://gempixel.com>
	 * @version 1.0
	 */
	
	public function __construct($key) {		
		$this->purchaseKey = $key;
	}
	/**
	 * [install description]
	 * @author KBRmedia <https://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	public function install(){
		// Check to make sure everything is OK
		$this->check();
		
		if($this->verify()){
			return true;
		}

		$this->error = "An unexpected error occurred. Please update manually.";
		throw new \Exception($this->error);		
		return false;		
	}
	/**
	 * [check description]
	 * @author KBRmedia <https://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	private function check(){		
		// Check cURL
		if(!in_array('curl', get_loaded_extensions())){ 
			$this->error = "cURL library is not available. Please update manually.";
			throw new \Exception($this->error);			
			return false;
		}

		// Check ZipArchive
		if(!class_exists("ZipArchive")){
			$this->error = "ZipArchive library is not available. Please update manually.";
			throw new \Exception($this->error);			
			return false;
		}

		// Check Permission
		if(!is_writable(ROOT)){
			$this->error = ROOT." is not writable. Please change the permission to 775.";
			throw new \Exception($this->error);			
			return false;
		}

		// Check Key
		if(is_null($this->purchaseKey) || empty($this->purchaseKey)){
			$this->error = "Purchase key is invalid. You can find your purchase key in the downloads section of codecanyon.";
			throw new \Exception($this->error);			
			return false;			
		}
	}
	/**
	 * [getMessage description]
	 * @author KBRmedia <https://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	public function getMessage(){
			return $this->error;
	}
	/**
	 * [verify description]
	 * @author KBRmedia <https://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	private function verify(){	

		$this->endpoint = self::serverURL."/".self::latestVersion."/";
		
		$response = $this->http(["data" => ["url" => \url()]]);

		$http = json_decode($response);

		if(isset($http->status) && $http->status == "validated"){
			return $this->download($http->download);
		}

		$this->error = "An error occurred: {$http->message}";
		throw new \Exception($this->error);		
		return false;
	}

	/**
	 * [download description]
	 * @author KBRmedia <https://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	protected function download($link){
		if(!$this->isHttpsEndpoint($link)){
			throw new \Exception('The update download URL is invalid.');
		}

		$this->endpoint = $link;
		$content = $this->http();
		$this->archivePath = tempnam(ROOT, '.ilang-update-');

		if(!is_string($this->archivePath) || file_put_contents($this->archivePath, $content, LOCK_EX) === false){
			$this->error = "The file cannot be downloaded due to server permission. Please change directory permission or update manually.";
			throw new \Exception($this->error);
		}

		chmod($this->archivePath, 0600);

		return $this->extract();
	}

	/**
	 * [extract description]
	 * @author KBRmedia <https://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	protected function extract(){
		try {
			(new ArchiveValidator())->extract($this->archivePath, ROOT, ArchiveValidator::TYPE_APPLICATION);
			return true;
		} catch (\Throwable $exception) {
			$this->error = "The downloaded update package is invalid or cannot be extracted safely.";
			throw new \Exception($this->error, 0, $exception);
		} finally {
			if(is_string($this->archivePath) && is_file($this->archivePath)){
				unlink($this->archivePath);
			}
			$this->archivePath = null;
		}
	}
	/**
	 * [http description]
	 * @author KBRmedia <https://gempixel.com>
	 * @version 1.0
	 * @param   [type] $url    [description]
	 * @param   array  $option [description]
	 * @return  [type]         [description]
	 */
	protected function http($option = []){
		if(!$this->isHttpsEndpoint($this->endpoint)){
			throw new \Exception('The update endpoint is invalid.');
		}

		$curl = curl_init();
		$options = [
			CURLOPT_URL => $this->endpoint,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 60,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_HTTPHEADER => [
				"X-Authorization: TOKEN ".$this->purchaseKey,
				"X-Script: Premium URL Shortener",
				"X-Version: ".config('version')
			],
		];

		if(isset($option["data"]) && is_array($option["data"])){
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = http_build_query($option["data"], '', '&', PHP_QUERY_RFC3986);
		}

		curl_setopt_array($curl, $options);
		$response = curl_exec($curl);
		$status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
		$error = curl_error($curl);
		unset($curl);

		if(!is_string($response) || $status < 200 || $status >= 300){
			throw new \Exception($error ?: 'The update server returned an invalid response.');
		}

		return $response;
	}

	private function isHttpsEndpoint($endpoint){
		if(!is_string($endpoint) || filter_var($endpoint, FILTER_VALIDATE_URL) === false){
			return false;
		}

		$parts = parse_url($endpoint);

		return is_array($parts)
			&& strtolower((string) ($parts['scheme'] ?? '')) === 'https'
			&& !isset($parts['user'], $parts['pass']);
	}
}
