<?php

class giRouter {
		
	// specific to the whole router
	protected $Routes;
	
	// specific to the current request
	protected $Request;
	protected $Method;
	protected $Headers;
	protected $Cache;
	protected $Debug;
	protected $Cli;
	
	// specific to the current route
	protected $Plugin;
	protected $Controller;
	protected $Script;
	protected $Class;
	protected $Action;
	protected $Options;
	protected $Parameters;
	
	// specific to the current request
	static protected $Compression;
	static protected $Signature;
		
	public function __construct() {
		
		// check for cached request
		$this->checkCache();
		
		// set request informations
		$this->Request 			= (string)	$_SERVER['REQUEST_URI'];
		$this->Debug			= false;
		$this->Compression		= false;
		$this->Method			= null;
		$this->Options			= null;
		$this->Plugin			= null;
		$this->Action			= null;
		$this->Controller		= null;
		$this->Script			= null;
		$this->Class			= null;
		$this->Headers			= array();
		$this->Parameters		= array();
		$this->Routes			= array();
		
	}
	
	// set the configuration
	public function setConfiguration($cache=false) {
		
		// set we enable cache
		$this->Cache = (boolean) $cache;
		
	}
	
	// register a route
	public function route($url,$controller,$level=null,$right=null) {
		
		// if missing plugin/controller separator (this codeblock can be commented for performance)
		if(substr_count($controller,'/') != 1) {
			// exeception
			Throw new Exception('giRouter->route() : Missing "/" between plugin and controller name');	
		}
		
		// if options are found is the url
		if(substr_count($url,':') > 0) {
			// set only the static part as a match
			$exploded_url = explode(':',$url);
			// get static part of the url
			$route_static = $exploded_url[0];
			// $Parameters
			$parameters = $exploded_url;
		}
		// page is static 
		else {
			// keep as is
			$route_static = $url;
			// no Parameters
			$parameters = null;
		}
		// add to the routing array
		$this->Routes[$route_static] = array(
			'url'				=>$route_static,
			'mapto'				=>$controller,
			'parameters'		=>$parameters,
			'security_level'	=>$level,
			'security_right'	=>$right,
		);
		
	}
	
	public function runtime($data,$controller) {
		
	}
	
	// dispatch Request to the proper controller
	public function dispatch() {
		
		// check for request headers
		$this->checkHeaders();
		// check for compression
		$this->checkCompression();
		// check url
		$this->checkURL();
		// check controller
		$this->checkController();
		// check method
		$this->checkMethod();
		// check parameters
		$this->checkParameters();
		// return useful informations to giCore/main
		return(array(
			$this->Script,
			$this->Class
		));

	}
	
	// analyse the url
	private function checkURL() {	
	
		// if we find the debug symbol
		if(strpos($this->Request,'@@') !== false) {
			// update the debug status
			$this->Debug	= true;
			// clean url
			$this->Request	= str_replace('@@','',$this->rawRequest);
		}
		// if no request URI at all
		if(!$this->Request) {
			// use root request
			$this->Request = '/';
			
		}
		
	}
	
	// check with controller is associated with 
	private function checkController() {
		
		// iterate on the list to find the proper page
		foreach($this->Routes as $aRoute => $aRouteOptions) {
			// if the request matchs a whole route
			if($aRoute == $this->Request) {
				// set options
				$this->Options = $aRouteOptions;
				// get plugin and controller
				list($this->Plugin,$this->Controller) = explode('/',$aRouteOptions['mapto']);
				// we found the requested page in the sitemap so we set the proper handler
				$this->Script = '../private/plugins/'.$this->Plugin.'/controllers/'.$this->Controller.'.php';
				// this is it
				return;
			}
			// if request matches the begening of a route which is dynamic
			elseif($aRouteOptions['parameters'] and strpos($this->Request,$aRoute) === 0) {
				// set options
				$this->Options = $aRouteOptions;
				// get plugin and controller
				list($this->Plugin,$this->Controller) = explode('/',$aRouteOptions['mapto']);
				// we found the requested page in the sitemap so we set the proper handler
				$this->Script = '../private/plugins/'.$this->Plugin.'/controllers/'.$this->Controller.'.php';
				// this is it
				return;
			}
		}
	}
	
	private function checkMethod() {
		
		
	}
	
	private function checkParameters() {
		
		
	}
	
	
	private function checkCache() {
	
		// if we are not running in production
		if(!$this->Cache) {
			// stop here
			return;
		}
		// check for the presence of a file
		if(file_exists('../private/data/cache/output/'.giRoute::getSignature().'.raw')) {
			// if the cached file is still valid
			if(filemtime('../private/data/cache/output/'.giRoute::getSignature().'.raw') > time()) {
				// for each cached headers
				foreach(json_decode(file_get_contents('../private/data/cache/output/'.giRoute::getSignature().'.json'),true) as $aHeaderKey => $aHeaderValue) {
					// output the header
					header("{$aHeaderKey}: {$aHeaderValue}");
				}
				// output the cached file and die
				die(file_get_contents('../private/data/cache/output/'.giRoute::getSignature().'.raw'));	
			}
			// the file is not valid anymore
			else {
				// remove the cached files
				unlink('../private/data/cache/output/'.giRoute::getSignature().'.raw');
				unlink('../private/data/cache/output/'.giRoute::getSignature().'.json');
			}	
		}
		
	}
	
	// get all Parameters
	public function getParameters() {
		
	}
	
	// allows you to get a specific parameter
	public function get($parameter) {
		
	}

	// get headers from the browser
	private function checkHeaders() {
		
		// retrieve the headers
		$availableHeaders = getallheaders();
		// if they are found
		if(is_array($availableHeaders)) {
			// we push them in place
			$this->Headers = $availableHeaders;
		}
		// no headers found
		else {
			// set empty array
			$this->Headers = array();	
		}
		
	}

	// get the unique signature of a request
	public static function getSignature() {
	
		// if no signature is available
		if(!$this->Signature) {
			// if post has data
			if(count($_POST) > 0) {
				// generate a signature including post data
				return(substr(sha1($this->rawRequest.var_export($_POST,true)),0,16));
			}
			// post has no data
			else {
				// generate the signature
				return(substr(sha1($this->rawRequest),0,16));
			}
		}
		// a signature is already available
		else {
			// return the already generated signature
			return($this->Signature);
		}
		
	}
	
	// if debug tag is present in the url
	public static function hasDebug() {
	
		return(false);
		
	}
	
	// check if the browser accepts compressed (gzip) content
	private function checkCompression() {
		// if header are set
		if($this->Headers) {
			// if the encoding is set
			if($this->Headers['Accept-Encoding']) {
				// if we find a match
				if(
					stripos($this->Headers['Accept-Encoding'],'gzip') !== false or 
					stripos($this->Headers['Accept-Encoding'],'deflate') !== false
				) {
					// browser is gladly accepting compression
					$this->Compression = true;	
				}
			}
		}
	}
	
}


/*

class based on plugin name

PluginAction

then 
->defaultAction()

or if $_POST['action'] -> createAction() // for example
or if :action -> creationAction // for example


*/

?>