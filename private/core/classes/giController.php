<?php

class giController {
	
	// references to core object
	public $Core;
	
	// actual data required by the view
	public $Data;
	public $View;
	
	// prepare controller environment
	public function __construct(&$giCore) {
	
		// give access to useful objects
		$this->Core 			= &$giCore;
		
		// data passed to the view
		$this->Data				= null;
		
	}
	
	// pass to a specific view
	public function view($view_name) {
		
		// include the specific template
		$view_path = '../private/plugins/'.$this->Core->Router->Plugin.'/views/'.$view_name.'.php';
		
		// if the view doesn't exist
		if(!file_exists($view_path)) {
		
			// exception
			Throw new Exception('giController->view() : missing view script ['.$view_path.']');	
			
		}
		// the view exists
		else {
			// include it
			include($view_path);
		}
			
	}
	
}

?>