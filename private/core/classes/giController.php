<?php

class giController {
	
	// references to useful objects
	protected $Router;
	protected $Database;
	protected $Security;
	protected $Response;
	protected $Configuration;
	
	// actual data required by the view
	protected $Data;
	
	// prepare controller environment
	public function __construct() {
	
		// access global elements
		global $giRouter,$giDatabase,$giSecurity,$giResponse,$giConfiguration;
		
		// give access to useful objects
		$this->Router 			= &$giRouter;
		$this->Database 		= &$giDatabase;
		$this->Security 		= &$giSecurity;
		$this->Response 		= &$giResponse;
		$this->Configuration 	= &$giConfiguration;
		
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