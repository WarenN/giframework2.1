<?php

// helper that provides some simple methods simplifying records manipulation 
class giDatabase {

	// class of the child
	const FETCH_CLASS = 'giRecord';

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
		// if memcache is enabled
		if($this->Cache['enabled']) {
			// instanciate a memcache client
			$this->Cache['handle'] = new Memcache();
			// configure the memcache client
			$this->Cache['handle']->connect($this->Cache['hostname'],$this->Cache['port']);
		}
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
	
	private function quote($string) {
		// return a string properly quoted without any dangerous symbols using PDO's engine
		return($this->Database['handle']->Quote($string));
	}
	
	private function generateQueryHash($queryElements) {
		// create a signature for this request
		return(md5(json_encode($queryElements)));
	}
	
	// convert types like dates and arrays
	private function convertTypes($associativeArray) {
		// for each element of the array
		foreach($associativeArray as $anElementKey => $anElementValue) {
			// if we find a file keyword
			if(strpos($anElementKey,'_file') !== false) {
					// store that file and replace it by a json value in the database
					// array(path=>null,size=>null,mime=>null)
			}
			// if we find a serialization keyword
			elseif(strpos($anElementKey,'_array') !== false) {
				// encode the content as JSON
				$associativeArray[$anElementKey] = json_encode($anElementValue);
			}
			// if we are dealing with a date
			elseif(strpos($anElementKey,'_date') !== false) {
				// if the date is formated with two slashs
				if(substr_count($anElementValue,'/') == 2) {
					// set the separator
					$aSeparator = '/';
				}
				// if the date is formated with two dots
				elseif(substr_count($anElementValue,'.') == 2) {
					// set the separator
					$aSeparator = '.';
				}
				// if the date is formated with two -
				elseif(substr_count($anElementValue,'-') == 2) {
					// set the separator
					$aSeparator = '-';
				}
				// separator in unknown, we assume it is already a timestamp
				else {
					// clean it just to make sure
					$anElementValue = preg_replace('/\D/','',$anElementValue);
					// don't save anything
					$associativeArray[$anElementKey] = $anElementValue;
				}
				// if we know the separator
				if($aSeparator) {
					// explode the date's elements
					list($day,$month,$year) = explode($aSeparator,$anElementValue);
					// create a timestamp
					$timestamp = mktime(0,0,1,$month,$day,$year);
					// put it in place of the date
					$associativeArray[$anElementKey] = (string)	$timestamp;
				}
			}	
		}
		// return the array
		return($associativeArray);
	}
	
	// escape the table name
	private function buildTable($table) {
		return($this->quote($table));
	}
	
	// build the column list
	private function buildColumns($columns) {
		// if no columns are provided
		if(!$columns or !is_array($columns)) {
			// select all columns
			return('*');	
		}
		// columns are provided
		else {
			$actualColumns = array();
			// for each column
			foreach($columns as $aColumn) {
				// escape the column name
				$actualColumns[] = $this->quote($aColumn);
			}
			// aggregate all columns
			return(implode(', ',$actualColumns));	
		}
	}
	
	// build simple conditions
	private function buildConditions($conditions,$operator=null) {
		// if the operator is invalid
		if(!in_array($operator,array('AND','OR'))) {
			// force the AND operator
			$operator = 'AND';
		}
		// if conditions are under array form
		if(is_array($conditions)) {
			// prepare empty arrays
			$conditionsArray		= array();
			$conditionsValues		= array();
			// for each condition provided
			foreach($conditions as $aConditionColumn => $aConditionValue) {
				// escape the column name and insert the ?
				$conditionsArray	[]= $this->quote($aConditionColumn).' = ?';
				// set the value
				$conditionsValues	[]= $aConditionValue;
			}
			// assemble all the conditions together
			$conditions 			= ' WHERE ( '.implode(' '.$operator.' ',$conditionsArray).' )';
			// prepare an array to return with query in 0 and conditions array in 1
			$return					= array($conditions,$conditionsValues);
			// return built conditions
			return($return);
		}
		// conditions provided are in a wrong format
		else {
			// prepare an empty array to return
			$return = array('',array());
			// return empty options
			return($return);
		}
	}
	
