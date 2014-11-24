<?php

/*	

CREATE TABLE "Accounts" (
  "id" integer NULL PRIMARY KEY AUTOINCREMENT,
  "id_level" numeric NULL,
  "is_enabled" numeric NULL,
  "login" text NULL,
  "password" text NULL,
  "account_expiration_date" numeric NULL,
  "session_key" text NULL,
  "session_expiration_date" numeric NULL,
  "last_login_origin" text NULL,
  "last_login_agent" text NULL,
  "last_login_date" numeric NULL,
  "last_failure_origin" numeric NULL,
  "last_failure_agent" numeric NULL,
  "last_failure_date" numeric NULL,
  "rights_array" text NULL
);

*/

class giSecurity {

	/****************************/
	/* CONFIGURATION PARAMETERS */


	protected $configDatabase;			// (object)
	protected $configTableName;			// (string)
	protected $configSalt;				// (string)
	protected $configCurrentTime;		// (integer)
	protected $configLoginUrl;			// (string)
	protected $configHomeUrl; 			// (string) is this useful ?
	protected $configLogoutUrl; 		// (string) is this useful ?
	protected $configSessionLifetime;	// (integer) in hours
	protected $configWaitingDelay;		// (integer) in seconds
	protected $configPasswordLength;	// (integer)
	protected $configLoginCookie;		// (string)
	protected $configPasswordCookie;	// (string)
	protected $configSessionCookie;		// (string)
	protected $configPostLogin;			// (string)
	protected $configPostPassword;		// (string)
	
	/***********************************/
	/* AUTHENTIFIED SESSION PARAMETERS */
	
