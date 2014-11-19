<?php

class ExceptionController extends giController {
	
	public function indexAction() {
		
		$this->Core->Response->setMeta(array('title'=>'Exception'));
		$this->Core->Response->setContent('Exception');
		$this->Core->Response->output();
		
	}
	
}


?>