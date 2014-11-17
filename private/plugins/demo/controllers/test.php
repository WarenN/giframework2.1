<?php

echo $routed_plugin;
echo 'test1';



class TestController extends giController {
	
	
	public function defaultAction() {
	
		$this->Core->Response->setType('html');
		$this->Core->Response->setTitle('Hello world #123');
		$this->Core->Response->setContent('Hello world #123');
		$this->Core->Response->output();
		$this->Core->Database->select('');
		

		$this->View('');
	}
	
	public function listAction() {
	
		
		
	}
	
}

?>