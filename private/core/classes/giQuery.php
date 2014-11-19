<?php

class giQuery {

	// internal attributes
	protected	$Database;
	protected	$Cache;
	protected	$Query;
	protected	$Values;
	protected	$Prepared;
	protected	$Success;
	protected	$Result;
	protected	$Array;
	protected	$Action;
	// attributes to build a query
	protected	$Table;
	protected	$Selects;
	protected	$Joins;
	protected	$Operator;
	protected	$Conditions;
	protected	$Updates;
	protected	$Inserts;
	protected	$Order;
	protected	$Limit;

	// instanciate a new query
	public function __construct($Database,$Cache=null) {
		// set the link to the database
		$this->Database 	= &$Database;
		// set the link to the cache
		$this->Cache		= &$Cache;
		// set the query as being empty for now
		$this->Query		= null;
		// set an array to store all values to pass to the prepared query
		$this->Values		= array();
		// set the PDO prepare object
		$this->Prepared		= null;
		// set the status of the query
		$this->Success		= false;
		// set the result as being empty for no
		$this->Result		= null;
		// set the array of results as being empty too
		$this->Array		= array();
		// set the main action (INSERT, UPDATE, DELETE, SELECT or QUERY in case of passthru)
		$this->Action		= null;
		// initialize attributes
		$this->Table		= null;
		$this->Operator		= null;
		$this->Selects		= array();
		$this->Joins		= array();
		$this->Conditions	= array();
		$this->Updates		= array();
		$this->Inserts		= array();
		$this->Order		= array();
		$this->Limit		= array();
	}

	// on garbage collection	
	public function __destruct() {

	}
	
	// passrthu a query with values if needed
	public function query($query,$values=null) {
		// if no principal action is set yet
		if(!$this->Action) {
			// set the main action
			$this->Action = 'QUERY';
		}
		// an action has already been set, this is impossible
		else {
			// those actions being incompatible we throw an exception
			Throw new Exception("giQuery->select() : An incompatible action already exists : {$this->Action}");
		}
		// set the query
		$this->Query = $query;
		// set the array of values
		$this->Values = $values;
		// return self to the next method
		return($this);
	}
	
	// first main method
	public function select($array=null) {
		// if no principal action is set yet
		if(!$this->Action) {
			// set the main action
			$this->Action = 'SELECT';
		}
		// an action has already been set, this is impossible
		else {
			// those actions being incompatible we throw an exception
			Throw new Exception("giQuery->select() : An incompatible action already exists : {$this->Action}");
		}
		// if the argument passed is an array of columns
		if(is_array($array)) {
			// for each column
			foreach($array as $function_or_index => $column) {
				// if the key is numeric
				if(is_numeric($function_or_index)) {
					// just select the column
					//$this->Selects[] = "{$this->Database->quote($column)}";
					$this->Selects[] = $column;
				}
				// the key is a SQL function
				else {
					// format the name of the alias
					$alias = $this->Database->quote("{$function_or_index}_{$column}");
					// select the column using a function
					$this->Selects[] = "{$function_or_index}({$column}) AS $alias";
				}	
			}	
		}
		
		// return self to the next method
		return($this);
	}
	
	// second main method
	public function update($columns_and_values) {
		// if no principal action is set yet
		if(!$this->Action) {
			// set the main action
			$this->Action = 'UPDATE';
		}
		// an action has already been set, this is impossible
		else {
			// those actions being incompatible we throw an exception
			Throw new Exception("giQuery->update() : An incompatible action already exists : {$this->Action}");
		}
		// return self to the next method
		return($this);
	}
	
	// insert data
	public function insert($columns_and_values) {
		// if no principal action is set yet
		if(!$this->Action) {
			// set the main action
			$this->Action = 'INSERT';
		}
		// an action has already been set, this is impossible
		else {
			// those actions being incompatible we throw an exception
			Throw new Exception("giQuery->insert() : An incompatible action already exists : {$this->Action}");
		}
		// check if we have an array
		if(is_array($columns_and_values)) {
			// for each column and value
			foreach($columns_and_values as $column => $value) {
				// if the column is not numeric
				if(!is_numeric($column)) {
					// push the column
					$this->Inserts[] = $column;
					// check for automatic conversion and push in place
					$this->Values[] = $this->convert($column,$value);
				}
			}
		}
		// return self to the next method
		return($this);
	}
	
	// delete data from a table
	public function delete() {
		// if no principal action is set yet
		if(!$this->Action) {
			// set the main action
			$this->Action = 'DELETE';
		}
		// an action has already been set, this is impossible
		else {
			// those actions being incompatible we throw an exception
			Throw new Exception("giQuery->delete() : An incompatible action already exists : {$this->Action}");
		}
		// return self to the next method
		return($this);
	}
	
