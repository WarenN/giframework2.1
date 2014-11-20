<?php

// helper that provides some simple methods simplifying records manipulation 
class giDatabase {

	// handles everything about the database
	protected $Database;	
	// handles everything about the cache
	protected $Cache;
	
	// main constructor
	public function __construct() {
		
		// initialise empty properties
		$this->Database		= array('handle'=>null);	
		$this->Cache		= array('handle'=>null);

	}
	
	// set the configuration parameters
	public function setConfiguration(
	
		// set the parameters for this database
		$database_driver	=null,
		$database_database	=null,
		$database_user		=null,
		$database_pass		=null,
		$database_host		=null,
		
		// set the parameters for caching SQL queries
		$cache_enabled		=null,
		$cache_prefix		=null,
		$cache_host			=null,
		$cache_port			=null
		
	) {
	
		// set the parameters for this database
		$this->Database['driver']	= $database_driver;
		$this->Database['database']	= $database_database;
		$this->Database['username']	= $database_user;
		$this->Database['password']	= $database_pass;
		$this->Database['hostname']	= $database_host;
		
		// set the parameters for caching SQL queries
		$this->Cache['enabled']		= $cache_enabled;
		$this->Cache['prefix']		= $cache_prefix;
		$this->Cache['hostname']	= $cache_host;
		$this->Cache['port']		= $cache_port;
		
	}

	// upon destruction of the object
	public function __destruct() {
		// close all connexions
		$this->disconnect();
	}
	
	// connect database and cache (if any)
	private function connect() {

		// depending on the driver, use the right method to connect to the database
		switch($this->Database['driver']) {
			// in case the driver is SQLite
			case 'sqlite':
				// open a PDO connexion
				$this->Database['handle']	= new PDO('sqlite:'.$this->Database['database']);
			break;
			// in case the driver is MySQL
			case 'mysql':
				// open a PDO connexion
				$this->Database['handle']	= new PDO(
					'mysql:dbname='.$this->Database['database'].';host='.$this->Database['hostname'],
					$this->Database['username'],
					$this->Database['password']
				);
			break;
			// the driver is unknown or not supported
			default:
				// throw an exception
				Throw new Exception('giDatabase->connect() : This PDO driver is not supported');
			break;
		}
		if(!$this->Database['handle']) {
			// throw an exception
			Throw new Exception("giDatabase->connect() : failed to connect to PDO/{$this->Database['driver']} database");	
		}
	}
	
	// request an advanced query
	public function query() {
	
		// if we don't have a connexion yet
		if(!$this->Database['handle']) {
			// connect to the database
			$this->connect();	
		}
		// if cache is enabled
		if($this->Cache['enabled']) {
			// connect the cache
			$this->Cache['handle'] = new Memcache();
			// configure the memcache client
			$connected = $this->Cache['handle']->connect($this->Cache['hostname'],$this->Cache['port']);
			// if connection failed
			if(!$connected) {
				// throw an exception
				Throw new Exception('giDatabase->connect() : failed to connect to Memcache');	
			}
		}

		// return a new query object
		return(new giQuery($this->Database,$this->Cache));
		
	}
	
	// close all openned connexions
	public function disconnect() {
		// if a database connexion is opened
		if($this->Database['handle']) {
			// close it
			unset($this->Database['handle']);
		}
		// if a cache connexion is opened
		if($this->Cache['handle']) {
			// close it
			$this->Cache['handle']->close();
		}
	}
	
	// this will flush all the cached queries
	public function flushCache() {
		// the cache is enabled
		if($this->Cache['enabled']) {
			// flush the cache
			return($this->Cache['handler']->flush());	
		}
	}
	
}

?>