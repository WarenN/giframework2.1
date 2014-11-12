<?php


class giRequest {
	
	protected $urlMapping;
	protected $rawRequest;
	protected $parsedRequest;
	protected $requestedScript;
	protected $requestParameters;
	protected $requestAllParameters;
	protected $requestHandler;
	protected $requestSignature;
	protected $requestHeaders;
	protected $debugStatus;
	protected $isCommandLine;
	
	public function __construct() {
		$this->checkCache();					// check is the request isn't available as cached output
		$this->isCommandLine		= false;	// not running as command line by default
		$this->requestParameters	= array();	// request parameters are empty by default
		$this->requestHeaders		= array();	// default is no request headers
		$this->rawRequest 			= (string)	$_SERVER['REQUEST_URI'];	// grab the raw request
		$this->patchCommandLine(); 				// if in command line, this will overide the raw request
		$this->setHeaders();					// get request headers
	}

	// get the signature of this request (for caching purpose)
	public function getSignature() {
		
		// if no signature is available
		if(!$this->requestSignature) {
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
			return($this->requestSignature);
		}
	}

	// check for the presence of a cached file
	private function checkCache() {
		
		// access configuration
		global $giConfiguration;
		
		// if we are not running in production
		if(!$giConfiguration->isCachingEnabled()) {
		
			// stop here
			return;
			
		}
		
		// generate a signature of this request
		$this->requestSignature = $this->getSignature();
		
		// check for the presence of a file
		if(file_exists('../private/data/cache/output/'.$this->requestSignature.'.raw')) {
			
			// if the cached file is still valid
			if(filemtime('../private/data/cache/output/'.$this->requestSignature.'.raw') > time()) {
				// for each cached headers
				foreach(json_decode(file_get_contents('../private/data/cache/output/'.$this->requestSignature.'.json'),true) as $aHeaderKey => $aHeaderValue) {
				
					// output the header
					header("{$aHeaderKey}: {$aHeaderValue}");
					
				}
				
				// output the cached file and die
				die(file_get_contents('../private/data/cache/output/'.$this->requestSignature.'.raw'));
				
			}
			// the file is not valid anymore
			else {
				// remove the cached files
				unlink('../private/data/cache/output/'.$this->requestSignature.'.raw');
				unlink('../private/data/cache/output/'.$this->requestSignature.'.json');
			}	
		}
	}

	// map an url to a handler
	public function setHandler($plugin,$url,$handler,$is_dynamic=false) {
		$this->urlMapping[]	= array(
			'plugin'		=> (string)		$plugin,
			'url'			=> (string)		$url,
			'handler'		=> (string)		$handler,
			'is_dynamic'	=> (boolean)	$is_dynamic
		);
	}
	
	// get the handler path
	public function getHandler() {

		// if the handler doesn't exist
		if(!file_exists($this->requestHandler)) {
			// access logger
			global $giLogger;
			// log this
			$giLogger->error('missing handler '.$this->requestHandler);
			// access giOutput
			global $giOutput;
			// output an error
			$giOutput->error500('giRequest: missing_handler');
		}
		return($this->requestHandler);	
	}

	// get parameters after the static part of an url
	public function getParameters() {
		return($this->requestParameters);
	}
	
	// get parameters, every fucking one of them, including static path
	public function getAllParameters() {
		return($this->requestAllParameters);
	}

	// get the raw request
	public function getRawRequest() {
		return($this->rawRequest);	
	}
	
	// check if we are in debug mode
	public function isDebugEnabled() {
		return($this->debugStatus);	
	}	

	// this is called so that URL/ARGV is parsed and a proper handler is found
	public function processRequest() {
		$this->parseRequest();
		$this->deductHandler();
	}

	// this will retrieve all the request headers (only working on PHP => 5.4)
	private function setHeaders() {
		
		// retrieve the headers
		$availableHeaders = getallheaders();
		
		// if they are found
		if(is_array($availableHeaders)) {
			
			// we push them in ourselves
			$this->requestHeaders = $availableHeaders;
			
		}
		
	}
	
