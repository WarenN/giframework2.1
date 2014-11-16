<?php

// if the configuration file does not exist
if(!file_exists('../private/data/cache/plugins/pages.json') or $giConfiguration->getEnvironment() == 'dev') {
	// parses folders to build the pages mapping
	parseStatic('../private/plugins/pages/handlers/',$mapping);
	// if in production
	if($giConfiguration->getEnvironment() == 'prod') {
		// save the parsed mapping
		$aCacheOutput = new giOutput();
		$aCacheOutput->setContent($mapping);
		$aCacheOutput->setType('json');
		$aCacheOutput->save('../private/data/cache/plugins/pages.json',true);
	}
}	
// a cache file exists
else {
	// import it (force converting objects to arrays)
	$mapping = json_decode(file_get_contents('../private/data/cache/plugins/pages.json'),true);
}

// scan the handlers directory
foreach($mapping as $aPage) {
	// add an association URL -> HANDLER
	$giRequest->setHandler(
		// plugin's name
		'pages',
		// listerning url
		$aPage['url'],
		// listerning handler (.php will be happened automatically)
		$aPage['handler'],
		// is dynamic (if data after the base url should be used as parameters)
		false
	);
}

// delete the mapping
unset($mapping,$aCacheOutput);

?>