<?php

class giCore {
	
	// store the current configuration
	protected $Configuration;
	protected $Environment;
	protected $Includes;
	protected $Runtimes;
	protected $Controller;
	
	public $Logger;
	public $Response;
	public $Router;
	public $Database;
	public $Debug;
	public $Localization;
	public $Security;
	
	// main constructor from where everything starts
	public function __construct() {
		
		$this->Configuration = array();
		$this->Include = array();
		
		$this->initTime();
		$this->initPHP();
		$this->initDependencies();
		$this->initEnvironment();
		$this->initConfiguration();

		$this->Logger			= new giLogger();						// logging tool
		$this->Response 		= new giResponse();						// output formatting tool
		$this->Router			= new giRouter();						// handles routing of requests
		$this->Database			= new giDatabase(); 					// database abstraction layer
		$this->Debug			= new giDebug();						// debugging helper
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
			$giIncludes['cached'] = json_decode(file_get_contents('../private/data/cache/includes/core.json'),true);
			
		}
		
		// if the cache is available
		if(isset($giIncludes['cached'])) {
			// include core classes
			foreach($giIncludes['cached']['includes']['vendor'] as $anInclude) {
				// include the class
				include($anInclude);	
			}
		}
		// no cache file is available for includes
		else {
			// load all the vendor libraries
			foreach(scandir('../private/vendor/') as $aVendorFolder) {
				// if it's a folder
				if(is_dir('../private/vendor/'.$aVendorFolder)) {
					// if a generic named loader exists
					if(file_exists('../private/vendor/'.$aVendorFolder.'/'.$aVendorFolder.'.php')) {
						// define the include
						$anInclude = '../private/vendor/'.$aVendorFolder.'/'.$aVendorFolder.'.php';
						// push the include
						$giIncludes['includes']['vendor'][] = $anInclude;
						// actually include it
						include($anInclude);
					}		
				}
			}
		}

		// if the cache is available
		if(isset($giIncludes['cached'])) {
			// for each file to include
			foreach($giIncludes['cached']['includes']['plugins'] as $anInclude) {
				// include the class
				include($anInclude);	
			}
			// for each configuration file to include
			foreach($giIncludes['cached']['includes']['plugins_configurations'] as $aPluginConfiguration) {
				// include the configuration file
				include($aPluginConfiguration['path']);	
				// set the plugins runtime environnement
				$this->Runtime[$aPluginConfiguration['name']] = $aPlugin['runtime'];
				// remove the runtime
				unset($aPlugin);
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
					// set the plugin assets path
					$aPlugin['assets']				= '../private/plugins/' . $aPlugin['name'] . '/assets/';
					// set the plugin assets path
					$aPlugin['locales']				= '../private/plugins/' . $aPlugin['name'] . '/locales/';
					// set the plugin classes path
					$aPlugin['classes']				= '../private/plugins/' . $aPlugin['name'] . '/classes/';
					// set the plugin libraries path
					$aPlugin['libraries']			= '../private/plugins/' . $aPlugin['name'] . '/libraries/';
					// set the plugins configuration file path
					$aPlugin['configuration']		= '../private/plugins/' . $aPlugin['name'] . '/configuration/configuration.php';
					// set the plugins initializator file path
					$aPlugin['initializator']		= '../private/plugins/' . $aPlugin['name'] . '/initializer/initializer.php';
					// try to include libraries
					if(is_dir($aPlugin['classes'])) {
						// iterate on plugins libraries
						foreach(scandir($aPlugin['classes']) as $aPlugin['potential_class'] ) {
							// if the filename matches a pattern
							if(strpos($aPlugin['potential_class'] ,'.php') and substr($aPlugin['potential_class'] ,0,1) != '.') {
								// set the include
								$aPlugin['an_include'] = $aPlugin['classes'].$aPlugin['potential_class'] ;
								// push the include
								$giIncludes['includes']['plugins'][] = $aPlugin['an_include'];
								// include the actual class
								include($aPlugin['an_include']);
							}
						}
					}
					// try to include classes
					if(is_dir($aPlugin['libraries'])) {
						// iterate on plugins libraries
						foreach(scandir($aPlugin['libraries']) as $aPlugin['potential_library'] ) {
							// if the filename matches a pattern
							if(strpos($aPlugin['potential_library'] ,'.php') and substr($aPlugin['potential_library'] ,0,1) != '.') {
								// set the include
								$aPlugin['an_include'] = $aPlugin['libraries'].$aPlugin['potential_library'] ;
								// push the include
								$giIncludes['includes']['plugins'][] = $aPlugin['an_include'];
								// include the actual library
								include($aPlugin['an_include']);
							}
						}
					}
					// try to add locales
					if(is_dir($aPlugin['locales'])) {
						// iterate on plugins libraries
						foreach(scandir($aPlugin['locales']) as $aPlugin['potential_locale'] ) {
							// if the filename matches a pattern
							if(strpos($aPlugin['potential_locale'] ,'.csv') and substr($aPlugin['potential_locale'] ,0,1) != '.') {
								// set the include
								$aPlugin['a_locale'] = $aPlugin['locales'].$aPlugin['potential_locale'] ;
								// push the include
								$giIncludes['includes']['locales'][] = $aPlugin['a_locale'];
								// include the actual library
								$this->Localization->setLocales($aPlugin['a_locale']);
							}
						}
					}
					// try to include the configuration file
					if(file_exists($aPlugin['configuration'])) {
						// push the include
						$giIncludes['includes']['plugins_configurations'][] = array(
							'name'	=>$aPlugin['name'],
							'path'	=>$aPlugin['configuration']
						);
						// include
						include($aPlugin['configuration']);
					}
					// set the plugins runtime environnement
					$this->Runtime[$aPluginConfiguration['name']] = $aPlugin['runtime'];
					// try to include the initializator file
					if(file_exists($aPlugin['initializator'])) {
						// push the include
						$giIncludes['includes']['plugins'][] = $aPlugin['initializator'];
						// include
						include($aPlugin['initializator']);
					}
				// unset the temporary aPlugin variable
				unset($aPlugin);
				}
			}
		}

		// if the includes do not come from the cache
		if(!isset($giIncludes['cached'])) {
			
			// cache the includes
			file_put_contents('../private/data/cache/includes/core.json',json_encode($giIncludes));
			
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
		if($this->Router->Level != null) {
			// enforce security
			$this->Security->enforceSecurity($this->Router->Level,$this->Router->Module);
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
		
			// throw an exception
			Throw new Exception('giCore->sandbox() : Method ['.$this->Router->Class.'/'.$this->Router->Function.'] does not exist');
			
		}
		
		// execute the routed method indexAction is no :action pr $_POST['action'] provided
		$this->Controller->{$this->Router->Function}();
		
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
		
	}
	
	// include dependencies
	private function initDependencies() {
	
		// include the shared library
		include('../private/core/libraries/shared.php');
		
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
			$this->Configuration['is_cli'] = true;
			// set the current environment
			$this->Environment = $_SERVER['argv'][1];
		}
		// not command line
		else {
			// set as command line
			$this->Configuration['is_cli'] = false;
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
	
	// access configuration parameters
	public function get($section=null,$parameter=null) {
		
		// if no specific section is given
		if(!$section) {
			// return the whole configuration
			return($this->Configuration);
		}
		// a section is requested
		else {
			// if no specific parameter is requested
			if(!$parameter) {
				// return the section
				return($this->Configuration[$section]);
			}
			// a specific parameter is requested
			else {
				// return only the parameter
				return($this->Configuration[$section][$parameter]);
			}
		}
	}
	
	
}

?>