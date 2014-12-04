<?php


class SecureController extends giController {
	
	public function indexAction() {
		$this->Core->Response->setContent('hello');
	}
	
	public function defaultAction() {
		$this->Core->Response->setType('text');
		$this->Core->Response->setContent('This action is not supported');
		$this->Core->Response->output();
	}
	
	public function testAction() {
	}
	
}

?>