<?php

/*	

CREATE TABLE "Accounts" (
	"id" integer NULL PRIMARY KEY AUTOINCREMENT,
	"id_level" numeric NULL,
	"is_enabled" numeric NULL,
	"login" text NULL,
	"password" text NULL,
	"creation_date" numeric NULL,
	"account_expiration_date" numeric NULL,
	"session_key" text NULL,
	"session_expiration_date" numeric NULL,
	"last_login_origin" text NULL,
	"last_login_agent" text NULL,
	"last_login_date" numeric NULL,
	"last_failure_origin" numeric NULL,
	"last_failure_agent" numeric NULL,
	"last_failure_date" numeric NULL,
	"modules_array" text NULL
);

*/

class giSecurity {
	
	// general singleton constructor
	public function __construct() {
		
		// general configuration
		$this->Table				= 'Accounts';
		$this->Cookie				= 'giSecurity';
		$this->Salt					= 'Uç!èsdH7èsb:=0)qn&hsbbWK&ç8wsNAKJQsbbQXN198nh%sll-sJ&';
		$this->Time					= time();
		$this->LoginField			= 'login';
		$this->PasswordField		= 'password';
		$this->SessionLifetime		= 72;
		$this->WaitingDelay			= 60;
		$this->Algorithm			= 'sha512';
		
		// authentication informations
		$this->Auth 				= new stdClass();
		$this->Auth->Id 			= null;
		$this->Auth->Login			= null;
		$this->Auth->Level 			= null;
		$this->Auth->Modules		= null;
		$this->Auth->Expiration 	= null;
		$this->Auth->Success 		= false;

		// redirection informations
		$this->URLs					= new stdClass();
		$this->URLs->Login			= null;
		$this->URLs->Logout			= null;
		$this->URLs->Home			= null;
		$this->URLs->From			= null;

	}

	// set the configuration
	public function setConfiguration($home_url,$login_url,$logout_url) {
		// configure the urls
		$this->URLs->Login				= $login_url;
		$this->URLs->Logout				= $logout_url;
		$this->URLs->Home				= $home_url;
	}

	// enforce security rules
	public function enforce($level=null,$module=null) {
	
		// if we want to login
		if(isset($_POST[$this->LoginField]) and isset($_POST[$this->PasswordField])) {
			// call the login method
			$this->login(
				$_POST[$this->LoginField],
				$_POST[$this->PasswordField]
			);
			// check if we haven't been logged in
			if(!$this->Auth->Success) {
				// access the output handler
				global $app;
				// remove the password (to prevent it from being logged)
				unset($_POST[$this->PasswordField]);
				// log the error
				$app->Logger->security('failed_login_attempt');
				// prepare a redirect
				$app->Response->redirectAfter($this->URLs->Login,3);
				// redirect with a 403
				$app->Response->error403('you_cannot_be_logged_in');
			}
			// else we have been logged in
			else {
				// access the app
				global $app;
				// remove the password (to prevent it from being logged)
				unset($_POST[$this->PasswordField]);
				// log this
				$app->Logger->info('user_has_logged_in');
			}	
		}	
		// if we want to directly access a page we must have a session cookie set
		elseif(isset($_COOKIE[$this->Cookie])) {
			// call the identification method
			$this->authenticate($_COOKIE[$this->Cookie]);
			// check if we haven't been logged in
			if(!$this->Auth->Success) {
				// access the app
				global $app;
				// log this
				$app->Logger->info('session_is_no_longer_valid');
				// remove cookies
				$this->killCookies();
				// prepare a redirect
				$app->Response->redirectAfter($this->URLs->Login,3);
				// redirect with a 403
				$app->Response->error403('session_is_no_longer_valid');	
			}
		}
		// if we are not logged in and didn't try to log in
		else {
			// access the logger
			global $app;
			// log this
			$app->Logger->notice('authentication_required');
			// prepare a redirect
			$app->Response->redirect($this->URLs->Login);
			// redirect with a 403
			$app->Response->error403('authentication_required');
		}
		// if a specific level is required
		if($level) {
			// check the level
			if(!$this->checkLevel($level)) {
				// access the logger
				global $app;
				// log this
				$app->Logger->security('level_not_authorized');
				// prepare a redirect
				$app->Response->redirect($this->URLs->Home);
				// redirect with a 403
				$app->Response->error403('level_not_authorized');			
			}
		}
		// if a specific module is required
		if($module) {
			// check the level
			if(!$this->checkModule($module)) {
				// access the logger
				global $app;
				// log this
				$app->Logger->security('module_not_authorized');
				// prepare a redirect
				$app->Response->redirect($this->URLs->Home);
				// redirect with a 403
				$app->Response->error403('module_not_authorized');	
			}
		}
	}
	
