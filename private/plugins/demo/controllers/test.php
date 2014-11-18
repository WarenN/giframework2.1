<?php


class TestController extends giController {
	
	// this will respond to /demo/
	public function indexAction() {
	
		$this->debugAction();
		
	}
	
	// this will respond to /demo/format/
	public function formatAction() {
	
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
	
	// this will NOT respond to /demo/debug/ as the method is private
	private function debugAction() {
	
			$this->Core->Response->setType('text');
		$this->Core->Response->setContent(var_export($this->Core->Router));

	}
	
	// this will respond to /demo/helloWorld/
	public function helloWorldAction() {

		$this->Core->Response->setType('text');
		$this->Core->Response->setContent('Hello world!');

	}
	
	// this will respond to /demo/memoryTest/
	public function memoryTestAction() {
		
		$this->Core->Response->setType('json');
		$this->Core->Response->setContent(array('get_memory_usage'=>memory_get_usage()));
		
	}
	
	public function testDatabaseAction() {
	
			$this->Core->Response->setType('text');
			$this->Core->Response->setContent($this->Core->Database->select('Accounts'));
		
	}
	
	
}

?>