<?php

//echo $routed_plugin;
//echo 'test1';



class TestController extends giController {
	
	
	public function defaultAction() {
	
		
		switch($this->Core->Router->Parameters->Action) {
		
			case 'formating-test':
				$this->formatAction();
			break;
			
			case 'debug':
				$this->debugAction();
			break;
		
			default;	
		}
		
	}
	
	private function formatAction() {
	
		/*
		call this page with /demo/formating-test/json/
		or /demo/formating-test/xml/
		or /demo/formating-test/text/
		*/
	
		// sets the type and formating according to an url parameter
		$this->Core->Response->setType($this->Core->Router->Parameters->Format);
		// sets some random content
		$this->Core->Response->setContent(array(
			'blue'=>'sea',
			'green'=>'leaves',
			'brown'=>'earth',
			'yellow'=>'sand'
		));
		
	}
	
	private function debugAction() {
	
		//var_dump($this->Core->Router);
		//die();
		
	}
	
	
}

?>