	// select the table
	public function from($table) {
		// if the table is in string format
		if(is_string($table)) {
			// set the table
			$this->Table = $this->Database->quote($table);	
		}
		// return self to the next method
		return($this);
	}
	
	// select another table to join on
	public function join($table_and_id) {
		// if table_and_id is an array
		if(is_array($table_and_id)) {
			// for each table/column
			foreach($table_and_id as $table => $column) {
				// do something
				// ------------	
			}
		}
		// return self to the next method
		return($this);
	}
	
	// add a condition
	public function where($conditions) {
		// if provided conditions are an array
		if(is_array($conditions)) {
			// for each provided strict condition
			foreach($conditions as $column => $value) {
				// if the column name not numeric
				if(!is_numeric($column)) {
					// if the operator is missing
					if(!$this->Operator) {
						// force AND operator
						$this->Operator = 'AND';	
					}
					// save the condition
					$this->Conditions[] = "{$this->Operator} ( {$column} = :{$column} )";
					// save the value
					$this->Values[":{$column}"] = $value;
				}
			}	
		}
		// return self to the next method
		return($this);
	}
	
	// add into for inserts
	public function into($table) {
		// if $table is set
		if(is_string($table) and $table) {
			// set the table
			$this->Table = $table;
		}
		// return self to the next method
		return($this);
	}
	
	public function addAnd() {
		// set the AND
		$this->Operator = 'AND';
		// return self to the next method
		return($this);
	}
	
	public function addOr() {
		// set the OR
		$this->Operator = 'OR';		
		// return self to the next method
		return($this);
	}
	
	// shortcuts
	public function whereStartsWith($column,$value) {
		
		// return self to the next method
		return($this);
	}
	public function whereEndWith($column,$value) {
		
		// return self to the next method
		return($this);
	}
	public function whereContains($column,$value) {
		
		// return self to the next method
		return($this);
	}
	public function whereMatch($column,$value) {	
		
		// return self to the next method
		return($this);
	}
	public function whereHigherThan($column,$value) {
		
		// return self to the next method
		return($this);
	}
	public function whereLowerThan($column,$value) {
		
		// return self to the next method
		return($this);
	}
	public function whereBetween($column,$lower,$higher) {
		
		// return self to the next method
		return($this);
	}
	public function whereEmpty($column) {
		
		// return self to the next method
		return($this);
	}
	public function whereNull($column) {
		
		// return self to the next method
		return($this);
	}
	public function whereNotNull($column) {
		
		// return self to the next method
		return($this);
	}
	public function whereTrue($column) {
		
		// return self to the next method
		return($this);
	}
	public function whereFalse($column) {
		
		// return self to the next method
		return($this);
	}
	
	// add an order clause
	public function orderBy($columns_and_direction) {
		// if the parameter is an array
		if(is_array($columns_and_direction)) {
			// for each given parameter
			foreach($columns_and_direction as $column => $direction) {
				// if the column is numeric
				if(is_numeric($column)) {
					// skip it as a wrong parameter has been provided
					continue;	
				}
				// if the direction is not valid
				if($direction != 'ASC' and $direction != 'DESC') {
					// skip it as a wrong parameter has been provided	
					continue;
				}
				// push it
				$this->Order[] = "{$this->Database->quote($column)} $direction";
			}
		}
		// return self to the next method
		return($this);
	}
	
	// add a limit clause
	public function limitTo($from,$until) {
		// if both parameters are numric
		if(is_numeric($from) and is_numeric($until)) {
			// build the limit to 
			$this->Limit = array(
				// start
				$from,
				// selected only 
				$until
			);
		}
		// return self to the next method
		return($this);
	}

	// method that actually assemble the query
	private function buildQuery() {
		
	}

