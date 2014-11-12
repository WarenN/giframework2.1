<?php
$giConfiguration = array(

	// crawler and indexing robot rules
	'pageRobotRule' 			=> 'noindex,nofollow',
	'pageRevisitRate' 			=> '14 days',
	
	// general meta tags
	'pageTitle' 				=> '',
	'pageDescription' 			=> '',
	'pageKeywords' 				=> '',
	'pageCopyright' 			=> '',
	'pageCompany' 				=> '',
	'pageLanguage' 				=> '',
	'pageAuthor' 				=> '',
	
	// geo localization tags
	'pageGeoPosition' 			=> '',
	'pageGeoPlacename' 			=> '',
	'pageGeoRegion' 			=> '',
	
	// assets (JS/CSS)
	'pageJavascript' 			=> array('mootools-core.js','mootools-more.js','mootools-threads.js','mootools-mbox.js'),
	'pageStylesheets' 			=> array('reset.css','mbox.css'),

	// google verifications & analytics
	'pageGoogleSiteVerification'=> '',
	'pageGoogleAnalyticsId'		=> '',
	
	// localization
	'availableLanguages'		=> array('fr','en'),
	'defaultLanguage'			=> 'fr',
	'defaultLocales'			=> '../private/data/localized/core.csv',
	
	'configLoginUrl'			=> '/identification/', 	// page de login
	'configLogoutUrl'			=> '/identification/', 	// page de sortie
	'configHomeUrl'				=> '/administration/', 	// page d'entrée
	'config404'					=> '/404.html',			// page non trouvée
	
	// environment specific options
	'dev'			=> array(
	
		// general functionnalities configuration
		'enableIndentation'			=> false,	// enable auto indentation of HTML code
		'enableObfuscation'			=> false,	// enable auto minifying of HTML code
		'enableCompression'			=> false,	// enable output compression
		'enableDebug'				=> true,	// enable debug console
		'enableCaching'				=> false,	// enable output caching
		'enableMemcache'			=> false,	// enable memcache for SQL queries
		
		// database configuration
		'database'	=> array(
			'driver'	=> 'sqlite',
			'database'	=> '../private/data/sqlite/common.sqlite',
			'username'	=> '',
			'password'	=> '',
			'hostname'	=> '',
		),
		
		// sql cache configuration
		'cache'		=> array(
			'prefix'	=> 'giF',
			'hostname'	=> '127.0.0.1',
			'port'		=> '11211',
		),
		
		// mail configuration
		'mail'		=> array(
			'url'		=> 'http://noreply/',
			'email'		=> 'noreply@noreply.com',
			'name'		=> 'dev',
		)
		
	),
	// environment specific options
	'prod'			=> array(
	
		// general functionnalities configuration
		'enableIndentation'			=> false,	// enable auto indentation of HTML code
		'enableObfuscation'			=> true,	// enable auto minifying of HTML code
		'enableCompression'			=> true,	// enable output compression
		'enableDebug'				=> true,	// enable debug console
		'enableCaching'				=> true,	// enable output caching
		'enableMemcache'			=> false,	// enable memcache for SQL queries
		
		// database configuration
		'database'	=> array(
			'driver'	=> 'sqlite',
			'database'	=> '../private/data/sqlite/common.sqlite',
			'username'	=> '',
			'password'	=> '',
			'hostname'	=> '',
		),
		
		// sql cache configuration
		'cache'		=> array(
			'prefix'	=> 'giF',
			'hostname'	=> '127.0.0.1',
			'port'		=> '11211',
		),
		
		// mail configuration
		'mail'		=> array(
			'url'		=> 'http://www.noreply.com/',
			'email'		=> 'noreply@noreply.com',
			'name'		=> 'prod',
		)
		
	)

);
?>