	public function getId() {
		// return directly
		return($this->Auth->Id);
	}
	
	public function getLogin() {
		// return directly
		return($this->Auth->Login);
	}
	
	public function getLevel() {
		// return directly
		return($this->Auth->Level);
	}
	
	public function getModules() {
		// return directly
		return($this->Auth->Modules);
	}
	
	public function getExpiration() {
		// return directly
		return($this->Auth->Expiration);
	}
	
	public function checkLevel($level) {
		// if no level
		if(!$this->Auth->Level) {
			// return false	
			return(false);
		}
		// if we reach the level
		if($this->Auth->Level <= $level) {
			// return false
			return(true);
		}
		// if we are not reaching the required level
		else {
			// return true
			return(false);	
		}
	}
	
	public function checkModule($module,$level=1) {
		// if we don't have the proper module and are not admin
		if(!in_array($module,$this->Auth->Modules) and $this->Auth->Level > $level) {
			// return false
			return(false);
		}
		// else we have the proper module are we are admin
		else {
			// return true
			return(true);	
		}
	}
		
	public function getPassword($password){
		// get the hash
		return($this->getChecksum($password));
	}
	
	public function closeSession() {
		// try to logout
		$this->logout();
		// redirect to the logout page
		die(header('Location: '.$this->URLs->Logout));
	}

	
	/* ALL PUBLIC METHODS ABOVE */
	/****************************/


	// returns a hash of the string with dual salt
	private function getChecksum($string) {
		// sha1 hash of string plus salts
		return(hash($this->Algorithm,"$this->Salt $string $this->Salt"));
	}
	
