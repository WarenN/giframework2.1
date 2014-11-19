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
	public $Plugin;
	public $Controller;
	public $Script;
	public $Class;
	public $Action;
	public $Function;
	public $Options;
	public $Parameters;
	
	// specific to the current request
	static protected $Compression;
	static protected $Signature;
		
	public function __construct() {
		
		// set request informations
		$this->Request 			= (string)	$_SERVER['REQUEST_URI'];
		$this->Debug			= false;
		$this->Compression		= false;
		$this->Method			= null;
		$this->Options			= null;
		$this->Plugin			= null;
		$this->Action			= null;
		$this->Function			= null;
		$this->Controller		= null;
		$this->Script			= null;
		$this->Class			= null;
		$this->Headers			= array();
		$this->Routes			= array();
		$this->Parameters		= new stdClass();
		
	}
	
	// set the configuration
	public function setConfiguration($cache,$missing) {
		
		// if we enable cache
		$this->Cache = (boolean) $cache;
		
		// set the missing page url
		$this->Missing = $missing;
		
		// check for cached request
		$this->checkCache();
		
	}
	
	// register a route
	public function route($url,$controller,$level=null,$module=null) {
		
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
			'security_module'	=>$module,
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
		// check script
		$this->checkScript();
		// check method
		$this->checkMethod();
		// check security
		$this->checkSecurity();
		// check parameters
		$this->checkParameters();
		// check action
		$this->checkAction();

	}
	
	// analyse the url
	private function checkURL() {	
	
		// if we find the debug symbol
		if(strpos($this->Request,'@@') !== false) {
			// update the debug status
			$this->Debug	= true;
			// clean url
			$this->Request	= str_replace('@@','',$this->Request);
		}
		// if no request URI at all
		if(!$this->Request) {
			// use root request
			$this->Request = '/';
			
		}
		
	}
	
	// check witch controller is associated with current URL
	private function checkController() {
		
		// iterate on the list to find the proper page
		foreach($this->Routes as $aRoute => $aRouteOptions) {
			// if the request matchs a whole route
			if($aRoute == $this->Request) {
				// set options
				$this->Options = $aRouteOptions;
				// get plugin and controller
				list($this->Plugin,$this->Controller) = explode('/',$aRouteOptions['mapto']);
				// set the class name
				$this->Class = str_replace(' ','',ucwords(strtolower(str_replace('-',' ',$this->Controller))).'Controller');
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
				// set the class name
				$this->Class = str_replace(' ','',ucwords(strtolower(str_replace('-',' ',$this->Controller))).'Controller');
				// we found the requested page in the sitemap so we set the proper handler
				$this->Script = '../private/plugins/'.$this->Plugin.'/controllers/'.$this->Controller.'.php';
				// this is it
				return;
			}
		}
		
	}
	
	// check if the script has been found or actually exists
	private function checkScript() {
	
		// we did not find the page in the sitemap 
		if(!$this->Script) {
			// clean the buffer
			ob_get_clean();
			// output a 404 header
			header('HTTP/1.1 404 Not Found');
			// set the url as 404
			$this->Request = $this->Missing;
			// retry
			$this->checkController();
			// if the script doesn't exist
			if(!file_exists($this->Script)) {
				// exception
				Throw new Exception('giRouter->checkScript() : missing controller script ['.$this->Script.']');
			}
			// stop here
			return;
		}
		// if the script doesn't exist
		elseif(!file_exists($this->Script)) {
			// exception
			Throw new Exception('giRouter->checkScript() : missing controller script ['.$this->Script.']');
		}
		
	}
	
	private function checkMethod() {
		
		
	}
	
	private function checkSecurity() {
	
		// set the level
		$this->Level = $this->Options['security_level'];
			
		// set the module
		$this->Module = $this->Options['security_module'];
		
	}
	
	private function checkParameters() {
		
		// if no parameter were specified at routing time
		if(!$this->Options['parameters']) {
			// if something has been posted
			if($_POST and count($_POST)) {
				// for each posted key
				foreach($_POST as $aParameterName => $aParameterValue) {
					// set the parameter
					$this->Parameters->$aParameterName = $aParameterValue;
				}
			}
			// nothing special to do about it
			return;
		}
		// we have url parameters to handle
		else {
			// remove static portion of the url
			$temporary_url = str_replace($this->Options['parameters'][0],'',$this->Request);
			// explode the url parameters
			$request_parameters = explode('/',$temporary_url);
			// remove static portion from parameters keys
			unset($this->Options['parameters'][0]);
			// for each parameter key
			foreach($this->Options['parameters'] as $aParameterPosition => $aParameterKey) {
				// parameter clean name
				$parameter_name = ucfirst(trim($aParameterKey,'/'));
				// if said parameter has a value
				if(strlen($request_parameters[$aParameterPosition-1]) > 0) {
					// set the parameter
					$this->Parameters->$parameter_name = $request_parameters[$aParameterPosition-1];
				} 
				// parameter has no value or is missing
				else {
					// set as null
					$this->Parameters->$parameter_name = null;
				}
			}
		}		
	}
	
	private function checkAction() {
		
		// if an action parameter exists
		if($this->Parameters->Action) {
			// set the name of the associated method
			$this->Function = $this->Parameters->Action.'Action';
		}
		// else no action parameter is given we fallback to indexAction
		else {
			// indexAction is the default for all controller
			$this->Function = 'indexAction';	
		}
		
	}
	
	private function checkCache() {
	
		// if we are not running in production
		if(!$this->Cache) {
			// stop here
			return;
		}
		// check for the presence of a file
		if(file_exists('../private/data/cache/response/'.self::getSignature().'.raw')) {
			// if the cached file is still valid
			if(filemtime('../private/data/cache/response/'.self::getSignature().'.raw') > time()) {
				// for each cached headers
				foreach(json_decode(file_get_contents('../private/data/cache/response/'.self::getSignature().'.json'),true) as $aHeaderKey => $aHeaderValue) {
					// output the header
					header("{$aHeaderKey}: {$aHeaderValue}");
				}
				// output the cached file and die
				die(file_get_contents('../private/data/cache/response/'.self::getSignature().'.raw'));	
			}
			// the file is not valid anymore
			else {
				// remove the cached files
				unlink('../private/data/cache/response/'.self::getSignature().'.raw');
				unlink('../private/data/cache/response/'.self::getSignature().'.json');
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
		if(!self::$Signature) {
			// if post has data
			if(count($_POST) > 0) {
				// generate a signature including post data
				return(substr(sha1($_SERVER['REQUEST_URI'].var_export($_POST,true)),0,16));
			}
			// post has no data
			else {
				// generate the signature
				return(substr(sha1($_SERVER['REQUEST_URI']),0,16));
			}
		}
		// a signature is already available
		else {
			// return the already generated signature
			return(self::$Signature);
		}
		
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

?>