	private $authId;					//	(integer)	0 at start, the row id for the authed user
	private $authAccount;				//	(object)	false at start, return the database record
	private $authGranted;				//	(boolean)	false at start, true if we are authenticated
	private $authLogin;					//	(string)	login of the authed user
	private $authLevel;					//	(integer)	level of the authed user
	private $authModules;				//	(array)		modules of the authed user
	private $authExpiration;			//	(integer)	timestamp of the sessions expiration

	
	// general singleton constructor
	public function __construct() {
		
		// access the main database
		global $app;
	
		// class variables configuration
		$this->configDatabase			= (object)	$app->Core->Database;
		$this->configTableName			= (string)	'Accounts';
		$this->configSalt				= (string)	'cc696n2babBc1307bdcF30d69dEa8ce93c1307bd7ZfIzbKsqmP8ce93c';
		
		$this->configLoginCookie		= (string)	'giLogin';
		$this->configPasswordCookie		= (string)	'giCookie';
		$this->configSessionCookie		= (string)	'giSession';
		$this->configPostLogin			= (string)	'login';
		$this->configPostPassword		= (string)	'password';
		$this->configCurrentTime		= (integer)	time();
		$this->configSessionLifetime	= (integer)	72;
		$this->configWaitingDelay		= (integer)	60;
		$this->configPasswordLength		= (integer)	6;
		

		$this->authId			= (integer)	0;
		$this->authAccount		= (boolean)	false;
		$this->authLevel		= (integer)	99;
		$this->authExpiration	= (integer)	0;
		$this->authGranted		= (boolean)	false;
		$this->authLogin		= (string)	'not logged in';
		$this->authModules		= (array)	array();
		
		
		// general configuration
		$this->Database				= $app->Core->Database;
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

	public function setConfiguration($home_url,$login_url,$logout_url) {
	
		$this->configLoginUrl			= $home_url;
		$this->configHomeUrl			= $login_url;
		$this->configLogoutUrl			= $logout_url;
		
		// configure the class
		$this->URLs->Login				= $login_url;
		$this->URLs->Logout				= $logout_url;
		$this->URLs->Home				= $home_url;
		
	}

	/******************/
	/* PUBLIC METHODS */

	public function enforce($aRequiredLevel=null,$aRequiredModule=null) {
	
		// if we want to login
		if(isset($_POST[$this->configPostLogin]) and isset($_POST[$this->configPostPassword])) {
			// call the login method
			$this->tryLogin(
				$_POST[$this->configPostLogin],
				$_POST[$this->configPostPassword]
				);
			// check if we haven't been logged in
			if($this->authGranted == false) {
				// redirect to the login page
				header('Refresh: 3; url='.$this->configLoginUrl);
				// remove the password
				unset($_POST[$this->configPostPassword]);
				// lockdown
				$this->lockDown('you_cannot_be_logged_in');
			}
			// else we have been logged in
			else {
				// access the logger
				global $giLogger;
				// remove the password
				unset($_POST[$this->configPostPassword]);
				// log this
				$giLogger->info('user_has_logged_in');
			}	
		}	
		// if we want to directly access a page
		elseif(isset($_COOKIE[$this->configLoginCookie]) and isset($_COOKIE[$this->configPasswordCookie]) and isset($_COOKIE[$this->configSessionCookie])) {
			// call the identification method
			$this->tryIdentify(
				$_COOKIE[$this->configLoginCookie],
				$_COOKIE[$this->configPasswordCookie],
				$_COOKIE[$this->configSessionCookie]
				);
			// check if we haven't been logged in
			if($this->authGranted == false) {
				// redirect to the login page
				header('Refresh: 3; url='.$this->configLoginUrl);
				// remove cookies
				$this->killCookies();
				// lockdown
				$this->lockDown('your_session_has_expired');
			}
		}
			
		// if we are not logged in and didn't try to log in
		else {
			// access the logger
			global $giLogger;
			// log this
			$giLogger->info('unauthorized');
			// the access if forbidden
			header("HTTP/1.0 403 Forbidden");
			// redirect to the login page
			die(header('Location: '.$this->configLoginUrl));
			}
		// if a specific level is required
		if($aRequiredLevel) {
			// check the level
			if(!$this->checkSelfLevel($aRequiredLevel)) {
				// access the logger
				global $giLogger;
				// log this
				$giLogger->security('level_not_allowed');
				// lockdown
				$this->lockDown('level_not_allowed');
			}
		}
		// if a specific module is required
		if($aRequiredModule) {
			// check the level
			if(!$this->checkSelfModule($aRequiredModule)) {
				// access the logger
				global $giLogger;
				// log this
				$giLogger->security('module_not_allowed');
				// lockdown
				$this->lockDown('module_not_allowed');
			}
		}
	}
	
	public function getSelfAccount() {
		// return the account
		return($this->authAccount);	
	}
	
	public function getSelfId() {
		// return directly
		return($this->authId);
	}
	
	public function getSelfLogin() {
		// return directly
		return($this->authLogin);
	}
	
	public function getSelfLevel() {
		// return directly
		return($this->authLevel);
	}
	
	public function getSelfExpiration() {
		// return directly
		return($this->authExpiration);
	}
	
	public function checkSelfLevel($aRequiredLevel) {
		// if we are not reaching the required level
		if($this->authLevel > $aRequiredLevel) {
			// return false
			return(false);
		}
		// else we reach the level
		else {
			// return true
			return(true);	
		}
	}
	
	public function checkSelfModule($aRequiredModule) {
		// if we don't have the proper module and are not admin
		if(!in_array($aRequiredModule,$this->authModules) and $this->authLevel != 1) {
			// return false
			return(false);
		}
		// else we have the proper module are we are admin
		else {
			// return true
			return(true);	
		}
	}
		
	public function setUserPassword($aUserId,$aUserPassword){
		// if a user id is set
		if($aUserId) {
			// if a password is set 
			if(!$aUserPassword) {
				// generate a random password
				$aUserPassword = $this->generatePassword();
			}	
			// update the user with new password
			$this->configDatabase->update($this->configTableName,array(
				'password'	=> $this->getChecksum($aUserPassword)
			),array(
				'id'		=>$aUserId
			));
			// return the password
			return($aUserPassword);
		}
	}
	
	public function closeSession() {
		// try to logout
		$this->tryLogout();
		// redirect to the logout page
		die(header('Location: '.$this->configLogoutUrl));
	}

	
	/* ALL PUBLIC METHODS OVER */
	/***************************/


	// returns a hash of the string with dual salt
	private function getChecksum($aString) {
		// sha1 hash of string plus salts
		return(sha1($this->configSalt.$aString.$this->configSalt));
	}
	
	// return a hash of the users signature
	private function getSignature() {
		return($this->getChecksum($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']));
	}
	
	// formats associative arrays
	private function formatArray($anArray) {
		
		// prepare the returned string
		$aString = '';
		
		// if it's an array
		if(is_array($anArray)) {
			// for each element of the array
			foreach($anArray as $aKey => $aValue) {
				// append to the string
				$aString .= $aKey.'='.$aValue.';';
			}
		}
		
		// return the string
		return($aString);
			
	}
	
	// kills the application with a shutdown message and logs/report if needed
	private function lockDown($aShutdownReason,$report=false) {

		// access the output handler
		global $giOutput;
		
		// set the type
		$giOutput->setType('text');
		
		// output the error
		$giOutput->error403($aShutdownReason);
	
	}

	// this function generates a password
	private function generatePassword() {
		// get the password length configuration
		$passwordLength 			= (integer) $this->configPasswordLength;
		// prepare a list of characters
		$passwordCharacters 		= (string) 
			'abcdefghijklmnopqrstuvwxyz?+@&-,:_%=;!/.$[]*{}()#ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789?+@&-,:_%=;!/.$[]*{}()#';
		// get the length of this list
		$passwordCharactersLength 	= (integer) strlen($passwordCharacters) - 1;
		// initialize the password variable
		$generatedPassword 			= (string) null;
		// iterate for each random character
		for($i=0;$i<$passwordLength;$i++) {
			// genereate one character at a time
			$generatedPassword .= $passwordCharacters[rand(0,$passwordCharactersLength)];
		}
		// if the generated password does not contain chars + capital leters + number we retry
		if(!preg_match('#((?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{4,255})#',$generatedPassword)) {
			// if the generated password isn't strong enough generate again
			return($this->generatePassword());
		}
		else {
			// return the generated password
			return($generatedPassword);
		}
		
		}

	// this function deletes all cookies
	private function killCookies() {
		// we reset all cookies
		setcookie($this->configLoginCookie,'',time() - 3600,'/');
		setcookie($this->configPasswordCookie,'',time() - 3600,'/');
		setcookie($this->configSessionCookie,'',time() - 3600,'/');
	}

	// this function deletes a session
	private function killSession($aUserId) {
		// reset fields in database
		$this->configDatabase->update(
			$this->configTableName,
			array(
				'session_key'				=>'',
				'session_expiration_date'	=>''
			),
			array(
				'id'=>$aUserId
			)
		);
	}

	// this function is called when the person wants to log out
	private function tryLogout() {
		// try to authenticate first
		$this->enforceSecurity();
		// we delete all cookies
		$this->killCookies();
		// we reset the session
		$this->killSession($this->getSelfId());
	}

	// this function is called when the person posted a login form
	private function tryLogin($postedLogin,$postedPassword) {
		// make sure the strings are not too long or too short
		if(strlen($postedLogin) < 1 or strlen($postedPassword) < 1 or strlen($postedLogin) > 255 or strlen($postedPassword) > 255) {
			// access the logger
			global $giLogger;
			// log this
			$giLogger->security('post_inputs_compromised');
			// lockdown
			$this->lockDown('post_inputs_compromised');
		}
		// search for this user in the database
		list($foundAccount) = $this->configDatabase->select($this->configTableName,array('login'=>$postedLogin));
		// if an account is found
		if($foundAccount) {
			// if the account is disabled
			if(!$foundAccount->get('is_enabled')) {
				// access the logger
				global $giLogger;
				// unset password
				unset($_POST[$this->configPostPassword]);
				// log this
				$giLogger->security('account_is_disabled');
				// lockdown
				$this->lockDown('account_is_disabled');
			}
			// if the account has expired
			if($foundAccount->get('account_expiration_date') and $this->configCurrentTime > $foundAccount->getRaw('account_expiration_date')) {
				// access the logger
				global $giLogger;
				// unset password
				unset($_POST[$this->configPostPassword]);
				// log this
				$giLogger->security('account_has_expired');
				// lockdown
				$this->lockDown('account_has_expired');
			}
			// if the account has been forced by the same person in the last x seconds
			if($foundAccount->getRaw('last_failure_origin') == $_SERVER['REMOTE_ADDR'] and $foundAccount->getRaw('last_failure_date') and 
			($foundAccount->getRaw('last_failure_date') + $this->configWaitingDelay > $this->configCurrentTime)) {
				// access the gioutput
				global $giLogger,$giOutput;
				// log this
				$giLogger->security('user_must_wait_before_trying_again');
				// redirect to the login page after a few second
				$giOutput->redirectAfter($this->configLoginUrl,3);
				// output a 403 header
				$giOutput->error403('please_wait_a_moment_before_trying_again');
			}

			// if the password matchs
			if($foundAccount->get('password') == $this->getChecksum($postedPassword)) {
				// generate session expiration date
				$session_expiration_date= (integer) $this->configCurrentTime + $this->configSessionLifetime * 3600;
				// generate session signature
				$session_key			= (string) $this->getChecksum($this->getSignature().$session_expiration_date);
				// populate auth information
				$this->authGranted 		= (boolean)	true;	
				$this->authAccount		= (object)	$foundAccount;
				$this->authId			= (integer)	$foundAccount->get('id');
				$this->authLogin		= (string)	$foundAccount->get('login');
				$this->authLevel 		= (integer)	$foundAccount->get('id_level');
				$this->authExpiration	= (integer)	$session_expiration_date;
				$this->authModules 		= (array)	$foundAccount->get('rights_array');
				// set cookies
				setcookie($this->configLoginCookie,$this->getChecksum($foundAccount->get('login')),$session_expiration_date,'/');
				setcookie($this->configPasswordCookie,$this->getChecksum($foundAccount->get('password')),$session_expiration_date,'/');
				setcookie($this->configSessionCookie,$session_key,$session_expiration_date,'/');
				// insert the session signature and session expiration
				$foundAccount->set('session_key',$session_key);
				$foundAccount->set('session_expiration_date',$session_expiration_date);
				$foundAccount->set('last_login_agent',$_SERVER['HTTP_USER_AGENT']);
				$foundAccount->set('last_login_origin',$_SERVER['REMOTE_ADDR']);
				$foundAccount->set('last_login_date',$this->configCurrentTime);							
				// save the account with its session set in
				$foundAccount->save();
			}
			// the password is wrong
			else {
				// update the user
				$foundAccount->set('last_failure_agent',$_SERVER['HTTP_USER_AGENT']);
				$foundAccount->set('last_failure_origin',$_SERVER['REMOTE_ADDR']);
				$foundAccount->set('last_failure_date',$this->configCurrentTime);
				// save the account
				$foundAccount->save();
				// access the logger
				global $giLogger,$giOutput;
				// unset password
				unset($_POST[$this->configPostPassword]);
				// log this
				$giLogger->security('wrong_password');
				// redirect to the login page after a few second
				$giOutput->redirectAfter($this->configLoginUrl,3);
				// output a 403 header
				$giOutput->error403('wrong_password');
			}
		}
		// wrong login
		else {
			// access the logger
			global $giLogger,$giOutput;
			// unset password
			unset($_POST[$this->configPostPassword]);
			// log this
			$giLogger->security('wrong_login');
			// redirect to the login page after a few second
			$giOutput->redirectAfter($this->configLoginUrl,3);
			// output a 403 header
			$giOutput->error403('wrong_password');
		}	
	}
	
	// this function is called if the person has auth cookies set so we try to authenticate
	private function tryIdentify($loginCookie,$passwordCookie,$sessionCookie) {
		// get the list of all users
		$accountList = $this->configDatabase->select($this->configTableName,array('session_key'=>$sessionCookie));
		// iterate trhu the whole list
		foreach($accountList as $anAccount) {
			// if the user matchs
			if($this->getChecksum($anAccount->get('login')) == $loginCookie) {
				// if the passhash matchs
				if($this->getChecksum($anAccount->get('password')) == $passwordCookie) {
					// if the session id matchs
					if($anAccount->get('session_key') == $sessionCookie) {
						// if the session has not yet expired
						if($anAccount->getRaw('session_expiration_date') > $this->configCurrentTime) {
							// if the account has itself not expired yet or has no expiration date
							if(!$anAccount->getRaw('account_expiration_date') or $anAccount->getRaw('account_expiration_date') > $this->configCurrentTime) {
								// if the dynamicaly generated session id still matches
								if($this->getChecksum($this->getSignature().$anAccount->getRaw('session_expiration_date')) == $sessionCookie) {
									// we grant access and fill auth infos
									$this->authGranted 		= (boolean)	true;	
									$this->authAccount		= (object)	$anAccount;
									$this->authId			= (integer)	$anAccount->get('id');
									$this->authLogin		= (string)	$anAccount->get('login');
									$this->authLevel 		= (integer)	$anAccount->get('id_level');
									$this->authExpiration	= (integer)	$anAccount->getRaw('session_expiration_date');
									$this->authModules 		= (array)	$anAccount->get('rights_array');
									// if the session expires within the next half hour
									if($anAccount->getRaw('session_expiration_date') < ($this->configCurrentTime + 1800) ) {
										// extend by 36 hours
										$anAccount->set('session_expiration_date',$anAccount->getRaw('session_expiration_date') + 36*3600);
										// update the session string
										$anAccount->set('session_key',$this->getChecksum($this->getSignature().$anAccount->getRaw('session_expiration_date')));
										// save the extended session
										$anAccount->save();
										// extend the cookie user
										setcookie($this->configLoginCookie,$loginCookie,$anAccount->getRaw('session_expiration_date'),'/');
										// extend the cookie pass
										setcookie($this->configPasswordCookie,$passwordCookie,$anAccount->getRaw('session_expiration_date'),'/');
										// extend the cookie session
										setcookie($this->configSessionCookie,$anAccount->get('session_key'),$anAccount->get('session_expiration_date'),'/');
										}
									} else { $this->killCookies(); $this->killSession($anAccount->get('id')); }	// the session signature mismatch
								} else { $this->killCookies(); $this->killSession($anAccount->get('id')); } 	// the account has expired
							} else { $this->killCookies(); $this->killSession($anAccount->get('id')); } 		// the session has expired
						} else { $this->killCookies(); $this->killSession($anAccount->get('id')); } 			// the session cookie/dbentry mismatch
					} else { $this->killCookies(); $this->killSession($anAccount->get('id')); } 				// the password cookie/dbentry mismatch
				}
			}
		}


	/* PRIVATE METHODS ARE DECLARED ABOVE
	********************************** */
	
}
?>