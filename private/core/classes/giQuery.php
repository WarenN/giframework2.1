<?php

class giQuery {

	// internal attributes
	protected	$Database;
	protected	$Cache;
	protected	$Query;
	protected	$Values;
	protected	$Result;
	protected	$Array;
	protected	$Action;
	
	// attributes to build a query
	protected	$Selects;
	protected	$Joins;
	protected	$Operators;
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
		// set the result as being empty for no
		$this->Result		= null;
		// set the array of results as being empty too
		$this->Array		= array();
		// set the main action (INSERT, UPDATE, DELETE, SELECT)
		$this->Action		= null;
		// initialize attributes
		$this->Selects		= array();
		$this->Joins		= array();
		$this->Operators	= array();
		$this->Conditions	= array();
		$this->Updates		= array();
		$this->Inserts		= array();
		$this->Order		= array();
		$this->Limit		= array();
		
	}

	// on garbage collection	
	public function __destruct() {

	}
	
	// passrthu a query with values in needed
	public function query($query,$values=null) {
		
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
			// those action being incompatible we throw an exception
			Throw new Exception('giQuery->select() : An incompatible action already exists : '.$this->Action);
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
			// those action being incompatible we throw an exception
			Throw new Exception('giQuery->select() : An incompatible action already exists : '.$this->Action);
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
			// those action being incompatible we throw an exception
			Throw new Exception('giQuery->select() : An incompatible action already exists : '.$this->Action);
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
			// those action being incompatible we throw an exception
			Throw new Exception('giQuery->select() : An incompatible action already exists : '.$this->Action);
		}
		// return self to the next method
		return($this);
	}
	
	// select the table
	public function from($table) {
		
		// return self to the next method
		return($this);
	}
	
	// select another table to join on
	public function join($table_and_id) {
		
		// return self to the next method
		return($this);
	}
	
	// add a condition
	public function where($conditions) {
		
		// return self to the next method
		return($this);
	}
	
	// add into for inserts
	public function into($table) {
		
		// return self to the next method
		return($this);
	}
	
	public function addAnd() {
		
		// push an AND operator in the list
		$this->Operators[] = 'AND';
		// return self to the next method
		return($this);
		
	}
	
	public function addOr() {
		
		// push an OR operator in the list
		$this->Operators[] = 'OR';		
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


}

?>