<?php
/* 

TODOLIST:
- make the ->fts method compatible with MySQL full text search engine.

CONNECT ON THE FLY !!!
IF PDO HANDLE IS MISSING BEFORE A QUERY, CONNECT THE DB
BUT DON'T CONNECT AS DEFAULT


*/

// helper that provides some simple methods simplifying records manipulation 
class giDatabase {

	
	const FETCH_CLASS = 'giDatabaseRecord';

	protected $Database;	// handles everything about the database
	protected $Cache;		// handles everything about the cache

	public function __construct() {
		// initialise empty properties
		$this->Database		= array('handle'=>null);	
		$this->Cache		= array('handle'=>null);
	}
	
	// set the configuration parameters
	public function setConfiguration(
	
		// set the parameters for this database
		$database_driver	='sqlite',
		$database_database	='../private/data/sqlite/common.sqlite',
		$database_user		='',
		$database_pass		='',
		$database_host		='127.0.0.1',
		
		// set the parameters for caching SQL queries
		$cache_prefix		='giCache_',
		$cache_host			='127.0.0.1',
		$cache_port			='12211'
		
	) {
	
		// set the parameters for this database
		$this->Database['driver']	= $database_driver;
		$this->Database['database']	= $database_database;
		$this->Database['username']	= $database_user;
		$this->Database['password']	= $database_pass;
		$this->Database['hostname']	= $database_host;
		
		// set the parameters for caching SQL queries
		$this->Cache['prefix']		= $cache_prefix;
		$this->Cache['hostname']	= $cache_host;
		$this->Cache['port']		= $cache_port;
		
	}

	// upon destruction of the object
	public function __destruct() {
		// close all connexions
		$this->disconnect();
	}

	/*********************************************************************************/
	
	// connect database and cache
	private function connect() {
		// access the configuration
		global $giConfiguration;
		// depending on the driver, use the right method to connect to the database
		switch($this->Database['driver']) {
			// in case the driver is SQLite
			case 'sqlite':
				// open a PDO connexion
				$this->Database['handle']	= new PDO('sqlite:'.$this->Database['database']);
				// set the proper quote symbol
				$this->quote 	= '"';
			break;
			// in case the driver is MySQL
			case 'mysql':
				// open a PDO connexion
				$this->Database['handle']	= new PDO('mysql:dbname='.$this->Database['database'].';host='.$this->hostname,$this->username,$this->password);
				// set the proper quote symbol
				$this->quote 	= '`';
			break;
			// de driver is unknown or not supported
			default:
				// access the logger
				global $giLogger;
				// log this
				$giLogger->error('This driver is not supported '.$this->Database['driver']);
				// access the output
				global $giOutput;
				// output a fatal error
				$giOutput->error500('This driver is not supported');
			break;
		}
		// depending on the database cache engine ot use
		if($giConfiguration->isMemcacheEnabled()) {
			// instanciate a memcache client
			$this->Cache['handle'] = new Memcache();
			// configure the memcache client
			$this->Cache['handle']->connect($this->Cache['hostname'],$this->Cache['port']);
		}
	}
	
