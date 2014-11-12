<?php

class giDebug {
	
	protected $debugInfos;	// array of informations
	protected $debugBox;	// string of formatted informations
	
	// init
	public function __construct() {
		$this->debugInfos		= array();
		$this->debugBox			= '';
	}
	
	// add an element
	public function addElement($key,$value) {
		// push it
		$this->debugInfos[$key] = $value;
	}
	
	// build the list of debug elements
	private function buildDebugElements() {
		
		// access the request handler
		global $giRequest,$giConfiguration,$giAuthentication,$giOutput,$giLocalization;
		
		// add each default debug element that we need
		$this->addElement('server_ip',			$_SERVER['SERVER_ADDR']);
		$this->addElement('browser_ip',			$_SERVER['REMOTE_ADDR']);
		$this->addElement('version',			'PHP '.phpversion());
		$this->addElement('max_execution_time',	ini_get('max_execution_time'));
		$this->addElement('current_execution_time',$giOutput->getExecutionTime());
		$this->addElement('post_max_size',		ini_get('post_max_size'));
		$this->addElement('upload_max_filesize',ini_get('upload_max_filesize'));
		$this->addElement('memory_limit',		ini_get('memory_limit'));
		$this->addElement('display_errors',		ini_get('display_errors'));
		$this->addElement('log_errors',			ini_get('log_errors'));
		$this->addElement('error_log',			ini_get('error_log'));
		$this->addElement('handler',			$giRequest->getHandler());
		$this->addElement('current_language',	$giLocalization->getLanguage());
		$this->addElement('locales_from_cache',	$giLocalization->isFromCache());
		$this->addElement('login',				$giAuthentication->getSelfLogin());
		$this->addElement('extension_dir',		ini_get('extension_dir'));
		
		if(extension_loaded('memcache')){
			$this->addElement('memcache.so',true);
		}
		else {
			$this->addElement('memcache.so',false);
		}
		
		if(extension_loaded('apc')) {
			$this->addElement('apc.so',		true);
		}
		else {
			$this->addElement('apc.so',		false);
		}
		
		if(extension_loaded('mbstring')) {
			$this->addElement('mbstring.so',true);
		}
		else {
			$this->addElement('mbstring.so',false);
		}
		
		if(extension_loaded('iconv')) {
			$this->addElement('iconv.so',	true);
		}
		else {
			$this->addElement('iconv.so',	false);
		}
		if(extension_loaded('pdo')) {
			$this->addElement('pdo.so',		true);
		}
		else {
			$this->addElement('pdo.so',		false);
		}
		if(extension_loaded('gd')) {
			$this->addElement('gd.so',		true);
		}
		else {
			$this->addElement('gd.so',		false);
		}
		if(extension_loaded('zlib')) {
			$this->addElement('zlib.so',	true);
		}
		else {
			$this->addElement('zlib.so',	false);
		}
		if(extension_loaded('exif')) {
			$this->addElement('exif.so',	true);
		}
		else {
			$this->addElement('exif.so',	false);
		}
		if(extension_loaded('libxml')) {
			$this->addElement('libxml.so',	true);
		}
		else {
			$this->addElement('libxml.so',	false);
		}
		
		$this->addElement('isObfuscationEnabled',		$giConfiguration->isObfuscationEnabled());
		$this->addElement('isIndentationEnabled',		$giConfiguration->isIndentationEnabled());
		$this->addElement('isCachingEnabled',			$giConfiguration->isCachingEnabled());
		$this->addElement('isCompressionEnabled',		$giConfiguration->isCompressionEnabled());
		$this->addElement('isDebugEnabled',				$giConfiguration->isDebugEnabled());
		$this->addElement('isMemcacheEnabled',			$giConfiguration->isMemcacheEnabled());
		
	}
	
	// get an array of debug elements
	public function getDebugArray() {
		
		// build the list of debug elements
		$this->buildDebugElements();
		
		// return the raw array
		return($this->debugInfos);
		
	}
	
	// get the formated code
	public function getDebugHTML() {
		
		// build the list of debug elements
		$this->buildDebugElements();
				
		// preprend code
		$this->debugBox = '<div style="index:128;position: absolute; top: 0px; right: 0px;background: #efefef;font-family:courier;font-size:11px; border-left: solid 1px #999999;box-shadow:rgba(0,0,0,0.4) 0px 0px 5px;opacity: 0.8;"><table>';
		
		// zebra formating
		$zebra = false;
		
		// for each debug element
		foreach($this->debugInfos as $aDebugKey => $aDebugValue) {
			// if it's a boolean
			if($aDebugValue == '0') {
				$aDebugValue = '<span style="color: red;">✘</span>';
			}
			elseif($aDebugValue == '1') {
				$aDebugValue = '<span style="color: green;">✔</span>';
			}
			// else we remove tags
			else {
				$aDebugValue = strip_tags($aDebugValue);	
			}
			// zebra formating
			if($zebra) {
				$zebra = false;	
				$firstColor = '#c3c3c3';
				$secondColor = '#efefef';
			}
			else {
				$zebra = true;
				$firstColor = '#a6a6a6';
				$secondColor = '#dcdcdc';
			}
			// add a specific line
			$this->debugBox .= '
			<tr>
				<td style="padding: 3px; background: '.$firstColor.'; border-bottom: solid 1px #999999;">
					'.strip_tags($aDebugKey).'
				</td>
				<td style="padding: 3px; background: '.$secondColor.';border-bottom: solid 1px #999999;">
					<b>
					'.$aDebugValue.'
					</b>
				</td>
			</tr>
			';
				
		}
		// append code
		$this->debugBox .= '</table></div>';
		
		// return the html formated debug box
		return($this->debugBox);
		
	}
	
}

?>