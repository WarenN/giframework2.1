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
	
	public function insertQueryAction() {
	
		$result = $this->Core->Database->query()
		->insert(array(
			'login'=>substr(md5(rand(0,99999)),0,4),
			'password'=>md5(rand(0,99999)),
			'id_level'=>rand(0,99),
			'last_login_date'=>'18/11/2014',
			'rights_array'=>array('mod_'.rand(9,999),'mod_'.rand(99,999)),
			'is_enabled'=>rand(0,1)
		))
		->into('accounts')
		->execute();
		
		var_dump($result);
		
	}
	
	public function updateQueryAction() {
	
		$query = $this->Core->Database->query();
		$result = $query
		->update('accounts')
		->set(array('last_login_date'=>'18/03/1995','last_login_origin'=>'45.106.223.90'))
		->where(array('is_enabled'=>'0'))
		->execute();
		
		var_dump($query,$result);
		die();
		
	}
	
	public function testQueryAction() {
	
		$query = $this->Core->Database->query();
		$result = $query
//		->select(array('id','login','password'))
//		->select(array('max'=>'id','0'=>'login','1'=>'password','2'=>'id'))
		->select()
		->from('accounts')
		->where(array('is_enabled'=>'1'))
//		->addAnd()
//		->where(array('login'=>'root'))
//		->addOr()
//		->whereLowerThan('id','999')
//		->where(array('password'=>'eg5§dby'))
//		->insert(array()) // crash test
//		->orderBy(array('id'=>'ASC','login'=>'DESC'))
//		->limitTo(0,10)
		->execute();
		var_dump($query,$result);
	//	foreach($result as $aResult) {
		//	var_dump($aResult);	
	//	}
		die();
		
	}
	
	
}

?>