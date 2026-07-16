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
 * @package Gem\Core\Request
 * @author GemPixel (http://gempixel.com)
 * @copyright 2020 GemPixel
 * @license http://gempixel.com/license
 * @link http://gempixel.com  
 * @since 1.0
 */
namespace Core;

use Core\Helper;

final class Request {
	/**
	 * Sessions
	 *
	 * @author GemPixel <https://gempixel.com> 
	 * @version 1.0
	 */
	private static $session = [];
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
	 * $request variable
	 * @var null
	 */
	private $_HTTPrequest = NULL;	
	/**
	 * HTTP Method
	 * @var null
	 */
	private $_HTTPmethod = NULL;	
	/**
	 * HTTP Param Count
	 * @var integer
	 */
	private $_HTTPcount = 0;
	/**
	 * Acceptable File Uploads
	 * @var array
	 */
	private $_FILEacceptable = ["image/jpg", "image/png", "image/jpeg"];
	/**
	 * List of Common File Types
	 * @var array
	 */
	private $_FILEcommon	= [
								"js" => ["application/javascript"],
								"json" => ["application/json"],
								"xml"  => ["application/xml"],
								"zip"  => ["application/zip", "application/x-zip-compressed"],
								"pdf"  => ["application/pdf"],
								"sql"  => ["application/sql"],
								"doc"  => ["application/msword"],
								"mpeg" => ["audio/mpeg"],
								"mp4" => ["video/mp4"],
								"ogg"  => ["audio/ogg"],
								"css"  => ["text/css"],
								"html" => ["text/html"],
								"xml"  => ["text/xml"],
								"csv"  => ["text/csv"],
								"txt"	=> ["text/plain"],
								"png"  => ["image/png"],
								"jpeg" => ["image/jpeg"],
								"jpg" => ["image/jpeg"],
								"gif"  => ["image/gif"],
								"ico" => ["image/x-icon"],
								"svg" => ['image/svg+xml']
							];
	/**
	 * File Object
	 * @var null
	 */
	private $_HTTPfiles = NULL;

	/**
	 * Response
	 * @var null
	 */
	public $_HTTPPARAMETERS = NULL;

	/**
	 * Capture Requests via HTTP Post or HTTP Get
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 */
	public function __construct(){
		
		
		$this->_HTTPmethod = Helper::clean($_SERVER['REQUEST_METHOD'] ?? 'GET', 3, TRUE);

		if(!in_array($this->_HTTPmethod, ["GET", "POST", "PUT", "DELETE","PATCH"])) return false;

		$this->_HTTPrequest = new \stdClass;

		foreach ($_REQUEST as $key => $value) {
			if($this->_HTTPmethod == "GET"){
				$this->_HTTPrequest->{$key} = Helper::clean($value, 3, TRUE);
			}else{
				$this->_HTTPrequest->{$key} = $value;
			}
			$this->_HTTPcount++;
		}

		$this->catchFile();
	}	

	/**
	 * Output class
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @return  string [description]
	 */
	public function __toString(){
		echo "<pre>";
		print_r($this->_HTTPrequest);
		echo "</pre>";
		return "";
	}
	/**
	 * Get variable magically
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @param   [type] $variable [description]
	 * @return  [type]           [description]
	 */
	public function __get($variable){
		if(!isset($this->_HTTPrequest->{$variable})) return null;
		return $this->_HTTPrequest->{$variable};
	}
	/**
	 * Get variable
	 *
	 * @author GemPixel <https://gempixel.com> 
	 * @version 1.0
	 * @param [type] $variable
	 * @return void
	 */
	public function get($variable){
		if(!isset($this->_HTTPrequest->{$variable})) return null;
		return $this->_HTTPrequest->{$variable};
	}
	/**
	 * Return All Variables
	 *
	 * @author GemPixel <https://gempixel.com> 
	 * @version 1.0
	 * @return void
	 */
	public function all($asArray = false){
		return $asArray ? (array) $this->_HTTPrequest : $this->_HTTPrequest;
	}
	/**
	 * Catch and process file uploads
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0	 
	 */
	private function catchFile(){

		if($_FILES){
			
			$this->_HTTPfiles = new \stdClass;
			foreach ($_FILES as $key => $file) {
				
				if(empty($file["type"]) || empty($file["name"])) continue;
				if(isset($this->_HTTPfiles->{$key})) continue;

				$this->_HTTPfiles->{$key} = new \stdCLass;
				$this->_HTTPfiles->{$key}->allowed = in_array($file["type"], $this->_FILEacceptable) ? true : false;
				$this->_HTTPfiles->{$key}->name = Helper::clean($file["name"]);
				$this->_HTTPfiles->{$key}->ext = Helper::extension($file["name"]);
				$this->_HTTPfiles->{$key}->type = Helper::clean($file["type"]);
				$this->_HTTPfiles->{$key}->location = Helper::clean($file["tmp_name"]);
				$this->_HTTPfiles->{$key}->size = $file["size"];
				$this->_HTTPfiles->{$key}->sizekb = round($file["size"] / 1024, 2);
				$this->_HTTPfiles->{$key}->sizemb = round($this->_HTTPfiles->{$key}->sizekb / 1024, 3);
				$this->_HTTPfiles->{$key}->mimematch = (isset($this->_FILEcommon[Helper::extension($file["name"])]) && in_array($file["type"], $this->_FILEcommon[Helper::extension($file["name"])])) ? true : false;
				$this->_HTTPfiles->{$key}->isvalid = $this->_HTTPfiles->{$key}->mimematch;
			}
		}

	}
	/**
	 * Return File Object
	 * @author GemPixel <https://gempixel.com>
	 * @param  $input Filter an input name
	 * @version 1.0
	 * @return  object File object
	 */
	public function file($input = NULL){

		if(!is_null($input)) {
			
			if(isset($this->_HTTPfiles->{$input})) return $this->_HTTPfiles->{$input};

			return false;
		}

		return $this->_HTTPfiles;
	}
	/**
	 * Move file to another directory
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @param   class $request   [description]
	 * @param   [type]  $directory [description]
	 * @return  [type]             [description]
	 */
	public function move($request, $directory = null, $name = null){
		
		$directory = $directory ?? PUB."/".VIEW::UPLOADS;		
		$filename = $name ?: $request->name;

		if(move_uploaded_file($request->location, $directory.'/'.$filename)){
			return true;
		}
		return false;
	}
	/**
	 * Set allowed types
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @param   array $types
	 */
	public function setAllowedType(array $types) {

		foreach ($types as $type) {
			
			if(!isset($this->_FILEcommon[$type])) continue;

			$this->_FILEacceptable[] = $this->_FILEcommon[$type];
		}

	}
	/**
	 * Type of HTTP Request
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 */
	public function typeof(){
		return strtolower($this->_HTTPmethod);
	}
	/**
	 * Count number of parameters
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 */
	public function count(){
		return $this->_HTTPcount;
	}	
	/**
	 * Check if a method has been posted
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 */
	public function isPost(){
		return $this->typeof() == "post" ? true : false;
	}
	/**
	 * Get Request Body
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 */
	public function getBody(){
		return file_get_contents("php://input");
	}
	/**
	 * Get HTTP Code
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 */
	public function httpCode($code = NULL){

		if(!is_null($code)) return http_response_code($code);

		return http_response_code();
	}
	/**
	 * Get Body JSON
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 */
	public function getJSON(){
		return json_decode($this->getBody());
	}
	/**
	 * Get Server Information
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @param   string $name [description]
	 * @return  [type]       [description]
	 */
	public function server(string $name){
		
		$name = strtoupper($name);

		if(isset($_SERVER[$name])) {
			return Helper::clean($_SERVER[$name], 3);
		}
		return null;
	}
	/**
	 * Get server information as a string
	 * @param string $name
	 * @param string $default
	 */
	public function serverString(string $name, string $default = ''): string {
		$value = $this->server($name);

		return is_scalar($value) ? (string) $value : $default;
	}
	/**
	 * Full URI
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 */
	public function uri($withquery = true){
		$uri = $this->http()."://".$this->server("HTTP_HOST").$this->server("REQUEST_URI");

		return ($withquery == false && $parts = explode("?", $uri)) ? $parts[0] : $uri;
	}
	/**
	 * Get Host
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	public function host(){
		return $this->http()."://".$this->server("HTTP_HOST");
	}
	/**
	 * Grabs referer URI
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 */
	public function referer(){
		return $this->server("HTTP_REFERER") ?? null;
	}
	/**
	 * Return path with or without query
	 *
	 * @author GemPixel <https://gempixel.com> 
	 * @version 1.0
	 * @param boolean $withquery
	 * @return void
	 */
	public function path($withquery = false){
		$path = rawurldecode(trim(rtrim($this->server("REQUEST_URI"), "/"), "/"));
		$base = trim(str_replace('/public/index.php', '', $this->server('PHP_SELF')), '/');
		$path = trim(preg_replace("~$base~", '', $path, 1), '/');		

		return ($withquery == false && $parts = explode("?", $path)) ? $parts[0] : $path;
	}

	/**
	 * Parse and return queries
	 *
	 * @author GemPixel <https://gempixel.com> 
	 * @version 1.0
	 * @param [type] $query
	 * @return void
	 */
	public function query($query = null){
		$path = explode('?', $this->path(true));
		
		if(!is_array($path) || !isset($path[1])) return null;

		parse_str($path[1], $queries);		

		return ($query && isset($queries[$query])) ? $queries[$query] : $queries;
	}
	/**
	 * Get URI Segment
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @param   int    $segment [description]
	 * @return  [type]          [description]
	 */
	public function segment(?int $segment = null){

		$uri = explode("/", $this->path());

		if(is_numeric($segment) && isset($uri[$segment-1])) return $uri[$segment-1];

		return $uri;
	}
	/**
	 * Return HTTP method
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 */
	public function http(){
		return $this->isSecure() ? "https" : "http";
	}
	/**
	 * Is Secure
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.2
	 * @return  boolean [description]
	 */
	public function isSecure(){
		$https = $_SERVER['HTTPS'] ?? null;

		if(!empty($https) && is_scalar($https) && strtolower(trim((string) $https)) !== 'off') return true;
		if((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) return true;
		if(!$this->isTrustedProxyPeer()) return false;

		$schemes = [];

		foreach([$this->forwardedHeaderScheme(), $this->xForwardedProtoScheme()] as $scheme){
			if($scheme !== null) $schemes[] = $scheme;
		}

		if($this->isTrustedCloudflarePeer()){
			$cloudflareScheme = $this->cloudflareVisitorScheme();

			if($cloudflareScheme !== null) $schemes[] = $cloudflareScheme;
		}

		$schemes = array_values(array_unique($schemes));

		return count($schemes) === 1 && $schemes[0] === 'https';
	}

	/**
	 * Check whether the immediate peer is explicitly trusted to set proxy headers.
	 */
	private function isTrustedProxyPeer(): bool {
		$remoteAddress = $this->normalizeIp($_SERVER['REMOTE_ADDR'] ?? null);

		if($remoteAddress === null) return false;
		if($this->ipMatchesAnyCidr($remoteAddress, $this->trustedProxyCidrs())) return true;

		return $this->isTrustedCloudflarePeer($remoteAddress);
	}

	/**
	 * Return the scheme from the nearest RFC 7239 Forwarded element.
	 */
	private function forwardedHeaderScheme(): ?string {
		$header = $_SERVER['HTTP_FORWARDED'] ?? null;

		if(!is_string($header) || trim($header) === '') return null;

		$elements = explode(',', $header);
		$nearestElement = trim((string) end($elements));

		foreach(explode(';', $nearestElement) as $parameter){
			$parts = explode('=', $parameter, 2);

			if(count($parts) !== 2 || strtolower(trim($parts[0])) !== 'proto') continue;

			return $this->normalizeForwardedScheme(trim($parts[1], " \t\n\r\0\x0B\""));
		}

		return null;
	}

	/**
	 * Return the scheme reported by the nearest X-Forwarded-Proto hop.
	 */
	private function xForwardedProtoScheme(): ?string {
		$header = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;

		if(!is_string($header) || trim($header) === '') return null;

		$schemes = explode(',', $header);

		return $this->normalizeForwardedScheme((string) end($schemes));
	}

	/**
	 * Return Cloudflare's visitor scheme only for an opted-in official edge.
	 */
	private function cloudflareVisitorScheme(): ?string {
		$header = $_SERVER['HTTP_CF_VISITOR'] ?? null;

		if(!is_string($header) || trim($header) === '') return null;

		$visitor = json_decode($header);

		return isset($visitor->scheme) && is_string($visitor->scheme)
			? $this->normalizeForwardedScheme($visitor->scheme)
			: null;
	}

	private function normalizeForwardedScheme(string $scheme): ?string {
		$scheme = strtolower(trim($scheme));

		return in_array($scheme, ['http', 'https'], true) ? $scheme : null;
	}
	/**
	 * Is Ajax
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 * @return  boolean [description]
	 */
	public function isAjax(){
		return ($this->server("HTTP_X_REQUESTED_WITH") && strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'xmlhttprequest') == 0) ? true : false;
	}
	/**
	 * Get Requester IP
	 * @author GemPixel <https://gempixel.com>
	 * @version 6.2.1
	 */
	public function ip(){
		$remoteAddress = $this->normalizeIp($_SERVER['REMOTE_ADDR'] ?? null);

		if($remoteAddress === null) return '';

		if($this->trustsCloudflare() && $this->ipMatchesAnyCidr($remoteAddress, self::cloudflareCidrs())){
			if(!isset($_SERVER['HTTP_CF_CONNECTING_IP'])) return $remoteAddress;

			return $this->normalizeIp($_SERVER['HTTP_CF_CONNECTING_IP']) ?? $remoteAddress;
		}

		$trustedProxyCidrs = $this->trustedProxyCidrs();

		if(!$trustedProxyCidrs || !$this->ipMatchesAnyCidr($remoteAddress, $trustedProxyCidrs)) return $remoteAddress;
		if(!isset($_SERVER['HTTP_X_FORWARDED_FOR']) || !is_string($_SERVER['HTTP_X_FORWARDED_FOR'])) return $remoteAddress;

		$forwardedAddresses = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		$validatedAddresses = [];

		foreach($forwardedAddresses as $forwardedAddress){
			$validatedAddress = $this->normalizeIp($forwardedAddress);

			if($validatedAddress === null) return $remoteAddress;

			$validatedAddresses[] = $validatedAddress;
		}

		$currentAddress = $remoteAddress;

		for($index = count($validatedAddresses) - 1; $index >= 0; $index--){
			if(!$this->ipMatchesAnyCidr($currentAddress, $trustedProxyCidrs)) return $currentAddress;

			$currentAddress = $validatedAddresses[$index];
		}

		return $currentAddress;
	}

	/**
	 * Return explicitly configured trusted proxy networks.
	 */
	private function trustedProxyCidrs(): array {
		$configuredCidrs = getenv('TRUSTED_PROXY_CIDRS');

		if(!is_string($configuredCidrs) || trim($configuredCidrs) === '') return [];

		return preg_split('/[\s,]+/', trim($configuredCidrs), -1, PREG_SPLIT_NO_EMPTY) ?: [];
	}

	/**
	 * Cloudflare proxy trust is disabled unless explicitly enabled.
	 */
	private function trustsCloudflare(): bool {
		$value = getenv('TRUST_CLOUDFLARE');

		if(!is_string($value)) return false;

		return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
	}

	/**
	 * Check whether the immediate peer is an explicitly trusted Cloudflare edge.
	 */
	private function isTrustedCloudflarePeer(?string $remoteAddress = null): bool {
		if(!$this->trustsCloudflare()) return false;

		$remoteAddress = $remoteAddress ?? $this->normalizeIp($_SERVER['REMOTE_ADDR'] ?? null);

		return $remoteAddress !== null && $this->ipMatchesAnyCidr($remoteAddress, self::cloudflareCidrs());
	}

	/**
	 * Official Cloudflare edge ranges from https://www.cloudflare.com/ips/.
	 */
	private static function cloudflareCidrs(): array {
		return [
			'173.245.48.0/20',
			'103.21.244.0/22',
			'103.22.200.0/22',
			'103.31.4.0/22',
			'141.101.64.0/18',
			'108.162.192.0/18',
			'190.93.240.0/20',
			'188.114.96.0/20',
			'197.234.240.0/22',
			'198.41.128.0/17',
			'162.158.0.0/15',
			'104.16.0.0/13',
			'104.24.0.0/14',
			'172.64.0.0/13',
			'131.0.72.0/22',
			'2400:cb00::/32',
			'2606:4700::/32',
			'2803:f800::/32',
			'2405:b500::/32',
			'2405:8100::/32',
			'2a06:98c0::/29',
			'2c0f:f248::/32',
		];
	}

	/**
	 * Normalize and validate an IPv4 or IPv6 address.
	 */
	private function normalizeIp(mixed $address): ?string {
		if(!is_string($address)) return null;

		$address = trim($address);

		if(strncasecmp($address, '::ffff:', 7) === 0){
			$mappedAddress = substr($address, 7);

			if(filter_var($mappedAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) return $mappedAddress;
		}

		if(filter_var($address, FILTER_VALIDATE_IP) === false) return null;

		$packedAddress = inet_pton($address);

		return $packedAddress === false ? null : inet_ntop($packedAddress);
	}

	/**
	 * Check whether an address belongs to any valid configured CIDR.
	 */
	private function ipMatchesAnyCidr(string $address, array $cidrs): bool {
		foreach($cidrs as $cidr){
			if($this->ipMatchesCidr($address, $cidr)) return true;
		}

		return false;
	}

	/**
	 * Compare IPv4 and IPv6 addresses using their packed binary form.
	 */
	private function ipMatchesCidr(string $address, mixed $cidr): bool {
		if(!is_string($cidr)) return false;

		$parts = explode('/', trim($cidr), 2);
		$network = $this->normalizeIp($parts[0]);

		if($network === null) return false;

		$packedAddress = inet_pton($address);
		$packedNetwork = inet_pton($network);

		if($packedAddress === false || $packedNetwork === false || strlen($packedAddress) !== strlen($packedNetwork)) return false;

		$totalBits = strlen($packedAddress) * 8;
		$prefixLength = count($parts) === 2 && ctype_digit($parts[1]) ? (int) $parts[1] : $totalBits;

		if($prefixLength < 0 || $prefixLength > $totalBits) return false;

		$fullBytes = intdiv($prefixLength, 8);
		$remainingBits = $prefixLength % 8;

		if($fullBytes > 0 && substr($packedAddress, 0, $fullBytes) !== substr($packedNetwork, 0, $fullBytes)) return false;
		if($remainingBits === 0) return true;

		$mask = (0xff << (8 - $remainingBits)) & 0xff;

		return (ord($packedAddress[$fullBytes]) & $mask) === (ord($packedNetwork[$fullBytes]) & $mask);
	}
	/**
	 * Current User Agent
	 * @author GemPixel <https://gempixel.com>
	 * @version 1.0
	 */
	public function userAgent(){
		return isset($_SERVER["HTTP_USER_AGENT"]) ? Helper::clean($_SERVER["HTTP_USER_AGENT"], 3, TRUE): null;
	}
	
	/**
	 * Detect Device
	 *
	 * @author GemPixel <https://gempixel.com> 
	 * @version 6.0
	 * @return void
	 */
	public function device(){
		$platform =   "Unknown OS";
		$os       =  [
					'/windows nt 11.0/i'    =>  'Windows 11',
					'/windows nt 10.0/i'    =>  'Windows 10',
					'/windows nt 6.3/i'     =>  'Windows 8.1',
					'/windows nt 6.2/i'     =>  'Windows 8',
					'/windows nt 6.1/i'     =>  'Windows 7',
					'/windows nt 6.0/i'     =>  'Windows Vista',
					'/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
					'/windows nt 5.1/i'     =>  'Windows XP',
					'/windows xp/i'         =>  'Windows XP',
					'/windows nt 5.0/i'     =>  'Windows 2000',
					'/windows me/i'         =>  'Windows ME',
					'/win98/i'              =>  'Windows 98',
					'/win95/i'              =>  'Windows 95',
					'/win16/i'              =>  'Windows 3.11',
					'/macintosh|mac os x/i' =>  'Mac OS X',
					'/mac_powerpc/i'        =>  'Mac OS 9',
					'/linux/i'              =>  'Linux',
					'/ubuntu/i'             =>  'Ubuntu',
					'/iphone/i'             =>  'iPhone',
					'/ipod/i'               =>  'iPod',
					'/ipad/i'               =>  'iPad',
					'/android/i'            =>  'Android',
					'/blackberry/i'         =>  'BlackBerry',
					'/bb10/i'         		=>  'BlackBerry',
					'/cros/i'				=>	'Chrome OS',
					'/webos/i'              =>  'Mobile'
				];
		foreach ($os as $regex => $value) { 
			if (preg_match($regex, $this->userAgent())) {
				$platform    =   $value;
			}
		}   
		return $platform;	
	}
	/**
	 * User's browser
	 *
	 * @author GemPixel <https://gempixel.com> 
	 * @version 6.2
	 * @return void
	 */
	public function browser() {
		$matched   = 	false;
		$browser   =   "Unknown Browser";
		$browsers  =   [
						'/safari/i'     =>  'Safari',			
						'/firefox/i'    =>  'Firefox',
						'/fxios/i'    	=>  'Firefox',						
						'/msie/i'       =>  'Internet Explorer',
						'/Trident\/7.0/i'  =>  'Internet Explorer',
						'/chrome/i'     =>  'Chrome',
						'/crios/i'		=>	'Chrome',
						'/opera/i'      =>  'Opera',
						'/opr/i'      	=>  'Opera',
						'/netscape/i'   =>  'Netscape',
						'/maxthon/i'    =>  'Maxthon',
						'/konqueror/i'  =>  'Konqueror',
						'/edg/i'       =>  'Edge',
					];
		
		foreach ($browsers as $regex => $value) { 
			if (preg_match($regex,  $this->userAgent())) {
				$browser  =  $value;
				$matched = true;
			}
		}
		
		if(!$matched && preg_match('/mobile/i', $this->userAgent())){
			$browser = 'Mobile Browser';
		}

		return $browser;
	  } 
	/**
	 * Get geoip
	 *
	 * @author GemPixel <https://gempixel.com> 
	 * @version 1.0
	 * @param [type] $ip
	 * @return void
	 */
	public function country($ip = null){		
		$ip = $ip ?? $this->ip();

		if(appConfig('app.geodriver') == 'api'){

			$url = str_replace('{IP}', $ip, appConfig('app.geopath'));
			$response = Http::url($url)->get()->bodyObject();
			return ['city' => $response->city, 'country' => $response->country_name];
		}

		if(appConfig('app.geodriver') == 'maxmind'){
			try{
				$reader = new \MaxMind\Db\Reader(appConfig('app.geopath'));
				$response = $reader->get($ip);
				$reader->close();
	
				return ['city' => $response['city']['names']['en'] ?? '', 'state' => $response['subdivisions'][0]['names']['en'] ?? '', 'country' => $response['country']['names']['en'] ?? ''];				
			
			} catch(\Exception $e){
				\GemError::log('IP Error: '.$e->getMessage(), ['ip' => $ip]);
				return ['city' => null, 'state' => null, 'country' => null];
			}
		}

		if(appConfig('app.geodriver') == 'custom'){
			return \call_user_func_array(appConfig('app.geopath'), [$ip]);
		}
	}
	/**
   * Read/Write Cookie
   * @author GemPixel <https://gempixel.com>
   * @version 1.0
   * @param   string  $name 
   * @param   string  $value
   * @param   integer $time  in minutes        
   */
	public function cookie($name, $value = "", $time = 1){
		if(empty($value)){
		if(isset($_COOKIE[$name])){
			return Helper::clean($_COOKIE[$name], 3, FALSE);
		}else{
			return FALSE;
		}
		}
		setcookie($name, $value, self::cookieOptions(time()+($time*60), $this->isSecure()));
	}	

	public static function cookieOptions(int $expires, ?bool $secure = null): array {
		if($secure === null){
			$secure = (new self())->isSecure();
		}

		return [
			'expires' => $expires,
			'path' => '/',
			'domain' => '',
			'secure' => $secure,
			'httponly' => true,
			'samesite' => 'Lax',
		];
	}
  /**
   * Read/Write Session
   * @author GemPixel <https://gempixel.com>
   * @version 1.0
   * @param   string $name  [description]
   * @param   mixed $value [description]
   */
	public function session(string $name, $value = ""){
		if(empty($value)){
		if(isset($_SESSION[$name])){
			return Helper::clean($_SESSION[$name], 3, FALSE);
		}else{
			return FALSE;
		}
		}
		$_SESSION[$name] = $value;
		return true;
	}
  /**
   * Unset session
   *
   * @author GemPixel <https://gempixel.com> 
   * @version 1.0
   * @param string $name
   * @return void
   */
	public function unset(string $name){
		if(isset($_SESSION[$name])) {
			unset($_SESSION[$name]);
			return true;
		}
		return false;
	}
   /**
   * Save temporary data
   *
   * @author GemPixel <https://gempixel.com> 
   * @version 1.0
   * @param [type] $name
   * @param [type] $value
   * @return void
   */
	public static function save($name, $value){
		$name = "TEMP_".$name;
		$_SESSION[$name] = $value;
		self::$session[] = $name;
	}
  /**
   * Clear temporary data
   *
   * @author GemPixel <https://gempixel.com> 
   * @version 1.0
   * @return void
   */
	public static function clear(){
		foreach (self::$session as $session) {
		unset($_SESSION[$session]);
		}
	}
 	/**
 	 * Validate Input
 	 * @author GemPixel <https://gempixel.com>
 	 * @version 1.0
 	 * @param   string $input [description]
 	 * @param   mixed $rule  [description]
 	 * @return  [type]        [description]
 	 */
	public function validate(?string $input, $rule = null){

		if(!$input || empty($input)) return false;

		if($rule && is_numeric($rule)) {
			if(strlen($input) < $rule) return false;
		}

		if($rule && $rule == "email") {
			if(Helper::Email($input) === false) return false;
		}

		if($rule && $rule == "url") {
			if(Helper::isURL($input) === false) return false;
		}

		if($rule && $rule == "username") {
			if(Helper::Username($input) === false) return false;
		}

		if($rule && $rule == "int"){
			if(!is_numeric($input)) return false;
		}
		return true;
	} 
}
