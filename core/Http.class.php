<?php 
/**
 * ====================================================================================
 *                           GemFramework (c) GemPixel
 * ----------------------------------------------------------------------------------
 *  This software is packaged with an exclusive framework owned by GemPixel Inc as such
 *  distribution or modification of this framework is not allowed before prior consent
 *  from GemPixel administrators. If you find that this framework is packaged in a 
 *  software not distributed by GemPixel or authorized parties, you must not use this
 *  sofware and contact gempixel at https://gempixel.com/contact to inform them of this
 *  misuse otherwise you risk of being prosecuted in courts.
 * ====================================================================================
 *
 * @package Gem\Core\Http
 * @author GemPixel (http://gempixel.com)
 * @copyright 2020 GemPixel
 * @license http://gempixel.com/license
 * @link http://gempixel.com  
 * @since 1.0
 * @example Http::url("http://site.com")->withHeaders(["authorization" => "Token 123456"])->post();
 */
namespace Core;

use Core\Helper;
use GemError;
use Helpers\OutboundUrl;

final class Http {
	private const MAX_TEST_FIXTURE_BYTES = 2097152;
	/**
	 * URL to send request
	 * @var null
	 */
	private $_HTTPURL = NULL;
	/**
	 * CURL Response
	 * @var array
	 */
	private $_HTTPCURLRESPONSE = [];
	/**
	 * CURL Parameters
	 * @var array
	 */
	private $_HTTPCURLPARAMS = [];

	/**
	 * Permit private network destinations for framework-owned internal calls.
	 * User-controlled URLs must never enable this option.
	 * @var bool
	 */
	private $_HTTPAllowPrivateNetwork = false;
	/** @var string */
	private $_HTTPResponseBody = '';
	/** @var bool */
	private $_HTTPResponseLimitExceeded = false;
	/**
	 * Build Http Request
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @param   string|null $url [description]
	 */
	public function __construct(?string $url = null){
		$this->_HTTPURL = $url;
		$this->_HTTPCURLPARAMS = [];
		$this->_HTTPCURLRESPONSE = [];		
		return $this;
	}
	/**
	 * Return Body
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @return  string [description]
	 */
	public function __toString(){
		return $this->getBody();
	}
	/**
	 * Call Statistically
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @param   string|null $url [description]
	 * @return  [type]           [description]
	 */
	public static function url(?string $url = null){
		return new self($url);
	}
	/**
	 * Explicitly permit a framework-owned request to reach a private network.
	 */
	public function allowPrivateNetwork(bool $allow = true){
		$this->_HTTPAllowPrivateNetwork = $allow;
		return $this;
	}
	/**
	 * Get Request Body
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	public function getBody(){
		if(isset($this->_HTTPCURLRESPONSE["curlbody"]) && !empty($this->_HTTPCURLRESPONSE["curlbody"])) return $this->_HTTPCURLRESPONSE["curlbody"];
		return false;
	}
  /**
   * Get Body as Object
   * @author GemPixel <https://gempixel.com>
   * @version 1.0
   * @return  [type] [description]
   */
  	public function bodyObject(){
    	if(isset($this->_HTTPCURLRESPONSE["curlbody"]) && !empty($this->_HTTPCURLRESPONSE["curlbody"])) return json_decode($this->_HTTPCURLRESPONSE["curlbody"]);
    	return false;    
  	}
	/**
	 * Get HTTP Code
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 */
	public function httpCode($code = NULL){
		if(isset($this->_HTTPCURLRESPONSE["http_code"]) && !empty($this->_HTTPCURLRESPONSE["http_code"])) return (int) $this->_HTTPCURLRESPONSE["http_code"];
		return false;
	}	
  /**
   * Set Headers
   * @author GemPixel <https://gempixel.com>
   * @version 1.0
   * @param   [type] $name   [description]
   * @param   [type] $content [description]   
   */
  	public function with($name, $content){

  		$this->_HTTPCURLPARAMS["headers"][ucwords($name, "-")] = $content;
  		return $this;
  	}
  /**
   * Set headers with Array
   * @author GemPixel <https://gempixel.com>
   * @version 1.0
   * @param   array  $headers [description]
   * @return  [type]          [description]
   */
	public function withHeaders(array $headers){
		foreach ($headers as $name => $content) {
			$this->_HTTPCURLPARAMS["headers"][ucwords($name, "-")] = $content;
		}
		return $this;
	}
	/**
	 * Request Auth
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @param   [type] $username [description]
	 * @param   [type] $password [description]
	 * @return  [type]           [description]
	 */
	public function auth($username, $password){
			$this->_HTTPCURLPARAMS["auth"] = "{$username}:{$password}";
			return $this;
	}
	/**
	 * Request Body
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @param   [type] $content [description]
	 */
	public function body($content){

		if(isset($this->_HTTPCURLPARAMS["headers"]["Content-Type"]) && $this->_HTTPCURLPARAMS["headers"]["Content-Type"] == "application/json"){
			$content = json_encode($content);
		}

		$this->_HTTPCURLPARAMS["body"] = $content;
		return $this;
	}
	/**
	 * Send a get request
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @param   array  $options [description]
	 * @return  [type]          [description]
	 */
	public function get($options = []){

		if(isset($this->_HTTPCURLPARAMS["body"]) && !empty($this->_HTTPCURLPARAMS["body"])){
				
			$this->_HTTPURL .= strpos($this->_HTTPURL, "?") ? "&" : "?";

			if(is_array($this->_HTTPCURLPARAMS["body"])){
				$this->_HTTPURL .= http_build_query($this->_HTTPCURLPARAMS["body"]);
			} else{
				$this->_HTTPURL .= $this->_HTTPCURLPARAMS["body"];
			}
		}

		if($this->executePhpunitFixture()) return $this;

		$curl = $this->prepareRequest($options);

			if(isset($this->_HTTPCURLPARAMS["headers"])){
				$headers = [];
				foreach ($this->_HTTPCURLPARAMS["headers"] as $name => $value) {
					$headers[] = "{$name}:{$value}";
				}
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			}

			if(isset($this->_HTTPCURLPARAMS["auth"]) && !empty($this->_HTTPCURLPARAMS["auth"])){
				curl_setopt($curl, CURLOPT_USERPWD, $this->_HTTPCURLPARAMS["auth"]);  
			}

		$this->executeRequest($curl);
		return $this;
	}
	/**
	 * Send a POST request
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @param   array  $options [description]
	 * @return  [type]          [description]
	 */
	public function post($options = []){    	
		$curl = $this->prepareRequest($options);

			if(isset($options["method"]) && in_array($options["method"], ["put", "patch", "delete"])){
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($options["method"]));
			}

