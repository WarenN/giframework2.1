<?php


class TestController extends giController {
	
	// this will respond to /demo/
	public function indexAction() {
	
		$this->debugRouterAction();
		
	}
	
	// this will respond to /demo/format/
	public function formatAction() {
	
		/*
		call this page with /demo/formating-test/json/
		or /demo/format/xml/
		or /demo/format/text/
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
	
	// this will NOT respond to /demo/debugRouter/ as the method is private
	private function debugRouterAction() {
	
			$this->Core->Response->freezeFor(24);
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
			
//			$this->Core->Response->setContent($this->Core->Database->select('Accounts')[0]);
			$this->Core->Response->setContent($this->Core->Database->select('Accounts')[0]->asArray());
//			$this->Core->Response->setContent($this->Core->Database->select('Accounts')[0]->asArray(true));
		
	}
	
	public function testQueryAction() {
	
		$query = $this->Core->Database->query();
		//var_dump($query);
		//die();
		$query
		->select()
		->from('accounts')
		->addAnd()
		->where(array('id_level'=>'1'))
		->addOr()
		->whereLowerThan('id','999')
		->orderBy(array('id'=>'ASC'))
		->limitTo(0,10);
		
		var_dump($query);
		die();
		
	}
	
	
}

?>