	// check if the browser accepts compressed (gzip) content
	public function isCompressionEnabled() {
		
		// if header are set
		if($this->requestHeaders) {
			// if the encoding is set
			if($this->requestHeaders['Accept-Encoding']) {
				// if we find a match
				if(
					stripos($this->requestHeaders['Accept-Encoding'],'gzip') !== false or 
					stripos($this->requestHeaders['Accept-Encoding'],'deflate') !== false
				) {
					// browser is gladly accepting compression
					return(true);	
				}	
			}
		}
		// default is not to accept compression
		return(false);
		
	}
	
	// get the available headers
	public function getHeaders() {
		// return the raw array
		return($this->requestHeaders);
	}

	// patch the command line if running that way
	private function patchCommandLine() {
	
		// if command line argument are present
		if(count($_SERVER['argv']) > 0) {
			// set as command line
			$this->isCommandLine = true;
			// patch them as url
			$this->rawRequest = $_SERVER['argv'][2];
			// access the configuration handler
			global $giConfiguration;
			// set him what environment it is
			$giConfiguration->setEnvironment($_SERVER['argv'][1]);
		}
		
	}
	
	// parse the request to make it understandable
	private function parseRequest() {
		// if we find the debug symbol
		if(strpos($this->rawRequest,'@@') or isset($_POST['giDebug'])) {
			// update the debug status
			$this->debugStatus	= true;
			// clean url
			$this->rawRequest	= str_replace('@@','',$this->rawRequest);
		}
		else {
			// update the debug status
			$this->debugStatus	= false;	
		}
		// remove question mark if not using rewriting
		$this->parsedRequest	= (string)	str_replace('?','',$this->rawRequest);
		// remove first slash
		$this->parsedRequest	= (string)	substr($this->parsedRequest,1);
		// if the url is empty or just a slash
		if($this->parsedRequest == '' or $this->parsedRequest == '/') {
			// set the requested url as /
			$this->requestedScript	= (string)	'/';	
		}
		// we've got ourselves some neat url
		else {
			// put it after a slash
			$this->requestedScript	= (string)	'/'.$this->parsedRequest;
		}
	}

	// find the proper handler according to the url
	private function deductHandler() {

		// iterate on the list to find the proper page
		foreach($this->urlMapping as $aPage) {
			if($aPage['url'] == $this->requestedScript) {
				// parse the parameters
				$this->parseParameters($aPage['url']);
				// we found the requested page in the sitemap so we set the proper handler
				$this->requestHandler = '../private/plugins/'.$aPage['plugin'].'/handlers/'.$aPage['handler'].'.php';	
				// this is it
				return;
			}
			// if the page is a multiple parameter handler
			elseif($aPage['is_dynamic'] and strpos($this->requestedScript,$aPage['url']) === 0) {
				// parse the parameters
				$this->parseParameters($aPage['url']);
				// set the handler
				$this->requestHandler = '../private/plugins/'.$aPage['plugin'].'/handlers/'.$aPage['handler'].'.php';	
				// this is it
				return;
			}
		}
		// we did not find the page in the sitemap 
		if(!$this->requestHandler) {
			// access the config
			global $giConfiguration;
			// clean the buffer
			ob_get_clean();
			// output a 404 header
			header('HTTP/1.1 404 Not Found');
			// if even the 404 handler is not found !
			if($this->rawRequest == $giConfiguration->get404Page()) {
				// stop completely
				global $giLogger;
				// log this
				$giLogger->error('missing 404 handler '.$giConfiguration->get404Page());
				// access giOutput
				global $giOutput;
				// output an error
				$giOutput->error500('giRequest: missing_404_handler');
			}
			// set the 404 url as a request
			$this->rawRequest = $giConfiguration->get404Page();
			// re-process the request
			$this->processRequest();
			// stop here
			return;
		}
		
	}
	
	// parse the url parameters so that we retrieve them as array later on
	private function parseParameters($staticPartOfThePage) {
		// get and set all parameters
		$this->requestAllParameters	= (array)	explode('/',substr($this->requestedScript,1));
		// remove static part
		$requestParameters			= (string)	str_replace($staticPartOfThePage,'',$this->requestedScript);
		// get and set dynamic parameters
		$this->requestParameters	= (array)	explode('/',$requestParameters);
		// the requested script being the url without dynamic parameters
		$this->requestedScript		= (string)	$staticPartOfThePage;
	}

}

?>