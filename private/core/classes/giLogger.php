<?php

class giLogger {

	// general log path	
	protected $logPath;
	
	// general singleton constructor
	public function __construct() {
		
		$this->logPath = '../private/data/storage/logs/common.log';
		
	}
	
	// lower level of importance
	public function info($message) {
		// format
		$formattedLog = $this->formatLogMessage('INFO',$message);
		// write
		$this->writeLogMessage($formattedLog);
	}
	
	// notice level
	public function notice($message) {
		// format
		$formattedLog = $this->formatLogMessage('NOTICE',$message);
		// write
		$this->writeLogMessage($formattedLog);
	}
	
	// error level
	public function error($message) {
		// format
		$formattedLog = $this->formatLogMessage('ERROR',$message);
		// write
		$this->writeLogMessage($formattedLog);
	}
	
	// attack/intrusion level
	public function security($message) {
		// format
		$formattedLog = $this->formatLogMessage('SECURITY',$message);
		// write
		$this->writeLogMessage($formattedLog);
	}
	
	// formats associative arrays
	private function formatArray($anArray) {
		
		// prepare the returned string
		$aString = '';
		
		// if it's an array
		if(is_array($anArray)) {
			// for each element of the array
			foreach($anArray as $aKey => $aValue) {
				// append to the string
				$aString .= $aKey.'='.$aValue.';';
			}
		}
		
		// return the string
		return($aString);
			
	}
	
	// format the log message
	private function formatLogMessage($level,$message) {
		// access authentiction
		global $giAuthentication;
		// declare the log message
		$logMessage = '';
		// first set the date and time
		$logMessage .= date('d/m/y H:i:s');
		// alert level
		$logMessage .= ' l:['.$level.']';
		// request method
		$logMessage .= ' rm:['.$_SERVER['REQUEST_METHOD'].']';
		// query string
		$logMessage .= ' qs:['.$_SERVER['REQUEST_URI'].']';
		// remote ip
		$logMessage .= ' ip:['.$_SERVER['REMOTE_ADDR'].']';
		// script
		$logMessage .= ' s:['.$_SERVER['SCRIPT_FILENAME'].']';
		// authed used
		$logMessage .= ' u:['.$giAuthentication->getSelfLogin().']';
		// error message
		$logMessage .= ' m:['.$message.']';
		// general
		$logMessage .= ' cookie:['.$this->formatArray($_COOKIE).']';
		// general
		$logMessage .= ' post:['.$this->formatArray($_POST).']';
		// general
		$logMessage .= ' get:['.$this->formatArray($_GET).']';
		// return the formatted message
		return($logMessage);
	}
	
	// write log message to disk
	private function writeLogMessage($logMessage) {
		// append to current log file
		file_put_contents('../private/data/logs/common.log',$logMessage."\n",FILE_APPEND);
	}
	
}
	
?>