			if(isset($this->_HTTPCURLPARAMS["headers"])){
				$headers = [];
				foreach ($this->_HTTPCURLPARAMS["headers"] as $name => $value) {
					$headers[] = "{$name}:{$value}";
				}
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			}

			if(isset($this->_HTTPCURLPARAMS["auth"]) && !empty($this->_HTTPCURLPARAMS["auth"])){
				curl_setopt($curl, CURLOPT_USERPWD, $this->_HTTPCURLPARAMS["auth"]);  
			}

		curl_setopt($curl, CURLOPT_POST, 1);

			if(isset($this->_HTTPCURLPARAMS["body"]) && !empty($this->_HTTPCURLPARAMS["body"])){  		
				if(is_array($this->_HTTPCURLPARAMS["body"])){
					curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($this->_HTTPCURLPARAMS["body"]));
				} else{
					curl_setopt($curl, CURLOPT_POSTFIELDS, $this->_HTTPCURLPARAMS["body"]);
				}
			}

		$this->executeRequest($curl);
		return $this;
	}
	/**
	 * Execute a bounded, non-network fixture only inside the PHPUnit CLI process.
	 * This keeps compatibility tests off cURL without admitting extra production schemes.
	 */
	private function executePhpunitFixture(){
		$phpunitProcess = defined('PHPUNIT_COMPOSER_INSTALL')
			|| class_exists('PHPUnit\\Framework\\TestCase', false);
		if(PHP_SAPI !== 'cli' || !$phpunitProcess) return false;

		$url = (string) $this->_HTTPURL;
		$scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

		if(!in_array($scheme, ['file', 'data'], true)) return false;

		if($scheme === 'file'){
			$path = realpath(rawurldecode((string) parse_url($url, PHP_URL_PATH)));
			$testsRoot = realpath(dirname(__DIR__).'/tests');

			if($path === false || $testsRoot === false || !str_starts_with($path, $testsRoot.DIRECTORY_SEPARATOR) || !is_file($path)){
				throw new \InvalidArgumentException('Local HTTP test fixture is not allowed.');
			}
		} elseif(!preg_match('~^data://text/plain(?:;charset=[a-z0-9._-]+)?(?:;base64)?,~i', $url)){
			throw new \InvalidArgumentException('Inline HTTP test fixture is not allowed.');
		}

		if(strlen($url) > self::MAX_TEST_FIXTURE_BYTES * 2){
			throw new \InvalidArgumentException('HTTP test fixture exceeds the configured size limit.');
		}

		$stream = @fopen($url, 'rb');

		if($stream === false) throw new \InvalidArgumentException('HTTP test fixture could not be opened.');

		try {
			$body = stream_get_contents($stream, self::MAX_TEST_FIXTURE_BYTES + 1);
		} finally {
			fclose($stream);
		}

		if($body === false || strlen($body) > self::MAX_TEST_FIXTURE_BYTES){
			throw new \InvalidArgumentException('HTTP test fixture exceeds the configured size limit.');
		}

		$this->_HTTPCURLRESPONSE = [
			'curlbody' => $body,
			'http_code' => 200,
			'test_fixture' => true,
		];

		return true;
	}
	/**
	 * Build a bounded cURL request after validating and pinning its destination.
	 */
	private function prepareRequest(array $options){
		if(!class_exists(OutboundUrl::class)){
			require_once dirname(__DIR__).'/app/helpers/OutboundUrl.php';
		}

		$target = OutboundUrl::assertSafe((string) $this->_HTTPURL, $this->_HTTPAllowPrivateNetwork);
		$timeout = isset($options['timeout']) ? (int) $options['timeout'] : OutboundUrl::DEFAULT_TOTAL_TIMEOUT;
		$connectTimeout = isset($options['connect_timeout'])
			? (int) $options['connect_timeout']
			: (isset($options['timeout']) ? (int) $options['timeout'] : OutboundUrl::DEFAULT_CONNECT_TIMEOUT);
		$curl = curl_init($this->_HTTPURL);

		if($curl === false) throw new \RuntimeException('Unable to initialize outbound HTTP request.');

		$this->_HTTPResponseBody = '';
		$this->_HTTPResponseLimitExceeded = false;
		$curlOptions = OutboundUrl::curlOptions($target, $connectTimeout, $timeout);
		$curlOptions[CURLOPT_WRITEFUNCTION] = OutboundUrl::responseWriter(
			$this->_HTTPResponseBody,
			$this->_HTTPResponseLimitExceeded
		);
		curl_setopt_array($curl, $curlOptions);

		return $curl;
	}
	/**
	 * Execute a prepared request and retain the legacy response shape.
	 */
	private function executeRequest($curl){
		$result = curl_exec($curl);
		$this->_HTTPCURLRESPONSE = curl_getinfo($curl);
		$this->_HTTPCURLRESPONSE["curlbody"] = $result === false ? false : $this->_HTTPResponseBody;

		if($result === false){
			$error = $this->_HTTPResponseLimitExceeded || curl_errno($curl) === CURLE_FILESIZE_EXCEEDED
				? 'Outbound HTTP response exceeded the configured size limit.'
				: curl_error($curl);
			$this->_HTTPCURLRESPONSE['curl_error'] = $error;

			if($error && class_exists(GemError::class)) GemError::log($error);
		}

		unset($curl);
	}
	/**
	 * Delete Request
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @param   array  $options [description]
	 * @return  [type]          [description]
	 */
	public function delete($options = []){
		return $this->post(["method" => "delete"]);
	}  
	/**
	 * Put Request
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @param   array  $options [description]
	 * @return  [type]          [description]
	 */
		public function put($options = []){
		return $this->post(["method" => "put"]);
	}  
	/**
	 * Patch Request
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @param   array  $options [description]
	 * @return  [type]          [description]
	 */
		public function patch($options = []){
		return $this->post(["method" => "patch"]);
	}  
	/**
	 * Request Response
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	public function response(?string $name = null){
		if(!is_null($name)) return isset($this->_HTTPCURLRESPONSE[$name]) ? $this->_HTTPCURLRESPONSE[$name] : null;
		return $this->_HTTPCURLRESPONSE;
	}
}
