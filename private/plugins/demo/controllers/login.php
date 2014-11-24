<?php

class LoginController extends giController {
	
	// routed to /
	public function indexAction() {
		
		// cache the page for 7 days (if cache_enable is set to 1 in your ini bloc [response])
		$this->Core->Response->freezeFor(7*24);
		
		// pass to view
		$this->view('login');

	}

}

?>