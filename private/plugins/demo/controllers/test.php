<?php

//echo $routed_plugin;
//echo 'test1';



class TestController extends giController {
	
	
	public function defaultAction() {
	
		//var_dump($this);
		$this->Core->Response->setType('html');
		$this->Core->Response->setMeta(array('title'=>'Hello world #123'));
		$this->Core->Response->setContent('Hello world #123');
		$this->Core->Response->output();
		//$this->Core->Database->select('');
		

	//	$this->View('');
	}
	
	public function listAction() {
	
		
		
	}
	
}

?>