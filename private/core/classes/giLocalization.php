<?php
/**
 * PHP Version 5
 * @package giFramework
 * @link https://github.com/AnnoyingTechnology/giframework2.1
 * @author Julien Arnaud (AnnoyingTechnology) <e10ad5d4ab72523920e7cbe55ba6c91c@gribouille.eu@gribouille.eu>
 * @copyright 2015 - 2015 Julien Arnaud
 * @license http://www.gnu.org/licenses/lgpl.txt GNU General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */

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
		$this->localizationCache	= '../private/data/cache/locales/translations.json';
		$this->currentLanguage		= $this->defaultLanguage;
		$this->isFromCache			= false;
		
	}

	public function setConfiguration($available_languages,$default_language,$default_locales) {
	
		$this->availableLanguages	= $available_languages;
		$this->defaultLanguage		= $default_language;
		$this->currentLanguage		= $default_language;
		
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
		if($_COOKIES[self::COOKIE]) {
			// use it
			// $this->setLanguage($_COOKIE['giLanguage']);
		}
		// no cookie set
		else {
			// detect browser's language
			// $app->Router->getHeaders()
			// if no match
			// use default
		}
		
	}
	
	// return if the locales are from the cache or not
	public function isFromCache() {
		// return the boolean
		return($this->isFromCache);
	}
	
	// set the language
	public function setLanguage($language) {
		// if language is authorized
		if(in_array($language,$this->availableLanguages)) {
			// set it as current
			$this->currentLanguage	= $language;	
			// memorize in a cookie
			$this->setCookie($language);
		}
		// language is not authorized
		else {
			// set default as current
			$this->currentLanguage	= $this->defaultLanguage;
			// memorize it in a cookie
			$this->setCookie($language);
		}
	}
	
	// get current language
	public function getLanguage() {
		return($this->currentLanguage);	
	}
	
	// return the localized string (if any)
	public function translate($localeKey) {
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
		$aLocalizationFile	= file_get_contents($path);
		// use each line separatly
		$lineExploded		= explode("\n",$aLocalizationFile);
		// use the first line as an index of languages codes
		$localizationIndex	= explode("\t",$lineExploded[0]);
		// for each language declared in the first line
		foreach($localizationIndex as $indexNumerical => $indexLanguage) { 
			// trim and save it
			$localizationIndex[$indexNumerical] = trim($indexLanguage,'"'); 
		}
		$localizationIndex	= array_flip($localizationIndex);
		// remove the first indexes line
		unset($lineExploded[0]);
		// for each line of translations
		foreach($lineExploded as $aLocalzedLine) {
			// get each available translation
			$tabExploded = explode("\t",$aLocalzedLine);
			// get the key for that translation
			$keyword = trim($tabExploded[0],'"');
			// for each translated string
			foreach($this->availableLanguages as $aLanguage) {
				$index = $localizationIndex[$aLanguage];
				$locale = @trim($tabExploded[@$index],'"');
				// save the localized string
				$this->availableLocales[$aLanguage][$keyword] = $locale;
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