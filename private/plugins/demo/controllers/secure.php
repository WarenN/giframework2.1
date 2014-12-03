<?php


class SecureController extends giController {
	
	// this will respond to /demo/
	public function indexAction() {
		$this->Core->Response->setContent('hello');
	}
	
	public function defaultAction() {
		
		$this->Core->Response->setType('text');
		$this->Core->Response->setContent('This action is not supported');
		$this->Core->Response->output();
			
	}
	
	// this will respond to /demo/format/
	public function testAction() {
	
		die('here');
				
	}
	
}

?>