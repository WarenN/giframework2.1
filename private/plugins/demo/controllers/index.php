<?php

class IndexController extends giController {
	
	
	public function indexAction() {
		
			// cache the page for 24 hour (if cache_enable is set to 1 in your ini bloc [response])
			$this->Core->Response->freezeFor(24);
		
		// pass to view
		$this->view('index');

	}

	
}

?>