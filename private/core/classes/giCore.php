<?php

class giCore {
	
	// store the current configuration
	protected $Configuration;
	protected $Environment;
	protected $Controller;
	protected $Includes;
	protected $Version;
	protected $Cli;
	
	// accessible from giController
	public $Logger;
	public $Response;
	public $Router;
	public $Database;
	public $Debug;
	public $Localization;
	public $Security;
	
	// main constructor from where everything starts
	public function __construct() {
		
		$this->Version = '2.1.0-alpha';
		$this->Configuration = array();
		$this->Include = array();
		$this->Cli = false;
		
		$this->initTime();
		$this->initPHP();
		$this->initDependencies();
		$this->initEnvironment();
		$this->initConfiguration();

		$this->Logger			= new giLogger();						// logging tool
		$this->Response 		= new giResponse();						// output formatting tool
		$this->Router			= new giRouter();						// handles routing of requests
		$this->Database			= new giDatabase(); 					// database abstraction layer
		$this->Localization		= new giLocalization(); 				// help translating
		$this->Security			= new giSecurity(); 					// security handler
		
		// configure the router
		$this->Router->setConfiguration(
			$this->Configuration['response']['enable_cache'],
			$this->Configuration['routing']['not_found_url']
		);
		
		// configure the database
		$this->Database->setConfiguration(
			$this->Configuration['database']['driver'],
			$this->Configuration['database']['database'],
			$this->Configuration['database']['username'],
			$this->Configuration['database']['password'],
			$this->Configuration['database']['hostname'],
			$this->Configuration['memcache']['enable'],
			$this->Configuration['memcache']['prefix'],
			$this->Configuration['memcache']['hostname'],
			$this->Configuration['memcache']['port']
		);
		
		// configure the security
		$this->Security->setConfiguration(
			$this->Configuration['routing']['home_url'],
			$this->Configuration['routing']['login_url'],
			$this->Configuration['routing']['logout_url']
		);
		
		// configure the localization
		$this->Localization->setConfiguration(
			$this->Configuration['localization']['available_languages'],
			$this->Configuration['localization']['default_language'],
			$this->Configuration['localization']['default_locales']
		);
		
		// configure the response
		$this->Response->setConfiguration(
			$this->Configuration['response'],
			$this->Configuration['assets'],
			$this->Configuration['meta_tags'],
			$this->Configuration['start_time'],
			$this->Environment
		);

		// if the environment is prod and a global cache file exists
		if($this->Environment == 'prod' and file_exists('../private/data/cache/includes/core.json')) {
			
			// grab the cached list of includes
			$this->Includes['cached'] = json_decode(file_get_contents('../private/data/cache/includes/core.json'),true);
			
		}
		// if the cache is available
		if(isset($this->Includes['cached'])) {
			// for each file to include
			foreach($this->Includes['cached']['includes']['plugins'] as $anInclude) {
				// include the class
				include($anInclude);	
			}
			// for each configuration file to include
			foreach($this->Includes['cached']['includes']['plugins_configurations'] as $aPluginConfiguration) {
				// include the configuration file
				include($aPluginConfiguration['path']);	
			}
		}
		// no cache file is available for includes
		else {
			// load all plugins
			foreach(scandir('../private/plugins/') as $aPlugin['name']) {
				// if the folder is an actual plugin
				if(substr($aPlugin['name'],0,1) != '.') {
					// set the plugin runtime variable
					$aPlugin['runtime']				= array();
					// set the plugin locales path
					$aPlugin['locales']				= '../private/plugins/' . $aPlugin['name'] . '/locales/';
					// set the plugins configuration file path
					$aPlugin['config']				= '../private/plugins/' . $aPlugin['name'] . '/init/config.php';
					// set the plugins initializator file path
					$aPlugin['routes']				= '../private/plugins/' . $aPlugin['name'] . '/init/routes.php';
					// try to add locales
					if(is_dir($aPlugin['locales'])) {
						// iterate on plugins libraries
						foreach(scandir($aPlugin['locales']) as $aPlugin['potential_locale'] ) {
							// if the filename matches a pattern
							if(strpos($aPlugin['potential_locale'] ,'.csv') and substr($aPlugin['potential_locale'] ,0,1) != '.') {
								// set the include
								$aPlugin['a_locale'] = $aPlugin['locales'].$aPlugin['potential_locale'] ;
								// push the include
								$this->Includes['includes']['locales'][] = $aPlugin['a_locale'];
								// include the actual library
								$this->Localization->setLocales($aPlugin['a_locale']);
							}
						}
					}
					// try to include the configuration file
					if(file_exists($aPlugin['config'])) {
						// push the include
						$this->Includes['includes']['plugins_configurations'][] = array(
							'name'	=>$aPlugin['name'],
							'path'	=>$aPlugin['config']
						);
						// include
						include($aPlugin['config']);
					}
					// set the plugins runtime environnement
					$this->Runtime[$aPluginConfiguration['name']] = $aPlugin['runtime'];
					// try to include the initializator file
					if(file_exists($aPlugin['routes'])) {
						// push the include
						$this->Includes['includes']['plugins'][] = $aPlugin['routes'];
						// include
						include($aPlugin['routes']);
					}
				// unset the temporary aPlugin variable
				unset($aPlugin);
				}
			}
		}
		// if the includes do not come from the cache
		if(!isset($this->Includes['cached'])) {
			// cache the includes
			file_put_contents('../private/data/cache/includes/core.json',json_encode($this->Includes));
		}
	}
	
