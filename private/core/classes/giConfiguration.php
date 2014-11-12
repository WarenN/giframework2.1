<?php

class giConfiguration {
	
	protected $Configuration;
	protected $Environment;
	
	public function __construct($giConfiguration) {
		// set the configuration
		$this->Configuration = $giConfiguration;
		// auto detect the environment
		$this->determineEnvironment();
	}
	
	public function isCommandLine() {
		return($this->isCommandLine);	
	}

	// autodetect env
	private function determineEnvironment() {
		
		// default environment is set to production
		$this->setEnvironment('prod');

		// if the remote ip is a local one
		if(strpos($_SERVER['REMOTE_ADDR'],'192.168.') !== false) {
			// set as dev
			$this->setEnvironment('dev');	
		}
		// if the remote ip is a loopback
		elseif(strpos($_SERVER['REMOTE_ADDR'],'127.0.0.') !== false) {
			// set as dev
			$this->setEnvironment('dev');	
		}
		elseif(strpos($_SERVER['REMOTE_ADDR'],'10.10.10.') !== false) {
			// set as dev
			$this->setEnvironment('dev');	
		}
		// if the domain contain localhost
		elseif(strpos($_SERVER['HTTP_HOST'],'localhost') !== false) {
			// set as dev
			$this->setEnvironment('dev');
		}
		
	}

	// force setting env
	public function setEnvironment($Environment) {
		// set the current environment
		$this->Environment	= $Environment;	
		// for each environment specific value
		foreach($this->Configuration[$this->Environment] as $aKey => $aValue) {
			// set it as static
			$this->Configuration[$aKey] = $aValue;
		}
	}

	public function getStartTime() {
		return($this->Configuration['scriptStartTime']);
	}

	public function getConfiguration() {
		return($this->Configuration);	
	}
	
	/* IS somethingEnabled ? */
	
	public function isObfuscationEnabled() {
		return((boolean)$this->Configuration['enableObfuscation']);
	}
	
	public function isIndentationEnabled() {
		return((boolean)$this->Configuration['enableIndentation']);
	}

	public function isCachingEnabled() {
		return((boolean)$this->Configuration['enableCaching']);	
	}
	
	public function isCompressionEnabled() {
		return((boolean)$this->Configuration['enableCompression']);	
	}

	public function isDebugEnabled() {
		return((boolean)$this->Configuration['enableDebug']);	
	}
	
	public function isMemcacheEnabled() {
		return((boolean)$this->Configuration['enableMemcache']);
	}

	/* ¿ IS somethingEnabled */

	public function getHomePage() {
		return($this->Configuration['configHomeUrl']);	
	}

	public function getLoginPage() {
		return($this->Configuration['configLoginUrl']);	
	}

	public function getLogoutPage() {
		return($this->Configuration['configLogoutUrl']);	
	}
	
	public function get404Page() {
		return($this->Configuration['config404Url']);	
	}

	public function getEnvironment() {
		return($this->Environment);	
	}

	public function getAvailableLanguages() {
		return($this->Configuration['availableLanguages']);	
	}

	public function getDefaultLanguage() {
		return($this->defaultLanguage);	
	}

	public function getLocalizationEngine() {
		return($this->localizationEngine);	
	}

	public function getAnalyticsId() {
		return($this->Configuration['pageGoogleAnalyticsId']);	
	}

	public function getPluginRuntime($aPlugin) {
		return($this->Configuration['plugins']['runtime'][$aPlugin]);
	}
	
	public function setPluginRuntime($aPlugin,$aPluginRuntime) {
		$this->Configuration['plugins']['runtime'][$aPlugin]		= $aPluginRuntime;	
	}
	
}

?>