	private function buildSearchs($conditions,$operator=null) {
		// if the operator is invalid
		if(!in_array($operator,array('AND','OR'))) {
			// force the AND operator
			$operator = 'AND';
		}
		// if the conditions provided are in form or an array
		if(is_array($conditions)) {
			// prepare empty arrays
			$conditionsArray		= array();
			$conditionsValues		= array();
			// for each provided condition
			foreach($conditions as $aConditionColumn => $aConditionValue) {
				// if the searched value is only one character long
				if(strlen($aConditionValue) == 1) {
					// searched value must start with that letter
					$conditionsArray	[]= $this->quote($aConditionColumn).' LIKE ?';
					$conditionsValues	[]= $aConditionValue.'%';
				}
				// the searched value can be anywhere in the column
				else {
					$conditionsArray	[]= $this->quote($aConditionColumn).' LIKE ?';
					$conditionsValues	[]= '%'.$aConditionValue.'%';
				}
					}
			// assemble all the conditions together
			$conditions 			= ' WHERE ( '.implode(' '.$operator.' ',$conditionsArray).' )';
			// prepare an array with the query and the values separated
			$return					= array($conditions,$conditionsValues);
			// return both
			return($return);
		}
		// conditions provided are wrong
		else {
			// prepare an empty array
			$return	= array('',array());
			// return it
			return($return);
		}
	}
	
	private function buildFinds($conditions,$operator=null) {
		/*
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
						$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quote($ConditionColumn).' '.$ConditionOperator.' ?';
						$conditionsValues	[]= (string)	$v1;
						break;
									case 'IS':
						if( strtoupper($v1) == "NULL") {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quote($ConditionColumn).' '.$ConditionOperator.$not.' NULL';
						} else {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quote($ConditionColumn).' '.$ConditionOperator.$not.' ?';
							$conditionsValues	[]= (string)	$v1;
						}
						break;
	
					case 'LIKE':
						$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quote($ConditionColumn).' '.$not.$ConditionOperator.' ?';
						$conditionsValues	[]= (string)	'%'.$v1.'%';
						break;
	
					case 'START WITH':
						$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quote($ConditionColumn).$not.' LIKE ?';
						$conditionsValues	[]= (string)	$v1.'%';
						break;
	
					case 'END WITH':
						$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quote($ConditionColumn).$not.' LIKE ?';
						$conditionsValues	[]= (string)	'%'.$v1;
						break;
	
					case 'BETWEEN':
						if( $v1 != NULL && $v2 != NULL ){
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quote($ConditionColumn).$not.' BETWEEN ? AND ?';
							$conditionsValues	[]= (string)	$v1;
							$conditionsValues	[]= (string)	$v2;
	
						} elseif( $v1 != NULL && $v2 == NULL  ) {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quote($ConditionColumn).' >= ?';
							$conditionsValues	[]= (string)	$v1;
	
						} elseif( $v1 == NULL && $v2 != NULL  ) {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quote($ConditionColumn).' <= ?';
							$conditionsValues	[]= (string)	$v2;
						}
						break;

					// bitwise AND
					case '&':
						if( strtoupper($v1) == "NULL") {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quote($ConditionColumn).' '.$ConditionOperator.$not.' NULL';
						} else {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quote($ConditionColumn).' '.$ConditionOperator.$not.' ?';
							$conditionsValues	[]= (string)	$v1;
						}
						break;
									// bitwise OR (inclusive or)
					case '|':
						if( strtoupper($v1) == "NULL") {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quote($ConditionColumn).' '.$ConditionOperator.$not.' NULL';
						} else {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quote($ConditionColumn).' '.$ConditionOperator.$not.' ?';
							$conditionsValues	[]= (string)	$v1;
						}
						break;

					// bitwise XOR (exclusive or)
					case '^':
						if( strtoupper($v1) == "NULL") {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quote($ConditionColumn).' '.$ConditionOperator.$not.' NULL';
						} else {
							$conditionsArray	[]= (string)	$ConditionChain.' '.$this->quote($ConditionColumn).' '.$ConditionOperator.$not.' ?';
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
		*/
	}

	private function buildInsert($associativeArray) {
		// prepare an array to receive column names
		$columns					= array();
		// prepare an array to receive values
		$values						= array();
		// for each value
		foreach($associativeArray as $insertColumn => $insertValue) {
			// push the column
			$columns				[]= $insertColumn;
			// push the value
			$values					[]= $insertValue;
		}
		// build the insert statement assembling all columns names
		$columns					= (string)	' ( '.$this->Quote.implode($this->Quote.' , '.$this->Quote,$columns).$this->Quote.' ) ';
		// prepare an array with statement and values separated
		$return						= array($columns,$values);
		// return that array
		return($return);
	}

	private function buildPlaceholders($array) {
		// prepare an empty array
		$placeholders				= array();
		// for each value
		foreach($array as $anEntry) {
			// add a placeholder
			$placeholders			[]= (string)	'?';
		}
		// assemble all placeholder together
		$return						= (string)	' ( '.implode(' , ',$placeholders).' ) ';
		// return the formated statement
		return($return);
	}
	
