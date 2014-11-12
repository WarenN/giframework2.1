<?php

class giLocalization {
	
	// some general configuration
	const COOKIE	= 'giLanguage';	// define the cookies name
	const LIFETIME	= 86000;		// define the cookies lifetime
	
	// runtime variables
	protected $defaultLanguage;		// handle the default language name
	protected $availableLanguages;	// handle the list of available languages
	protected $currentLanguage;		// handle the current language
	protected $availableLocales;	// handle all the current locales
	protected $localizationCache;	// handle the cache file
	protected $localizationFiles;	// list the localisation files
	protected $isFromCache;			// true if locales come from cache
	
	public function __construct() {
	
		$this->availableLanguages	= array('en','fr');
		$this->availableLocales		= array();
		$this->defaultLanguage		= 'fr';
		$this->localizationCache	= '../private/data/cache/locales/localized.json';
		$this->currentLanguage		= $this->defaultLanguage;
		$this->isFromCache			= false;
		
	}

	public function setConfiguration($available_languages,$default_language,$default_locales) {
	
		$this->availableLanguages	= (array)	$available_languages;
		$this->defaultLanguage		= (string)	$default_language;
		$this->currentLanguage		= (string)	$this->defaultLanguage;
		
		// if a cache file exists
		if(file_exists($this->localizationCache)) {
			// load the cache
			$this->availableLocales = json_decode(file_get_contents($this->localizationCache),true);
			// set that locales come from cahe
			$this->isFromCache = true;
		}
		// else a cache file doesn't exist
		else {
			// we load the default locales
			$this->setLocales($default_locales); // load the core locales
		}

	}


	// autodetect language
	public function detectLanguage() {
	
		// if a cookie is set
			// use it
			
		// else we detect the language according to the browser signature
			// use it

		
	}
	
	// return if the locales are from the cache or not
	public function isFromCache() {
		// return the boolean
		return($this->isFromCache);
	}
	
	// set the language
	public function setLanguage($language) {
		if(in_array($language,$this->availableLanguages)) {
			$this->currentLanguage	= (string)	$language;	
			$this->setCookie($language);
		}
		else {
			$this->currentLanguage	= (string)	$this->defaultLanguage;	
			$this->setCookie($language);
		}
	}
	
	// get current language
	public function getLanguage() {
		return($this->currentLanguage);	
	}
	
	// return the localized string (if any)
	public function getLocale($localeKey) {
		// if that locale exists
		if(key_exists($localeKey,$this->availableLocales[$this->currentLanguage]) and $this->availableLocales[$this->currentLanguage][$localeKey] != '') {
			// return proper translation
			return($this->availableLocales[$this->currentLanguage][$localeKey]);
		}
		// locale is missing
		else {
			// return the key
			return($localeKey);	
		}
	}
	
	// set the language cookie
	private function setCookie($language) {
		// set the locale cookie
		setcookie(self::COOKIE,$language,time() + self::LIFETIME,'/');
	}
	
	// check if a language cookie is set
	private function checkCookie() {
		// if the cookie exists
		if(isset($_COOKIE[self::COOKIE])) {
			// set the language accordingly
			$this->setLanguage($_COOKIE[self::COOKIE]);	
		}
		// missing cookie
		else {
			// set the default language
			$this->setLanguage($this->defaultLanguage);	
		}
	}
	
	// set a locale file
	public function setLocales($path) {
		// import the locales from provided file
		$aLocalizationFile	= (string)	file_get_contents($path);
		$lineExploded		= (array)	explode("\n",$aLocalizationFile);
		$localizationIndex	= (array)	explode("\t",$lineExploded[0]);
		foreach($localizationIndex as $indexNumerical => $indexLanguage) { 
			$localizationIndex[$indexNumerical] = trim($indexLanguage,'"'); 
		}
		$localizationIndex	= (array)	array_flip($localizationIndex);
		unset($lineExploded[0]);
		foreach($lineExploded as $aLocalzedLine) {
			$tabExploded						= (array)	explode("\t",$aLocalzedLine);
			$keyword							= (string)	trim($tabExploded[0],'"');
			foreach($this->availableLanguages as $aLanguage) {
				$index								= (integer)	$localizationIndex[$aLanguage];
				$locale								= (string)	@trim($tabExploded[@$index],'"');
				$this->availableLocales[$aLanguage][$keyword]		= (string)	$locale;
			}
		}
	}
	
	// upon estruction
	public function __destruct() {

		// if locales don't already come from the cache
		if(!$this->isFromCache) {
			
			// cache the file
			file_put_contents($this->localizationCache,json_encode($this->availableLocales));	
			
		}
	}
	
	
}

?>