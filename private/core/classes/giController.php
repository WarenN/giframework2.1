<?php

class giController {
	
	// references to useful objects
	public $Core;
	
	// actual data required by the view
	public $Data;
	
	// prepare controller environment
	public function __construct(&$giCore) {
	
		// give access to useful objects
		$this->Core 			= &$giCore;
		
		// data passed to the view
		$this->Data				= null;
		
	}
	
	// pass to a specific view
	public function View($template) {
		
		// include the specific template
		
		// pass it the $this->Data
			
	}
	
	public function __destruct() {
	
		
		
	}
	
}

?>