	private function buildUpdates($associativeArray) {
		// prepare an array to store columns
		$updates					= array();
		// prepare an array to store values
		$values						= array();
		// for each column to update
		foreach($associativeArray as $updateColumn => $updateValue) {
			// escape the column name with placeholder and push into an array
			$updates				[]= ' '.$this->quote($updateColumn).' = ? ';
			// push the value into another array
			$values					[]= $updateValue;
		}
		// assemble the SET statements 
		$updates					= (string)	' SET'.implode(' , ',$updates);
		// prepare an array with statement and value separated
		$return						= array($updates,$values);
		// return that array
		return($return);
	}
	
	private function buildOrderBy($orderby) {
		// default is no order by
		$return = '';
		// if order columns have been provided
		if(is_array($orderby)){
			// prepare an array to store order options
			$orderArray		= array();
			// for each ordering option
			foreach($orderby as $aColumnName => $aSortOrder){
				// if sort order is no valid
				if($aSortOrder != 'ASC' and $aSortOrder != 'DESC') {
					// force the sort order
					$aSortOrder = 'ASC';	
				}
				// push the ordering statement
				$orderArray	[]= $this->quote($aColumnName).' '.$aSortOrder;
			}
			// assemble all the order by statements
			$return = ' ORDER BY '.implode(', ',$orderArray);
		}
		// return the order by statement if any
		return $return;
	}

	private function buildLimitTo($limitto) {
		// default is no limit
		$return = '';
		// if limit to is an array of exactly two parameters
		if(is_array($limitto) and $limitto[0] !== null and $limitto[1] !== null){
			// assemble the limit to statement
			$return = ' LIMIT  '.intval($limitto[0]).' , '.intval($limitto[1]);
		}
		// return the limit if any
		return $return;
	}
	
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
			// assemble the condition
			$fts = ' WHERE '.$this->quote($search_columns[0]).' MATCH '.'?';
		}
		// multiple columns are to be searched
		else {
			// for each column to search
			foreach($search_columns as $aSearchId => $aSearchColumn) {
				// escape that column's name
				$search_columns[$aSearchId] = $this->quote($aSearchColumn);
			}
			// assemble the statements
			$fts = ' WHERE '.implode(', ',$search_columns).' MATCH '.'?';
		}
		// return the conditions
		return($fts);
	}
	
	
	
	public function updateOutdated($aTable) {
		// if caching is enabled
		if($this->Cache['enabled']) {
			// try to update
			$oudatedUpdate = $this->Cache['handle']->replace($this->Cache['prefix'].'_lu_'.$aTable,time());	
			// if the update failed
			if(!$outdatedUpdate) {
				// set the last modification date fot this table
				$this->Cache['handle']->set($this->Cache['prefix'].'_lu_'.$aTable,time());
			}	
		}	
	}
	
	// check if an sql query is in cache
	private function isInCache($queryElements,$aTable,$lagTolerance) {
		// the cache is enabled
		if($this->Cache['enabled']) {
			// generate a hash for this specific query
			$aQueryHash = $this->generateQueryHash($queryElements);
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
				if(!$lastTableUpdate) {
					// set the last modification date for the next time
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
	
	// put an SQL query result in cache
	private function putInCache($queryElements,$queryResult) {
		// if the cache is enabled
		if($this->Cache['enabled']) {
			// generate the query hash
			$aQueryHash = $this->generateQueryHash($queryElements);
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
	
	public function flushCache() {
		// the cache is enabled
		if($this->Cache['enabled']) {
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
		$fetch						= (array)	$prepare->fetchAll(PDO::FETCH_CLASS, self::FETCH_CLASS, array($atable,$this->Database['handle']));
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
		$fetch						= (array)	$prepare->fetchAll(PDO::FETCH_CLASS, self::FETCH_CLASS, array($atable,$this->Database['handle'],$this->Quote));
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
		$fetch						= (array)	$prepare->fetchAll(PDO::FETCH_CLASS, self::FETCH_CLASS, array($atable,$this->Database['handle'],$this->Quote));
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
		$fetch						= (array)	$prepare->fetchAll(PDO::FETCH_CLASS, self::FETCH_CLASS, array($atable,$this->Database['handle'],$this->Quote));
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
		list($object)				= (array)	$prepare->fetchAll(PDO::FETCH_CLASS, self::FETCH_CLASS, array($atable,$this->Database['handle'],$this->Quote));
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
		else 						{ $field	= $this->quote($field); }
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
		else 						{ $field	= $this->quote($field); }
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
		else 						{ $field	= $this->quote($field); }
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
	
	public function raw($query,$atable=null,$lag=null,$affected=null) {
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
		$returnedData = $this->Database['handle']->query($query,PDO::FETCH_CLASS, self::FETCH_CLASS, array($atable,$this->Database['handle'],$this->Quote));
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
	
	// request an advanced query
	public function query() {
	
		// return a new query object
		return(new giQuery($this->Database));
		
	}
	
}

?>