<?php

// set the timezone
date_default_timezone_set('Europe/Paris');

// include configuration file
include('../private/core/configuration/configuration.php');

// include the shared library
include('../private/core/libraries/shared.php');

// start the time counter
$giConfiguration['scriptStartTime'] 	= microtime(true);

// include core classes
foreach(scandir('../private/core/classes/') as $aClassFile) {
	// if we find the proper extension
	if(strpos($aClassFile,'.php') and substr($aClassFile,0,1) != '.') {
		// set the file to include
		$anInclude = '../private/core/classes/' . $aClassFile;
		// push the include
		$giIncludes['includes']['core'][] = $anInclude;
		// include the file
		include($anInclude);
	}
}

// initialization of global objects
$giConfiguration	= new giConfiguration($giConfiguration);// handle the global configuration
$giLogger			= new giLogger();						// logging tool
$giOutput 			= new giOutput();						// output formatting tool
$giRequest			= new giRequest(); 						// handle requests
$giDatabase			= new giDatabase(); 					// database abstraction layer
$giDebug			= new giDebug();						// debugging helper
$giLocalization		= new giLocalization(); 				// help translating
$giAuthentication	= new giAuthentication(); 				// authentication and security handler

// get the configuration array
$giConfigurationArray = $giConfiguration->getConfiguration();

// if the environment is prod and a global cache file exists
if($giConfiguration->getEnvironment() == 'prod' and file_exists('../private/data/cache/includes/core.json')) {
	
	// grab the cached list of includes
	$giIncludes['cached'] = json_decode(file_get_contents('../private/data/cache/includes/core.json'),true);
	
}

// configure the database
$giDatabase->setConfiguration(
	$giConfigurationArray['database']['driver'],
	$giConfigurationArray['database']['database'],
	$giConfigurationArray['database']['username'],
	$giConfigurationArray['database']['password'],
	$giConfigurationArray['database']['hostname'],
	$giConfigurationArray['cache']['prefix'],
	$giConfigurationArray['cache']['hostname'],
	$giConfigurationArray['cache']['port']
);

//$giConfiguration->setEnvironment('prod');

// configure the localization
$giLocalization->setConfiguration(
	$giConfigurationArray['availableLanguages'],
	$giConfigurationArray['defaultLanguage'],
	$giConfigurationArray['defaultLocales']
);

// set a database shortcut
$db = &$giDatabase;

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
		$giConfiguration->setPluginRuntime($aPluginConfiguration['name'],$aPlugin['runtime']);
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
			$aPlugin['initializator']		= '../private/plugins/' . $aPlugin['name'] . '/initializator/initializator.php';
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
						$giLocalization->setLocales($aPlugin['a_locale']);
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
			$giConfiguration->setPluginRuntime($aPlugin['name'],$aPlugin['runtime']);
			// try to include the initializator file
			if(file_exists($aPlugin['initializator'])) {
				// push the include
				$giIncludes['includes']['plugins'][] = $aPlugin['initializator'];
				// include
				include($aPlugin['initializator']);
			}
			/*
			// if an assets folder exists and we are in dev environment
			if(is_dir($aPlugin['assets']) and $giConfiguration->getEnvironment() == 'dev') {
				// iterate on the assets subfolders
				foreach(scandir($aPlugin['assets']) as $aPlugin['assets_subfolder'] ) {
					// if the subfolder is acceptable
					if(in_array($aPlugin['assets_subfolder'],array('css','js','img','fonts'))) {
						// define the destination
						$aPlugin['assets_destinations'][$aPlugin['assets_subfolder']] = './'.$aPlugin['assets_subfolder'].'/'.$aPlugin['name'].'/';
						// if the folder doesn't exist already
						if(!is_dir($aPlugin['assets_destinations'][$aPlugin['assets_subfolder']])) {
							// create a similar folder in the public directory
							mkdir($aPlugin['assets_destinations'][$aPlugin['assets_subfolder']]);
						}
						// for each file in here
						foreach(scandir($aPlugin['assets'].$aPlugin['assets_subfolder']) as $aPlugin['an_asset']) {
							// if the file is valid
							if(substr($aPlugin['an_asset'],0,1) != '.') {
								// in case obfuscation is enabled
								if($giConfiguration->isObfuscationEnabled()) {
									// switch depending on the filetype
									switch($aPlugin['assets_subfolder']) {
										// in case of css
										case 'css':
											// set the file to minify
											$minifier = new CSS($aPlugin['assets'].$aPlugin['assets_subfolder'].'/'.$aPlugin['an_asset']);
											// minify and save it
											$minifier->minify($aPlugin['assets_destinations'][$aPlugin['assets_subfolder']].$aPlugin['an_asset'], CSS::ALL);
										break;
										// in case of js
										case 'js':
											// set the file to minify
											$minifier = new JS($aPlugin['assets'].$aPlugin['assets_subfolder'].'/'.$aPlugin['an_asset']);
											// minify and save it
											$minifier->minify($aPlugin['assets_destinations'][$aPlugin['assets_subfolder']].$aPlugin['an_asset'], JS::ALL);
											
										break;
										// in case of img/fonts
										case 'img':
										case 'fonts':
											// copy that specific asset
											copy(
												$aPlugin['assets'].$aPlugin['assets_subfolder'].'/'.$aPlugin['an_asset'],
												$aPlugin['assets_destinations'][$aPlugin['assets_subfolder']].$aPlugin['an_asset']
											);
										break;	
									}
								}
								// obfuscation is disabled
								else {
									// copy that specific asset
									copy(
										$aPlugin['assets'].$aPlugin['assets_subfolder'].'/'.$aPlugin['an_asset'],
										$aPlugin['assets_destinations'][$aPlugin['assets_subfolder']].$aPlugin['an_asset']
									);
								}
							}
						}
					}
				}
			}
			*/
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

// process the request
$giRequest->processRequest();

// autoconfigure the output
$giOutput->autoConfigure($giConfiguration);

// set html as the default type of output
$giOutput->setType('html');

// we start the buffer to grab everything that leaks from the handler
ob_start();

// we can now execute the proper handler
include($giRequest->getHandler());

// if the handler didn't set any content
if(!$giOutput->getContent()) {
	
	// use gargabe collection to set the content
	$giOutput->setContent(ob_get_clean());
	
}

// let the giOutput handle the collected garbage
$giOutput->output(); 

// this line in never reached by PHP
// EOF

?>