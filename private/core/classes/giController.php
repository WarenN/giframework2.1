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
	
	// method to be overiden by the actual controller, executed before the actual action
	public function preAction() {
		
	}
	
	// method to be overiden by the actual controller, executed after the actual action
	public function postAction() {
		
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
	
	public function includeClass($class) {
		// include the specific template
		$class_path = '../private/plugins/'.$this->Core->Router->Plugin.'/classes/'.$class.'.php';
		// if the view doesn't exist
		if(!file_exists($class_path)) {
			// exception
			Throw new Exception('giController->includeClass() : missing class ['.$class_path.']');	
		}
		// the view exists
		else {
			// include it
			include($class_path);
		}
	}
	
	public function includeLibrary($library) {
		// include the specific template
		$library_path = '../private/plugins/'.$this->Core->Router->Plugin.'/libraries/'.$library.'.php';
		// if the view doesn't exist
		if(!file_exists($library_path)) {
			// exception
			Throw new Exception('giController->includeLibrary() : missing library ['.$library_path.']');	
		}
		// the view exists
		else {
			// include it
			include($library_path);
		}
	}
	
	public function includePartial($partial) {
		// include the specific template
		$partial_path = '../private/plugins/'.$this->Core->Router->Plugin.'/libraries/'.$partial.'.php';
		// if the view doesn't exist
		if(!file_exists($partial_path)) {
			// exception
			Throw new Exception('giController->includePartial() : missing partial ['.$partial_path.']');	
		}
		// the view exists
		else {
			// include it
			include($partial_path);
		}
	}
	
	public function includeVendor($vendor) {
		// remove any double points
		$vendor = str_replace('..','',$vendor);
		// include the specific template
		$vendor_path = '../private/vendor/'.$vendor.'.php';
		// if the view doesn't exist
		if(!file_exists($vendor_path)) {
			// exception
			Throw new Exception('giController->includeVendor() : missing vendor ['.$vendor_path.']');	
		}
		// the view exists
		else {
			// include it
			include($vendor_path);
		}
		
	}

}

?>