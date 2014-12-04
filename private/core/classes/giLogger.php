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

class giLogger {

	// general log path	
	protected $logPath;
	
	// general singleton constructor
	public function __construct() {
		
		// set the path for the logs
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
		global $app;
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
		$logMessage .= ' u:['.$app->Security->getLogin().']';
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