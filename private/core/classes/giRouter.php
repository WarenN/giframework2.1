<?php

class giRouter {
		
	protected $Request;
	protected $Method;
	protected $Headers;
	protected $Parameters;
	protected $Routes;
	protected $Cache;
	protected $Cli;
	static protected $Signature;
		
	public function __construct() {
		
		// check for cached request
		$this->checkCache();
		
		// set request informations
		$this->Request 			= (string)	$_SERVER['REQUEST_URI'];
		$this->Method			= (string)	$_SERVER['HTTP_METHOD'];
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
			'controller'		=>$controller,
			'parameters'		=>$parameters,
			'security_level'	=>$level,
			'security_right'	=>$right,
		
		);
		
	}
	
	public function runtime($data,$controller) {
		
	}
	
	// dispatch Request to the proper controller
	public function dispatch() {
		
		
		
		$this->parseRequest();
		
		
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