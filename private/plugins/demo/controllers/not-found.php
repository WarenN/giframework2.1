<?php

class NotFoundController extends giController {
	
	public function defaultAction() {
		
		$this->Core->Response->setMeta(array('title'=>'404 - Not Found'));
		$this->Core->Response->setContent('404, Not Found');
		$this->Core->Response->output();
		
	}
	
}


?>