	// close all openned connexions
	private function disconnect() {
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
	
	
	/*********************************************************************************/

	private function serializator($associativeArray) {
		foreach($associativeArray as $anElementKey => $anElementValue) {
			if(strpos($anElementKey,'serialized') !== false or strpos($anElementKey,'array') !== false) {
				$associativeArray[$anElementKey] = (string)	serialize($anElementValue);
			}	
		}
		return($associativeArray);
	}
	
	/*********************************************************************************/
	
	private function timestamper($associativeArray) {
		foreach($associativeArray as $anElementKey => $anElementValue) {
			if(strpos($anElementKey,'date') !== false and strpos($anElementValue,'/') !== false) {
				list($day,$month,$year) = explode('/',$anElementValue);
				$timestamp = mktime(0,0,1,$month,$day,$year);
				$associativeArray[$anElementKey] = (string)	$timestamp;
			}	
		}
		return($associativeArray);
	}
	
	
	/*********************************************************************************/
	
	private function buildTable($table) {
		return($this->quoteColumn($table));
	}
	
	/*********************************************************************************/
	
	private function buildColumns($columns) {
		if(!$columns or !is_array($columns)) {
			return('*');	
		}
		else {
			$actualColumns = array();
			foreach($columns as $aColumn) {
				$actualColumns[] = $this->quoteColumn($aColumn);
			}
			return(implode(', ',$actualColumns));	
		}
	}
	
	/*********************************************************************************/
	
	private function buildConditions($conditions,$operator=null) {
		if(!in_array($operator,array('AND','OR'))) {
			$operator = 'AND';
		}
		if(is_array($conditions)) {
			$conditionsArray		= array();
			$conditionsValues		= array();
			foreach($conditions as $aConditionColumn => $aConditionValue) {
				$conditionsArray	[]= (string)	$this->quoteColumn($aConditionColumn).' = ?';
				$conditionsValues	[]= (string)	$aConditionValue;
			}
			$conditions 			= (string)	' WHERE ( '.implode(' '.$operator.' ',$conditionsArray).' )';
			$return					= array($conditions,$conditionsValues);
			return($return);
		}
		else {
			$return					= array('',array());
			return($return);
		}
	}

	/*********************************************************************************/
	
	private function buildSearchs($conditions,$operator=null) {
		if(!in_array($operator,array('AND','OR'))) {
			$operator = 'AND';
		}
		if(is_array($conditions)) {
			$conditionsArray		= array();
			$conditionsValues		= array();
			foreach($conditions as $aConditionColumn => $aConditionValue) {
				if(strlen($aConditionValue) == 1) {
					$conditionsArray	[]= (string)	$this->quoteColumn($aConditionColumn).' LIKE ?';
					$conditionsValues	[]= (string)	$aConditionValue.'%';
				}
				else {
					$conditionsArray	[]= (string)	$this->quoteColumn($aConditionColumn).' LIKE ?';
					$conditionsValues	[]= (string)	'%'.$aConditionValue.'%';
				}
					}
			$conditions 			= (string)	' WHERE ( '.implode(' '.$operator.' ',$conditionsArray).' )';
			$return					= array($conditions,$conditionsValues);
			return($return);
		}
		else {
			$return					= array('',array());
			return($return);
		}
	}
	
	/*********************************************************************************/
	
	private function buildFinds($conditions,$operator=null) {
		
		// if the operator is invalid
		if(!in_array($operator,array('AND','OR'))) {
			// force an AND operator
			$operator = 'AND';
		}
		// if conditions are an array
		if(is_array($conditions)) {
			$conditionsArray	= array();
			$conditionsValues	= array();
			$ConditionCount		= 0;
			// for each specified condition
			foreach($conditions as $aCondition) {
				// if the parameter array contains 3 parameter but the first is not an operator
				if(count($aCondition) == 3 && !in_array($aCondition[0],array('AND','OR')) ) {
				// le descripteur de condition contient 3 parametres ET le 1er parametre n'est ni AND ni OR
					list( $ConditionColumn,$ConditionOperator,$ConditionValue ) = $aCondition;
					// utiliser l'operateur par defaut
					$ConditionChain = $operator ;
				} elseif( count($aCondition) == 4 && in_array($aCondition[0],array('AND','OR')) ) {
				// le descripteur de condition contient 4 parametres ET le 1er parametre est AND ou OR
					list( $ConditionChain,$ConditionColumn,$ConditionOperator,$ConditionValue ) = $aCondition;
				} else {
				// le descripteur de condition contient 3 parametres ET le 1er parametre est AND ou OR
				// il manque un parametre !! oui mais lequel ? je sais pas alors j'ignore la condition na!
					$ConditionChain = NULL ;
					$ConditionColumn = NULL ;
					$ConditionOperator = NULL ;
					$ConditionValue = NULL ;
				}
				// si c'est la 1ere condition supprimer l'operateur de chainage des condition
				if( $ConditionCount == 0 ) { $ConditionChain = NULL ; }
				$ConditionCount++;
								if(!is_array( $ConditionValue ) ) {
					$v1 = $ConditionValue;
					$v2 = NULL ;
				} else {
					$v1 = isset($ConditionValue[0]) ? $ConditionValue[0] : NULL ;
					$v2 = isset($ConditionValue[1]) ? $ConditionValue[1] : NULL ;
				}
						$ConditionOperator = strtoupper(trim($ConditionOperator));
						$not = strpos($ConditionOperator, "NOT");
				if( $not !== false ) {
					$not = " NOT ";
					$ConditionOperator = str_replace( array("NOT "," NOT") , "", $ConditionOperator );
				} else { $not = ''; }
				// depending on the operator
				switch( $ConditionOperator ){
					case '=':
					case '!=':
					case '<>':
					case '<=':
					case '>=':
					case '<':
					case '>':
						$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quoteColumn($ConditionColumn).' '.$ConditionOperator.' ?';
						$conditionsValues	[]= (string)	$v1;
						break;
									case 'IS':
						if( strtoupper($v1) == "NULL") {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quoteColumn($ConditionColumn).' '.$ConditionOperator.$not.' NULL';
						} else {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quoteColumn($ConditionColumn).' '.$ConditionOperator.$not.' ?';
							$conditionsValues	[]= (string)	$v1;
						}
						break;
	
					case 'LIKE':
						$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quoteColumn($ConditionColumn).' '.$not.$ConditionOperator.' ?';
						$conditionsValues	[]= (string)	'%'.$v1.'%';
						break;
	
					case 'START WITH':
						$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quoteColumn($ConditionColumn).$not.' LIKE ?';
						$conditionsValues	[]= (string)	$v1.'%';
						break;
	
					case 'END WITH':
						$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quoteColumn($ConditionColumn).$not.' LIKE ?';
						$conditionsValues	[]= (string)	'%'.$v1;
						break;
	
					case 'BETWEEN':
						if( $v1 != NULL && $v2 != NULL ){
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quoteColumn($ConditionColumn).$not.' BETWEEN ? AND ?';
							$conditionsValues	[]= (string)	$v1;
							$conditionsValues	[]= (string)	$v2;
	
						} elseif( $v1 != NULL && $v2 == NULL  ) {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quoteColumn($ConditionColumn).' >= ?';
							$conditionsValues	[]= (string)	$v1;
	
						} elseif( $v1 == NULL && $v2 != NULL  ) {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quoteColumn($ConditionColumn).' <= ?';
							$conditionsValues	[]= (string)	$v2;
						}
						break;

					// bitwise AND
					case '&':
						if( strtoupper($v1) == "NULL") {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quoteColumn($ConditionColumn).' '.$ConditionOperator.$not.' NULL';
						} else {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quoteColumn($ConditionColumn).' '.$ConditionOperator.$not.' ?';
							$conditionsValues	[]= (string)	$v1;
						}
						break;
									// bitwise OR (inclusive or)
					case '|':
						if( strtoupper($v1) == "NULL") {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quoteColumn($ConditionColumn).' '.$ConditionOperator.$not.' NULL';
						} else {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quoteColumn($ConditionColumn).' '.$ConditionOperator.$not.' ?';
							$conditionsValues	[]= (string)	$v1;
						}
						break;

					// bitwise XOR (exclusive or)
					case '^':
						if( strtoupper($v1) == "NULL") {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quoteColumn($ConditionColumn).' '.$ConditionOperator.$not.' NULL';
						} else {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quoteColumn($ConditionColumn).' '.$ConditionOperator.$not.' ?';
							$conditionsValues	[]= (string)	$v1;
						}
						break;
				}
			}
			// build the whole query
			$conditions = implode(' ',$conditionsArray);
			// remove first and last operators
			if( substr($conditions, 0,4) == 'AND ' ){
				$conditions = substr($conditions, 4);
			} elseif( substr($conditions, 0,3) == 'OR ' ){
				$conditions = substr($conditions, 3);
			}
			$conditions 			= (string)	'WHERE ( '.$conditions.' )';
			$return					= array($conditions,$conditionsValues);
			return($return);
		}
		// conditions are not an array we cannot return jack
		else {
			$return					= array('',array());
			return($return);
		}
	}

	/*********************************************************************************/

	private function buildInsert($associativeArray) {
		$columns					= array();
		$values						= array();
		foreach($associativeArray as $insertColumn => $insertValue) {
			$columns				[]= (string)	$insertColumn;
			$values					[]= $insertValue;
		}
		$columns					= (string)	' ( '.$this->quote.implode($this->quote.' , '.$this->quote,$columns).$this->quote.' ) ';
		$return						= array($columns,$values);
		return($return);
	}

	/*********************************************************************************/

	private function buildPlaceholders($array) {
		$placeholders				= array();
		foreach($array as $anEntry) {
			$placeholders			[]= (string)	'?';
		}
		$return						= (string)	' ( '.implode(' , ',$placeholders).' ) ';
		return($return);
	}

	/*********************************************************************************/
	
	private function buildUpdates($associativeArray) {
		$updates					= array();
		$values						= array();
		foreach($associativeArray as $updateColumn => $updateValue) {
			$updates				[]= (string)	' '.$this->quoteColumn($updateColumn).' = ? ';
			$values					[]= $updateValue;
		}
		$updates					= (string)	' SET'.implode(' , ',$updates);
		$return						= array($updates,$values);
		return($return);
	}

	/*********************************************************************************/

	private function buildOrderBy($orderby) {
		$return = '';
		if(count($orderby) == 0){
			return $return;
		}
		if(is_array($orderby)){
			$orderArray        = array();
			foreach($orderby as $aColumnName => $aSortOrder){
				$orderArray [] = $this->quoteColumn($aColumnName).' '.$aSortOrder;
			}
			$return = ' ORDER BY '.implode(', ',$orderArray);
		}
		return $return;
	}

	/*********************************************************************************/

	private function buildLimitTo($limitto) {
		$return = '';
		if(!is_array($limitto)){
			return $return;
		}
		if($limitto[0] !== null and $limitto[1] !== null){
			$return = ' LIMIT  '.$limitto[0].' , '.$limitto[1];
		}
		return $return;
	}
	
	/*********************************************************************************/
	
	private function buildFTS($search,$table,$column) {
		// define the columns to search into
		$search_columns = array();
		// if no search column is specified
		if(!$column or !is_string($column)) {
			// use the table name instead
			$search_columns[] = $table; 
		}
		else {
			// populate the search columns array
			$search_columns[] = $column;
		}
		// if only one column is to be searched
		if(count($search_columns) == 1) {
			$fts = ' WHERE '.$this->quoteColumn($search_columns[0]).' MATCH '.'?';
		}
		else {
			foreach($search_columns as $aSearchId => $aSearchColumn) {
				$search_columns[$aSearchId] = $this->quoteColumn($aSearchColumn);
			}
			$fts = ' WHERE '.implode(', ',$search_columns).' MATCH '.'?';
		}
		return($fts);
	}
	
	/*********************************************************************************/
	
	private function quote($string) {
		// return a string properly quoted without any dangerous symbols
		return($this->Database['handle']->quote($string));
	
	}
	
	private function quoteColumn($string) {
		// return a string simply quoted according to database preferences
		return($this->quote.$string.$this->quote);
	}
	
	/*********************************************************************************/
	
	private function generateQueryHash($queryElements) {
		// create a signature for this request
		return(md5(json_encode($queryElements)));
	}
	
	public function updateOutdated($aTable) {
		// access configuration
		global $giConfiguration;
		// if caching is enabled
		if($giConfiguration->isMemcacheEnabled()) {
			// try to update
			$oudatedUpdate = $this->Cache['handle']->replace($this->Cache['prefix'].'_lu_'.$aTable,time());	
			// if the update failed
			if(!$outdatedUpdate) {
				// set the last modification date fot this table
				$this->Cache['handle']->set($this->Cache['prefix'].'_lu_'.$aTable,time());
			}	
		}	
	}
	
	private function isInCache($queryElements,$aTable,$lagTolerance) {
		// access configuration
		global $giConfiguration;
		// the cache is enabled
		if($giConfiguration->isMemcacheEnabled()) {
			// generate a hash for this specific query
			$aQueryHash = $this->generateQueryHash($queryElements);
			// if in debug mode
			if($this->debug) {
				// dump the cache query
				var_dump('get:'.$aQueryHash);
			}
			// get the last cached query
			$lastCachedQuery = $this->Cache['handle']->get($this->Cache['prefix'].'_qt_'.$aQueryHash);
			// if there is no cached query
			if(!$lastCachedQuery) {
				// nothing to return
				return(null);	
			}
			// if there is no table specified we have no idea what's going on
			if(!$aTable) {
				// nothing to return from the cache
				return(null);	
			}
			// check the last table update
			$lastTableUpdate = $this->Cache['handle']->get($this->Cache['prefix'].'_lu_'.$aTable);
			// if there is no lag tolerance
			if(!$lagTolerance or $lagTolerance == 0) {
				// if we don't know the last update date of the able
				if(!$lastTableUpdate) {								// set the last modification date for the next time
					$this->Cache['handle']->set($this->Cache['prefix'].'_lu_'.$aTable,time());
					// returned the last cached query
					return($this->Cache['handle']->get($this->Cache['prefix'].'_qd_'.$aQueryHash));
				}
				// if the cached query is posterior to the last table update
				if($lastCachedQuery > $lastTableUpdate) {
					// get the cached data and return it
					return($this->Cache['handle']->get($this->Cache['prefix'].'_qd_'.$aQueryHash));	
				}
				// the last cached query is anterior to the last table update
				else {
					// nothing coherent to return
					return(null);
				}
			}
			// else there is a lag tolerance
			else {
				// if the last cached query is fresh enough
				if($lastCachedQuery + $lagTolerance > time()) {
					// get the cached data and return it
					return($this->Cache['handle']->get($this->Cache['prefix'].'_qd_'.$aQueryHash));
				}
				// else the last cached query is too old
				else {
					// nothing to return
					return(null);	
				}
			}
		}
		// cache is disabled
		else {
			// nothing to return
			return(null);
		}
	}
	
	private function putInCache($queryElements,$queryResult) {
		// access configuration
		global $giConfiguration;
		// the cache is enabled
		if($giConfiguration->isMemcacheEnabled()) {
			// generate the query hash
			$aQueryHash = $this->generateQueryHash($queryElements);
			// if in debug mode
			if($this->debug) {
				// dump
				var_dump('set:'.$aQueryHash);
			}
			// if the query was a passthru query we have an object and not an array
			if(is_object($queryResult) and get_class($queryResult) == 'PDOStatement') {
				// declare the actual results array
				$queryResultArray = array();
				// we must iterate
				foreach($queryResult as $aResult) {
					// push the result in the array
					$queryResultArray[] = $aResult;
				}
				// replace the object by the array
				$queryResult = $queryResultArray;
			}
			// put the query result in cache
			$seta= $this->Cache['handle']->set($this->Cache['prefix'].'_qd_'.$aQueryHash,$queryResult);
			// set the query time
			$setb= $this->Cache['handle']->set($this->Cache['prefix'].'_qt_'.$aQueryHash,time());
			// return the query result
			return($queryResult);
		}
	}

	/*********************************************************************************/
	
	public function flushCache() {
		// access configuration
		global $giConfiguration;
		// the cache is enabled
		if($giConfiguration->isMemcacheEnabled()) {
			// flush the cache
			return($this->Cache['handler']->flush());	
		}
	}
	
	public function delete($atable,$conditions,$operator=null) {
		// if we don't have a connexion yet
		if(!$this->Database['handle']) {
			// connect to the database
			$this->connect();
		}
		if(count($conditions) == 0) { return(false); }
		list($conditions,$values)	= (array)	$this->buildConditions($conditions,$operator);
		$table						= (string)	$this->buildTable($atable);
		$query						= (string)	'DELETE FROM '.$table.$conditions;
		$prepare					= (object)	$this->Database['handle']->prepare($query);
		$execute					= (object)	$prepare->execute($values);
		$return						= (integer)	$prepare->rowCount();
		if($return) {
			$this->updateOutdated($atable);
		}
		// return the number of affected rows
		return($return);
	}
	
	public function select($atable,$conditions=null,$operator=null,$orderby=null,$limitto=null,$columns=null,$lag=null) {
		// if we don't have a connexion yet
		if(!$this->Database['handle']) {
			// connect to the database
			$this->connect();	
		}
		$queryElements				= array($atable,$conditions,$operator,$orderby,$limitto,$columns);
		$cachedData 				= $this->isInCache($queryElements,$atable,$lag);
		if($cachedData !== null) {
			return($cachedData);
		}
		if(count($conditions) == 0) { $conditions= null; }
		list($conditions,$values)	= (array)	$this->buildConditions($conditions,$operator);
		$orderby					= (string)	$this->buildOrderBy($orderby);
		$limitto					= (string)	$this->buildLimitTo($limitto);
		$columns					= (string)	$this->buildColumns($columns);
		$table						= (string)	$this->buildTable($atable);
		$query						= (string)	'SELECT '.$columns.' FROM '.$table.$conditions.$orderby.$limitto;
		$prepare					= (object)	$this->Database['handle']->prepare($query);
		$execute					= (object)	$prepare->execute($values);
		$fetch						= (array)	$prepare->fetchAll(PDO::FETCH_CLASS, self::FETCH_CLASS, array($atable,$this->Database['handle'],$this->quote));
		$this->putInCache($queryElements,$fetch);
		// return an array of database-connected-objects
		return($fetch);
	}
	
	public function search($atable,$conditions,$operator=null,$orderby=null,$limitto=null,$columns=null,$lag=null) {
		// if we don't have a connexion yet
		if(!$this->Database['handle']) {
			// connect to the database
			$this->connect();	
		}
		$queryElements				= array($atable,$conditions,$operator,$orderby,$limitto,$columns);
		$cachedData 				= $this->isInCache($queryElements,$atable,$lag);
		if($cachedData !== null) {
			return($cachedData);
		}
		if(count($conditions) == 0) { $conditions= null; }
		list($conditions,$values)	= (array)	$this->buildSearchs($conditions,$operator);
		$orderby					= (string)	$this->buildOrderBy($orderby);
		$limitto					= (string)	$this->buildLimitTo($limitto);
		$table						= (string)	$this->buildTable($atable);
		$columns					= (string)	$this->buildColumns($columns);
		$query						= (string)	'SELECT '.$columns.' FROM '.$table.$conditions.$orderby.$limitto;
		$prepare					= (object)	$this->Database['handle']->prepare($query);
		$execute					= (object)	$prepare->execute($values);
		$fetch						= (array)	$prepare->fetchAll(PDO::FETCH_CLASS, self::FETCH_CLASS, array($atable,$this->Database['handle'],$this->quote));
		$this->putInCache($queryElements,$fetch);
		// return an array of database-connected-objects
		return($fetch);	
	}
	/*
	 *  FTS (full text search)
	 */
	public function fts($atable,$search_columns,$search_value,$orderby=null,$limitto=null,$columns=null) {
		// if we don't have a connexion yet
		if(!$this->Database['handle']) {
			// connect to the database
			$this->connect();	
		}
		// sqlite implementation only
		if($this->Database['driver'] != 'sqlite') {
			return(false);
		}
		// if the search is empty we refuse to search
		if(!trim($search_value)) {
			return(array());
		}
		$conditions 				= (string)	$this->buildFTS($search_value,$atable,$search_columns);
		$orderby					= (string)	$this->buildOrderBy($orderby);
		$limitto					= (string)	$this->buildLimitTo($limitto);
		$table						= (string)	$this->buildTable($atable);
		$columns					= (string)	$this->buildColumns($columns);
		$query						= (string)	'SELECT '.$columns.' FROM '.$table.$conditions.$orderby.$limitto;
		$this->dumpQuery($query,$search_value);
		$prepare					= (object)	$this->Database['handle']->prepare($query);
		$execute					= (object)	$prepare->execute(array($search_value));
		$fetch						= (array)	$prepare->fetchAll(PDO::FETCH_CLASS, self::FETCH_CLASS, array($atable,$this->Database['handle'],$this->quote));
		return($fetch);
	}
	
	/*
	 *  find (JJL addon)
	 */
	public function find($atable,$conditions,$operator=null,$orderby=null,$limitto=null,$columns=null,$lag=null) {
		// if we don't have a connexion yet
		if(!$this->Database['handle']) {
			// connect to the database
			$this->connect();	
		}
		$queryElements				= array($atable,$conditions,$operator,$orderby,$limitto,$columns);
		$cachedData 				= $this->isInCache($queryElements,$atable,$lag);
		if($cachedData !== null) {
			return($cachedData);
		}

		if(count($conditions) == 0) { $conditions= null; }
		$table						= (string)	$this->buildTable($atable);
		list($conditions,$values)	= (array)	$this->buildFinds($conditions,$operator);
		$orderby					= (string)	$this->buildOrderBy($orderby);
		$limitto					= (string)	$this->buildLimitTo($limitto);
		$columns					= (string)	$this->buildColumns($columns);
		$query						= (string)	'SELECT '.$columns.' FROM '.$table.$conditions.$orderby.$limitto;
		$prepare					= (object)	$this->Database['handle']->prepare($query);
		$execute					= (object)	$prepare->execute($values);
		$fetch						= (array)	$prepare->fetchAll(PDO::FETCH_CLASS, self::FETCH_CLASS, array($atable,$this->Database['handle'],$this->quote));
		// return an array of database-connected-objects
		return($fetch);
	}
	
	public function update($atable,$values,$conditions,$operator=null) {
		// if we don't have a connexion yet
		if(!$this->Database['handle']) {
			// connect to the database
			$this->connect();	
		}
		if(count($conditions) == 0) { $conditions= null; }
		$values						= (array)	$this->serializator($values);
		$values						= (array)	$this->timestamper($values);
		list($conditions,$cvalues)	= (array)	$this->buildConditions($conditions,$operator);
		list($updates,$uvalues)		= (array)	$this->buildUpdates($values);
		$table						= (string)	$this->buildTable($atable);
		$query						= (string)	'UPDATE '.$table.$updates.$conditions;
		$prepare					= (object)	$this->Database['handle']->prepare($query);
		$execute					= (array)	array_merge($uvalues,$cvalues);
		$execute					= (boolean)	$prepare->execute($execute);
		$return						= (integer) $prepare->rowCount();
		if($return) {
			$this->updateOutdated($atable);
		}
		// return the number of affected rows
		return($return);
	}
	
	public function insert($atable,$values) {
		// if we don't have a connexion yet
		if(!$this->Database['handle']) {
			// connect to the database
			$this->connect();	
		}
		$id							= (string)	@$values['id'];
		$values						= (array)	$this->serializator($values);
		$values						= (array)	$this->timestamper($values);
		list($columns,$values)		= (array)	$this->buildInsert($values);
		$placeholders				= (string)	$this->buildPlaceholders($values);
		$table						= (string)	$this->buildTable($atable);
		$query						= (string)	'INSERT INTO '.$table.' '.$columns.' VALUES '.$placeholders;
		$prepare					= (object)	$this->Database['handle']->prepare($query);
		$execute					= (object)	$prepare->execute($values);
		/* Database specific hack */
		if($id == '') {
			$id = 	(integer)$this->Database['handle']->lastInsertId();
		}
		/* Database specific hack */
		$object						= (object)	$this->get($atable,$id);
		if($object) {
			$this->updateOutdated($atable);
		}
		// return the inserted object
		return($object);
	}
	
	public function get($atable,$id,$columns=null,$lag=null) {
		// if we don't have a connexion yet
		if(!$this->Database['handle']) {
			// connect to the database
			$this->connect();	
		}
		$queryElements				= array($atable,$id,$columns);
		$cachedData 				= $this->isInCache($queryElements,$atable,$lag);
		if($cachedData !== null) {
			return($cachedData);
		}
		$conditions					= array('id' => intval($id));
		list($conditions,$values)	= (array)	$this->buildConditions($conditions);
		$table						= (string)	$this->buildTable($atable);
		$columns					= (string)	$this->buildColumns($columns);
		$query						= (string)	'SELECT '.$columns.' FROM '.$table.$conditions;
		$prepare					= (object)	$this->Database['handle']->prepare($query);
		$execute					= (object)	$prepare->execute($values);
		list($object)				= (array)	$prepare->fetchAll(PDO::FETCH_CLASS, self::FETCH_CLASS, array($atable,$this->Database['handle'],$this->quote));
		$this->putInCache($queryElements,$object);
		// return the object
		return($object);
	}
	public function purge($atable) {
		// if we don't have a connexion yet
		if(!$this->Database['handle']) {
			// connect to the database
			$this->connect();	
		}
		$table 						= $this->buildTable($atable);
		$query						= (string)	'DELETE FROM '.$table;
		$this->dumpQuery($query);
		$result						= (boolean)	$this->Database['handle']->query($query);
		$this->updateOutdated($atable);
		// return the request result
		return($result);
	}
	public function count($atable,$conditions=null,$operator=null,$lag=null) {
		// if we don't have a connexion yet
		if(!$this->Database['handle']) {
			// connect to the database
			$this->connect();	
		}
		$queryElements				= array($atable,$conditions,$operator);
		$cachedData 				= $this->isInCache($queryElements,$atable,$lag);
		if($cachedData !== null) {
			return($cachedData);
		}
		if(count($conditions) == 0) { $conditions= null; }
		$table 						= $this->buildTable($atable);
		list($conditions,$values)	= (array)	$this->buildConditions($conditions,$operator);
		$query						= (string)	'SELECT COUNT(*) FROM '.$table.$conditions;
		$prepare					= (object)	$this->Database['handle']->prepare($query);
		$execute					= (object)	$prepare->execute($values);
		$fetch						= (array)	$prepare->fetchAll(PDO::FETCH_COLUMN);
		$return						= (integer)	$fetch[0];
		$this->putInCache($queryElements,$return);
		return($return);
	}
	
	
	public function sum($atable,$field=null,$conditions=null,$operator=null,$lag=null) {
		// if we don't have a connexion yet
		if(!$this->Database['handle']) {
			// connect to the database
			$this->connect();	
		}
		$queryElements				= array($atable,$field,$conditions,$operator);
		$cachedData 				= $this->isInCache($queryElements,$atable,$lag);
		if($cachedData !== null) {
			return($cachedData);
		}
		if(count($conditions) == 0) { $conditions= null; }
		if($field==null) 			{ return(false); }
		else 						{ $field	= $this->quoteColumn($field); }
		$table 						= $this->buildTable($atable);
		list($conditions,$values)	= (array)	$this->buildConditions($conditions,$operator);
		$query						= (string)	'SELECT SUM('.$field.') FROM '.$table.$conditions;
		$prepare					= (object)	$this->Database['handle']->prepare($query);
		$execute					= (object)	$prepare->execute($values);
		$fetch						= (array)	$prepare->fetchAll(PDO::FETCH_COLUMN);
		$return						= (integer)	$fetch[0];
		$this->putInCache($queryElements,$return);
		return($return);
	}
	
	public function min($atable,$field,$conditions=null,$operator=null,$lag=null) {
		// if we don't have a connexion yet
		if(!$this->Database['handle']) {
			// connect to the database
			$this->connect();	
		}
		$queryElements				= array($atable,$field,$conditions,$operator);
		$cachedData 				= $this->isInCache($queryElements,$atable,$lag);
		if($cachedData !== null) {
			return($cachedData);
		}
		if(count($conditions) == 0) { $conditions= null; }
		if($field==null) 			{ return(false); }
		else 						{ $field	= $this->quoteColumn($field); }
		$table 						= $this->buildTable($atable);
		list($conditions,$values)	= (array)	$this->buildConditions($conditions,$operator);
		$query						= (string)	'SELECT MIN('.$field.') FROM '.$table.$conditions;
		$prepare					= (object)	$this->Database['handle']->prepare($query);
		$execute					= (object)	$prepare->execute($values);
		$fetch						= (array)	$prepare->fetchAll(PDO::FETCH_COLUMN);
		$return						= (integer)	$fetch[0];
		$this->putInCache($queryElements,$return);
		return($return);
	}
	
	public function max($atable,$field,$conditions=null,$operator=null,$lag=null) {
		// if we don't have a connexion yet
		if(!$this->Database['handle']) {
			// connect to the database
			$this->connect();	
		}
		$queryElements				= array($atable,$field,$conditions,$operator);
		$cachedData 				= $this->isInCache($queryElements,$atable,$lag);
		if($cachedData !== null) {
			return($cachedData);
		}
		if(count($conditions) == 0) { $conditions= null; }
		if($field==null) 			{ return(false); }
		else 						{ $field	= $this->quoteColumn($field); }
		$table 						= $this->buildTable($atable);
		list($conditions,$values)	= (array)	$this->buildConditions($conditions,$operator);
		$query						= (string)	'SELECT MAX('.$field.') FROM '.$table.$conditions;
		$prepare					= (object)	$this->Database['handle']->prepare($query);
		$execute					= (object)	$prepare->execute($values);
		$fetch						= (array)	$prepare->fetchAll(PDO::FETCH_COLUMN);
		$return						= (integer)	$fetch[0];
		$this->putInCache($queryElements,$return);
		return($return);
	}
	
	/*********************************************************************************/
	/*********************************************************************************/
	
	public function query($query,$atable=null,$lag=null,$affected=null) {
		// if we don't have a connexion yet
		if(!$this->Database['handle']) {
			// connect to the database
			$this->connect();	
		}
		$queryElements				= array($query);
		$cachedData 				= $this->isInCache($queryElements,$atable,$lag);
		if($cachedData !== null) {
			return($cachedData);
		}
		$returnedData = $this->Database['handle']->query($query,PDO::FETCH_CLASS, self::FETCH_CLASS, array($atable,$this->Database['handle'],$this->quote));
		$returnedDataByCache = $this->putInCache($queryElements,$returnedData);
		if($this->cacheEnabled and $affected) {
			if(is_string($atable)) {
				$this->updateOutdated($atable);
			}
			if(is_array($affected)) {
				foreach($atable as $aTable) {
					$this->updateOutdated($aTable);
				}
			}
		}
		if($this->cacheEnabled) {
			return($returnedDataByCache);	
		}
		return($returnedData);
	}
	
	/*********************************************************************************/
	/*********************************************************************************/
	
}

?>