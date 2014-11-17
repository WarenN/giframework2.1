<?php

echo 'Hello World !';

class TestController extends giController {
	
	
	public function defaultAction() {
	
		$this->Response->setType('html');
		$this->Response->setTitle('Hello world');
		$this->Response->setContent('Hello world.');
		$this->Response->output();
		
	}
	
}

?>