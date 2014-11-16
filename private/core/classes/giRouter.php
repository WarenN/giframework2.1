<?php

class giRouter {
		
	protected $Request;
	protected $Method;
	protected $Headers;
	protected $Parameters;
	protected $Signature;
	protected $Routes;
	protected $Cli;
		
	public function __construct() {
		
		$this->Request 			= (string)	$_SERVER['REQUEST_URI'];
		$this->Method			= (string)	$_SERVER['HTTP_METHOD'];
		$this->Headers			= array();
		$this->Parameters		= array();
		$this->Routes			= array();
		$this->Cli				= false;
		
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
	
	// dispatch Request to the proper controller
	public function dispatch() {
		
		
		
	}
	
	// get all Parameters
	public function getParameters() {
		
	}
	
	// allows you to get a specific parameter
	public function get($parameter) {
		
	}
	
	// check for cli execution
	private function cli() {
		
		// if command line argument are present
		if(count($_SERVER['argv']) > 0 and $_SERVER['argv'][1] and $_SERVER['argv'][2]) {
			// set as command line
			$this->cli = true;
			// patch them as url
			$this->Request = $_SERVER['argv'][2];
			// access the configuration handler
			global $giConfiguration;
			// set him what environment it is
			$giConfiguration->setEnvironment($_SERVER['argv'][1]);
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