	// actually route and execute the request
	public function run() {
		// start caching the output
		ob_start();
		// once everything is ready we dispatch the request to the proper controller/view/response
		$this->Router->dispatch();
		// secure the request
		$this->secure();
		// include the proper script in its own environment
		$this->sandbox();
		// if no content has been set by controller/view
		if(!$this->Response->getContent()) {
			// use gargabe collection to set the content
			$this->Response->setContent(ob_get_clean());
		}
		// if the controller didn't already handle its own output, ask the giResponse to format it
		$this->Response->output(); 
	}
	
	// apply security rules
	private function secure() {
		// if a level is specified
		if($this->Router->Level !== null) {
			// enforce security
			$this->Security->enforce($this->Router->Level,$this->Router->Module);
		}
	}
	
	// inclusion happens here
	private function sandbox() {
		// include the controller
		require($this->Router->Script);
		// instanciate the class associated to the controller
		$this->Controller = new $this->Router->Class($this);
		// if method doesn't exist
		if(!method_exists($this->Controller,$this->Router->Function)) {
			// if a default action doesn't exist ether
			if(!method_exists($this->Controller,'defaultAction')) {
				// throw an exception
				Throw new Exception("giCore->sandbox() : Method [{$this->Router->Class}/{$this->Router->Function}] does not exist");
			}
			// default action exists
			else {
				// set the default action
				$this->Router->Function = 'defaultAction';
			}
		}
		// execute the preAction
		$this->Controller->preAction();
		// execute the routed method indexAction is no :action pr $_POST['action'] provided
		$this->Controller->{$this->Router->Function}();
		// execute the postAction
		$this->Controller->postAction();
	}
	
	// start the execution time
	private function initTime() {
		// set the time
		$this->Configuration['start_time'] = microtime(true);
	}
	
	// initialize PHP parameters
	private function initPHP() {

		// set the timezone
		date_default_timezone_set('Europe/Paris');
		// if using FPM (fix the missing headers bug) or older version of PHP
		if(!function_exists('getallheaders')) { 
			// declare the getallheaders
			function getallheaders() { 
				// store headers
				$headers = '';
				// for each $_server key
				foreach ($_SERVER as $name => $value) { 
					// if it's a header
					if (substr($name, 0, 5) == 'HTTP_') { 
						// clean it
						$headers[str_replace(' ','-',ucwords(strtolower(str_replace('_',' ',substr($name, 5)))))] = $value; 
					} 
				} 
				// return found headers
				return($headers); 
			} 
		}
		
	}
	
	// include dependencies
	private function initDependencies() {
		// include core classes
		foreach(scandir('../private/core/classes/') as $aClassFile) {
			// if we find the proper extension
			if(strpos($aClassFile,'.php') and substr($aClassFile,0,1) != '.' and $aClassFile != 'giCore.php') {
				// set the file to include
				$anInclude = '../private/core/classes/' . $aClassFile;
				// push the include
				$this->Includes['core'][] = $anInclude;
				// include the file
				include($anInclude);
			}
		}
	}
	
	// get the configuration parameters
	private function initConfiguration() {
		// load the main configuration
		$this->Configuration += parse_ini_file('../private/core/configuration/common.ini',true);
		// load the environment specific configuration
		$this->Configuration += parse_ini_file('../private/core/configuration/'.$this->Environment.'.ini',true);
	}
	
	// detect environment of execution
	private function initEnvironment() {
	
		// if command line argument are present
		if(count($_SERVER['argv']) > 0 and $_SERVER['argv'][1] and $_SERVER['argv'][2] and ($_SERVER['argv'][1] == 'local' or $_SERVER['argv'][1] == 'prod')) {
			// set as command line
			$this->Cli = true;
			// set the current environment
			$this->Environment = $_SERVER['argv'][1];
			// set the last parameter as being the URL
			$_SERVER['REQUEST_URI'] = $_SERVER['argv'][2];
		}
		// not command line
		else {
			// set as command line
			$this->Cli = false;
			// default would be prod
			$this->Environment = 'prod';
			// if the remote ip is a local one
			if(strpos($_SERVER['REMOTE_ADDR'],'192.168.') !== false) {
				// set as local
				$this->Environment = 'local';
			}
			// if the remote ip is a loopback
			elseif(strpos($_SERVER['REMOTE_ADDR'],'127.0.0.') !== false) {
				// set as local
				$this->Environment = 'local';
			}
			elseif(strpos($_SERVER['REMOTE_ADDR'],'10.10.10.') !== false) {
				// set as local
				$this->Environment = 'local';	
			}
			// if the domain contain localhost
			elseif(strpos($_SERVER['HTTP_HOST'],'localhost') !== false) {
				// set as local
				$this->Environment = 'local';
			}
		}
	}
	
	// allow to get the current environment
	public function getEnvironment() {
		// return current environment
		return($this->Environment);
	}
	
	// allow to get the configuration
	public function getConfiguration() {
		// return the whole configuration
		return($this->Configuration);	
	}
	
}

?>