	// execute the query
	public function execute() {
		
		// check in the cache
		// ------------
		
		// if the action is missing
		if(!$this->Action) {
			// thow an exception
			Throw new Exception('giQuery->execute() : Missing action');	
		}
		
		// if action anything but query
		if($this->Action != 'QUERY') {
			// set the first keyword
			$this->Query = $this->Action;
		}
		
		// if action is insert
		if($this->Action == 'INSERT') {
			// if the table is missing
			if(!$this->Table) {
				// throw an exception
				Throw new Exception('giQuery->execute() : Missing INTO');
			}
			// if missing values
			if(!$this->Values or !count($this->Values)) {
				// throw an exception
				Throw new Exception('giQuery->execute() : Missing VALUES');	
			}
			// set destination and columns
			$this->Query .= " INTO $this->Table ( " . implode(', ',$this->Inserts) . " )";
			// set the placeholders
			$this->Query .= " VALUES ( :".trim(implode(', :',$this->Inserts),', ')." )";
		}
		
		// if action is select
		if($this->Action == 'SELECT') {
			// if the table is missing
			if(!$this->Table) {
				// throw an exception
				Throw new Exception('giQuery->execute() : Missing FROM');	
			}
			// if columns are set for selection
			if(count($this->Selects)) {
				// assemble all the columns
				$this->Query .= ' ' . implode(', ',$this->Selects).' ';
			}
			// no specific column set for selection
			else {
				// select everything
				$this->Query .= ' * ';
			}
			
		}
		// build the joins
		// ------------
		
		// if the action has a from table
		if($this->Action == 'SELECT' or $this->Action == 'DELETE') {
			// add source table
			$this->Query .= "FROM $this->Table";
		}
		
		// if the action needs conditions
		if($this->Action == 'SELECT' or $this->Action == 'UPDATE' or $this->Action == 'DELETE') {
			// if conditions are provided
			if(count($this->Conditions)) {
				// assemble the conditions
				$this->Query .= ' WHERE ' . trim(implode(' ',$this->Conditions),'AND /OR ');
			}
		}
		
		
		
		// if ordering options are set
		if($this->Action == 'SELECT' and count($this->Order)) {
			// assemble orders
			$this->Order = implode(', ',$this->Order);
			// add ordering to the query
			$this->Query .= " ORDER BY $this->Order";
		}
		// if limit options are set
		if($this->Action == 'SELECT' and count($this->Limit)) {
			// assemble the limit options to the query
			$this->Query .= " LIMIT {$this->Limit[0]},{$this->Limit[1]}";
		}
		// prepare the statement
		$this->Prepared = $this->Database->prepare($this->Query);
		// if prepare failed
		if(!$this->Prepared) {
			// prepare informations to be thrown
			$exception_infos = implode(":",$this->Database->ErrorInfo()).":$this->Query";
			// throw an exception
			Throw new Exception('giQuery->execute() : Failed to prepare query ['.$exception_infos.']');
		}
		// execute the statement
		$this->Success = $this->Prepared->execute($this->Values);
		// if execution failed
		if($this->Success === false) {
			// prepare informations to be thrown
			$exception_infos = implode(":",$this->Database->ErrorInfo()).":$this->Query";
			// throw an exception
			Throw new Exception('giQuery->execute() : Failed to execute query ['.$exception_infos.']');
		}
		// fetch all results
		$this->Result = $this->Prepared->fetchAll(
			// fetch as an object
			PDO::FETCH_CLASS,
			// of this specific class
			'giRecord',
			// and pass it some arguments
			array($this->Table,$this->Database)
		);
		
		// if action is not insert nor delete and succeeded
		if($this->Action != 'INSERT' and $this->Action != 'DELETE' and $this->Success) {
			// place in cache
			// --------------
		}
		
		// if the query was an insert and it succeeded
		if($this->Action == 'INSERT' and $this->Success) {
			// update the cache
			// ----------------
			
			// instanciate a new query
			$this->Result = new giQuery($this->Database,$this->Cache);
			// get the newly inserted element from its id
			return($this->Result
				->select()
				->from($this->Table)
				->where(array('id'=>$this->Database->lastInsertId()))
				->execute()
			);
		}
		// return the results
		return($this->Result);
	}
	
	// get the results
	public function fetchObjects() {
		
		// return the results
		return($this->Result);
	}
	
	// get the results
	public function fetchArray() {

		// return the results
		return($this->Result);
	}

	// set the table last modification so all older objects will be disregarded
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
			$this->Cache['handle']->set($this->Cache['prefix'].'_qd_'.$aQueryHash,$queryResult);
			// set the query time
			$this->Cache['handle']->set($this->Cache['prefix'].'_qt_'.$aQueryHash,time());
			// return the query result
			return($queryResult);
		}
	}
	
	// convert types like dates and arrays
	private function convert($column,$value) {
		// if we find a file keyword
		if(strpos($column,'_file') !== false) {
			// store that file and replace it by a json value in the database
			// array(path=>null,size=>null,mime=>null)
		}
		// if we find a serialization keyword
		elseif(strpos($column,'_array') !== false) {
			// encode the content as JSON
			$value = json_encode($value);
		}
		// if we are dealing with a date
		elseif(strpos($column,'_date') !== false) {
			// if the date is formated with two slashs
			if(substr_count($column,'/') == 2) {
				// set the separator
				$separator = '/';
			}
			// if the date is formated with two dots
			elseif(substr_count($column,'.') == 2) {
				// set the separator
				$separator = '.';
			}
			// if the date is formated with two -
			elseif(substr_count($column,'-') == 2) {
				// set the separator
				$separator = '-';
			}
			// separator in unknown, we assume it is already a timestamp
			else {
				// clean it just to make sure
				$value = preg_replace('/\D/','',$value);
			}
			// if we know the separator
			if($separator) {
				// explode the date's elements
				list($day,$month,$year) = explode($separator,$value);
				// create a timestamp
				$value = mktime(0,0,1,$month,$day,$year);
			}
		}	
		// return the value
		return($value);
	}

}

?>