	// return a hash of the users signature
	private function getSignature() {
		// checksum using remote IP + useragent
		return($this->getChecksum($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']));
	}

	// this function deletes all cookies
	private function killCookies() {
		// we reset the cookies
		setcookie($this->Cookie,'',time() - 3600,'/');
	}

	// this function deletes a session
	private function killSession($id) {
		// access the app
		global $app;
		// reset fields in database
		return($app->Database->query()
		->update('accounts')
		->set(array(
			'session_key'=>'',
			'session_expiration_date'=>''
		))
		->where(array('id'=>$id))
		->execute());
		
	}

	// this function is called when the person wants to log out
	private function logout() {
		// try to authenticate first
		$this->enforce();
		// we delete all cookies
		$this->killCookies();
		// we reset the session
		$this->killSession($this->getId());
	}

	// this function is called when the person posted a login form
	private function login($login,$password) {
		// access the app
		global $app;
		// disable cache
		$app->Response->disableCache();
		// make sure the strings are not too long or too short
		if(strlen($login) < 3 or strlen($password) <= 3 or strlen($login) > 255 or strlen($password) > 255) {
			// log this
			$app->Logger->security('inputs_out_of_range');
			// lockdown
			$app->Response->error400();
		}
		// try to fetch the account
		list($account) = $app->Database->query()
		->select()
		->from('accounts')
		->where(array('login'=>$login))
		->execute();
		// if an account is found
		if($account) {
			// if the account is disabled
			if(!$account->get('is_enabled')) {
				// unset password
				$_POST[$this->PasswordField] = 'undisclosed';
				// log this
				$app->Logger->security('account_is_disabled');
				// lockdown
				$app->Response->error403('account_is_disabled');
			}
			// if the account has expired
			if($account->get('account_expiration_date') and $this->Time > $account->getRaw('account_expiration_date')) {
				// unset password
				$_POST[$this->PasswordField] = 'undisclosed';
				// log this
				$app->Logger->security('account_has_expired');
				// lockdown
				$app->Response->error403('account_has_expired');
			}
			// if the account has been forced by the same person in the last x seconds
			if($account->get('last_failure_origin') == $_SERVER['REMOTE_ADDR'] and $account->getRaw('last_failure_date') and 
			(($account->getRaw('last_failure_date') + $this->WaitingDelay) > $this->Time)) {
				// unset password
				$_POST[$this->PasswordField] = 'undisclosed';
				// log this
				$app->Logger->security('user_must_wait_before_trying_again');
				// redirect to the login page after a few second
				$app->Response->redirectAfter($this->URLs->Login,3);
				// output a 403 header
				$app->Response->error403('please_wait_a_moment_before_trying_again');
			}
			// if the password matchs
			if($account->get('password') == $this->getChecksum($password)) {
				// generate session expiration date
				$session_expiration_date= $this->Time + ($this->SessionLifetime * 3600);
				// generate session signature
				$session_key = $this->getChecksum($this->getSignature().$session_expiration_date.$login);
				// populate auth information
				$this->Auth->Success 	=	true;	
				$this->Auth->Id			= $account->get('id');
				$this->Auth->Login		= $account->get('login');
				$this->Auth->Level 		= $account->get('id_level');
				$this->Auth->Modules 	= $account->get('modules_array');
				$this->Auth->Exipration	= $session_expiration_date;
				// set session cookie
				setcookie($this->Cookie,$session_key,$session_expiration_date,'/');
				// insert the session signature and session expiration
				$account->set('session_key',$session_key);
				$account->set('session_expiration_date',$session_expiration_date);
				$account->set('last_login_agent',$_SERVER['HTTP_USER_AGENT']);
				$account->set('last_login_origin',$_SERVER['REMOTE_ADDR']);
				$account->set('last_login_date',$this->Time);							
				// save the account with its session set in
				$account->save();
			}
			// the password is wrong
			else {
				// update the user
				$account->set('last_failure_agent',$_SERVER['HTTP_USER_AGENT']);
				$account->set('last_failure_origin',$_SERVER['REMOTE_ADDR']);
				$account->set('last_failure_date',$this->Time);
				// save updated user
				$account->save();
				// unset password
				$_POST[$this->PasswordField] = 'undisclosed';
				// log this
				$app->Logger->security('wrong_password');
				// redirect to the login page after a few second
				$app->Response->redirectAfter($this->URLs->Login,3);
				// output a 403 header
				$app->Response->error403('wrong_password');
			}
		}
		// wrong login
		else {
			// unset password
			unset($_POST[$this->PasswordField]);
			// log this
			$app->Logger->security('wrong_login');
			// redirect to the login page after a few second
			$app->Response->redirectAfter($this->URLs->Login,3);
			// output a 403 header
			$app->Response->error403('wrong_password');
		}
	}
	
	// this function is called if the person has auth cookies set so we try to authenticate
	private function authenticate($session_key) {
		// access the app
		global $app;
		// get potential account
		list($account) = $app->Database->query()
		->select('session_key','session_expiration_date','account_expiration_date','id','login','id_level','modules_array')
		->from('accounts')
		->where(array('session_key'=>$session_key))
		->execute();
		// if an account with this session is is found
		if($account) {
			// if the expiration date of the session if in the future
			if($account->getRaw('session_expiration_date') > $this->Time) {
				// if the account itself has not expired yet or has no expiration date
				if(!$account->getRaw('account_expiration_date') or $account->getRaw('account_expiration_date') > $this->Time) {
					// if the dynamicaly generated session id still matches
					if($this->getChecksum($this->getSignature().$account->getRaw('session_expiration_date').$account->get('login')) == $session_key) {
						// we grant access and fill auth infos
						$this->Auth->Success	= true;	
						$this->Auth->Expiration	= $account->getRaw('session_expiration_date');
						$this->Auth->Id			= $account->get('id');
						$this->Auth->Login		= $account->get('login');
						$this->Auth->Level		= $account->get('id_level');
						$this->Auth->Modules	= $account->get('modules_array');
						// if the session expires within the next 6 hours
						if($account->getRaw('session_expiration_date') < ($this->Time + 21600) ) {
							// extend by 36 hours
							$account->set('session_expiration_date',$account->getRaw('session_expiration_date') + 36*3600);
							// update the session string
							$account->set('session_key',$this->getChecksum($this->getSignature().$account->getRaw('session_expiration_date').$account->get('login')));
							// save the extended session
							$account->save();
							// extend the cookie session
							setcookie($this->Cookie,$account->get('session_key'),$account->getRaw('session_expiration_date'),'/');
						}
					// session dynamic key missmatch
					} else { $this->killSession($account->get('id')); } 
				// account has expired
				} else { $this->killSession($account->get('id')); } 
			// session has expired
			} else { $this->killSession($account->get('id')); } 
		// no account found
		} else {} 
		
	}
	
}
?>