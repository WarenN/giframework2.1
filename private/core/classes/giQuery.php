<?php
/**
 * PHP Version 5
 * @package giFramework
 * @link https://github.com/AnnoyingTechnology/giframework2.1
 * @author Julien Arnaud (AnnoyingTechnology) <e10ad5d4ab72523920e7cbe55ba6c91c@gribouille.eu@gribouille.eu>
 * @copyright 2015 - 2015 Julien Arnaud
 * @license http://www.gnu.org/licenses/lgpl.txt GNU General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */

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
	protected	$Hash;
	protected	$Lag;
	// attributes to build a query
	protected	$Table;
	protected	$Selects;
	protected	$Joins;
	protected	$Operator;
	protected	$Conditions;
	protected	$Updates;
	protected	$Inserts;
	protected	$Groups;
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
		// set the unique hash of the query for caching purpose
		$this->Hash			= null;
		// set the maximum age allowed from the cache (in hour), 6h would use caches 6h old at maximum, 0 uses cache since table was not updated
		$this->Lag			= null;
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
	public function query($query,$values=null,$table=null) {
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
		// set the table
		$this->Table = $table;
		// set the query
		$this->Query = $query;
		// set the array of values
		$this->Values = $values;
		// detetect the action
		$action = substr($query,0,6);
		// if action can alter a table (INSERT, UPDATE, DELETE)
		if(in_array($action,array('INSERT','UPDATE','DELETE','SELECT'))) {
			// in case of INSERT
			if($action == 'INSERT') {
				// explode after INTO
				list($null,$table) = explode('INTO ',$this->Query);
				// isolate the table name
				list($this->Table) = explode(' ',$table);
			}
			// in case of UPDATE
			elseif($action == 'UPDATE') {
				// explode after UPDATE
				list($null,$table) = explode('UPDATE ',$this->Query);
				// isolate the table name
				list($this->Table) = explode(' ',$table);
			}
			// in case of DELETE or SELECT
			elseif($action == 'DELETE' or $action == 'SELECT') {
				// explode after FROM 
				list($null,$table) = explode('FROM ',$this->Query);
				// isolate the table name
				list($this->Table) = explode(' ',$table);
			}
			// clean the table name from any quotes
			$this->Table = trim($this->Table,'\'/"`');
		}
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
				// secure the column name
				$column = $this->secure($column);
				// if the key is numeric
				if(is_numeric($function_or_index)) {
					// just select the column
					$this->Selects[] = $column;
				}
				// the key is a SQL function
				else {
					// format the name of the alias
					$alias = $this->Database['handle']->quote("{$function_or_index}_{$column}");
					// select the column using a function
					$this->Selects[] = "{$function_or_index}({$column}) AS $alias";
				}	
			}	
		}
		// return self to the next method
		return($this);
	}
	
	// alias of update
	public function set($columns_and_values) {
		// if provided conditions are an array
		if(is_array($columns_and_values)) {
			// for each provided strict condition
			foreach($columns_and_values as $column => $value) {
				// if the column name not numeric
				if(!is_numeric($column)) {
					// secure the column name
					$column = $this->secure($column);
					// save the condition
					$this->Updates[] = "{$column} = :{$column}";
					// save the value (converted if necessary)
					$this->Values[":{$column}"] = $this->convert($column,$value);
				}
				// column name in a number
				else {
					// throw an exception
					Throw new Exception('giQuery->update() : Column name cannot be a number');
				}
			}
		}
		// return self to the next method
		return($this);
	}
	
	// second main method
	public function update($table) {
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
		// if the table is a string and is not empty
		if(is_string($table) and !empty($table)) {
			// set the destination table
			$this->Table = $table;			
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
					// secure the column name
					$column = $this->secure($column);
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
			$this->Table = $this->Database['handle']->quote($table);	
		}
		// wrong type
		else {
			// those actions being incompatible we throw an exception
			Throw new Exception("giQuery->from() : Wrong parameter type, string expected");
		}
		// return self to the next method
		return($this);
	}
	
	// select another table to join on (implicit INNER JOIN)
	public function join($table,$match,$against) {
		// if table_and_id is an array
		if(!is_string($table) or !$table or !is_string($match) or !$match or !is_string($against) or !$against) {
			// those actions being incompatible we throw an exception
			Throw new Exception("giQuery->join() : Wrong parameter");
		}
		// push the join condition
		$this->Joins[] = "JOIN {$this->secure($table)} ON $this->secure($match) = $this->secure($against)";
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
	
	// add a condition
	public function where($conditions) {
		// if provided conditions are an array
		if(is_array($conditions)) {
			// for each provided strict condition
			foreach($conditions as $column => $value) {
				// if the column name not numeric
				if(!is_numeric($column)) {
					// secure the column name
					$column = $this->secure($column);
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
				// column name in a number
				else {
					// throw an exception
					Throw new Exception('giQuery->where() : Column name cannot be a number');
				}
			}
		}
		// return self to the next method
		return($this);
	}
	
	// shortcuts
	public function whereStartsWith($conditions) {
		// if provided conditions are an array
		if(is_array($conditions)) {
			// for each provided strict condition
			foreach($conditions as $column => $value) {
				// if the column name not numeric
				if(!is_numeric($column)) {
					// secure the column name
					$column = $this->secure($column);
					// if the operator is missing
					if(!$this->Operator) {
						// force AND operator
						$this->Operator = 'AND';
					}
					// save the condition
					$this->Conditions[] = "{$this->Operator} ( {$column} LIKE :{$column} )";
					// save the value
					$this->Values[":{$column}"] = "{$value}%";
				}
				// column name in a number
				else {
					// throw an exception
					Throw new Exception('giQuery->whereStartsWith() : Column name cannot be a number');
				}
			}
		}
		// return self to the next method
		return($this);
	}
	public function whereEndsWith($conditions) {
		// if provided conditions are an array
		if(is_array($conditions)) {
			// for each provided strict condition
			foreach($conditions as $column => $value) {
				// if the column name not numeric
				if(!is_numeric($column)) {
					// secure the column name
					$column = $this->secure($column);
					// if the operator is missing
					if(!$this->Operator) {
						// force AND operator
						$this->Operator = 'AND';
					}
					// save the condition
					$this->Conditions[] = "{$this->Operator} ( {$column} LIKE :{$column} )";
					// save the value
					$this->Values[":{$column}"] = "%$value";
				}
				// column name in a number
				else {
					// throw an exception
					Throw new Exception('giQuery->whereEndsWith() : Column name cannot be a number');
				}
			}
		}
		// return self to the next method
		return($this);
	}
	public function whereContains($conditions) {
		// if provided conditions are an array
		if(is_array($conditions)) {
			// for each provided strict condition
			foreach($conditions as $column => $value) {
				// if the column name not numeric
				if(!is_numeric($column)) {
					// secure the column name
					$column = $this->secure($column);
					// if the operator is missing
					if(!$this->Operator) {
						// force AND operator
						$this->Operator = 'AND';
					}
					// save the condition
					$this->Conditions[] = "{$this->Operator} ( {$column} LIKE :{$column} )";
					// save the value
					$this->Values[":{$column}"] = "%{$value}%";
				}
				// column name in a number
				else {
					// throw an exception
					Throw new Exception('giQuery->whereContains() : Column name cannot be a number');
				}
			}
		}
		// return self to the next method
		return($this);
	}
	public function whereMatch($conditions) {	
		// if provided conditions are an array
		if(is_array($conditions)) {
			// for each provided strict condition
			foreach($conditions as $column => $value) {
				// if the column name not numeric
				if(!is_numeric($column)) {
					// secure the column name
					$column = $this->secure($column);
					// if the operator is missing
					if(!$this->Operator) {
						// force AND operator
						$this->Operator = 'AND';
					}
					// save the condition
					$this->Conditions[] = "{$this->Operator} ( MATCH {$column} AGAINST :{$column} )";
					// save the value
					$this->Values[":{$column}"] = $value;
				}
				// column name in a number
				else {
					// throw an exception
					Throw new Exception('giQuery->whereMatch() : Column name cannot be a number');
				}
			}
		}
		// return self to the next method
		return($this);
	}
	public function whereHigherThan($conditions) {
		// if provided conditions are an array
		if(is_array($conditions)) {
			// for each provided strict condition
			foreach($conditions as $column => $value) {
				// if the column name not numeric
				if(!is_numeric($column)) {
					// secure the column name
					$column = $this->secure($column);
					// if the operator is missing
					if(!$this->Operator) {
						// force AND operator
						$this->Operator = 'AND';
					}
					// save the condition
					$this->Conditions[] = "{$this->Operator} ( {$column} > :{$column} )";
					// save the value
					$this->Values[":{$column}"] = $value;
				}
				// column name in a number
				else {
					// throw an exception
					Throw new Exception('giQuery->whereHigherThan() : Column name cannot be a number');
				}
			}
		}
		// return self to the next method
		return($this);
	}
	public function whereLowerThan($conditions) {
		// if provided conditions are an array
		if(is_array($conditions)) {
			// for each provided strict condition
			foreach($conditions as $column => $value) {
				// if the column name not numeric
				if(!is_numeric($column)) {
					// secure the column name
					$column = $this->secure($column);
					// if the operator is missing
					if(!$this->Operator) {
						// force AND operator
						$this->Operator = 'AND';
					}
					// save the condition
					$this->Conditions[] = "{$this->Operator} ( {$column} < :{$column} )";
					// save the value
					$this->Values[":{$column}"] = $value;
				}
				// column name in a number
				else {
					// throw an exception
					Throw new Exception('giQuery->whereLowerThan() : Column name cannot be a number');
				}
			}
		}
		// return self to the next method
		return($this);
	}
	public function whereBetween($column,$lower,$higher) {
		// if column is set
		if($column and !is_numeric($column)) {
			// if lower or higher is numeric
			if(is_numeric($lower) and is_numeric($higher)) {
				// secure the column name
				$column = $this->secure($column);
				// save the condition
				$this->Conditions[] = "{$this->Operator} ( {$column} BETWEEN = :min_{$column} AND :max_{$column} )";
				// add the min value
				$this->Values[":min_{$column}"] = $lower;
				// add the max value
				$this->Values[":max_{$column}"] = $higher;
			}
			// min and max are not numeric
			else {
				// throw an exception
			Throw new Exception('giQuery->whereBetween() : Min or max values must be numbers');	
			}
		}
		// column is incorrect
		else {
			// throw an exception
			Throw new Exception('giQuery->whereBetween() : Column name is invalid');
		}
		// return self to the next method
		return($this);
	}
	public function whereNull($column) {
		// if column is set
		if($column and !is_numeric($column)) {
			// secure the column name
			$column = $this->secure($column);
			// save the condition
			$this->Conditions[] = "{$this->Operator} ( {$column} IS NULL OR {$column} = :empty_{$column} OR {$column} = 0 )";
			// add the empty value
			$this->Values[":empty_{$column}"] = '';
		}
		// column is incorrect
		else {
			// throw an exception
			Throw new Exception('giQuery->whereNull() : Column name is invalid');
		}
		// return self to the next method
		return($this);
	}
	public function whereNotNull($column) {
		// if column is set
		if($column and !is_numeric($column)) {
			// secure the column name
			$column = $this->secure($column);
			// save the condition
			$this->Conditions[] = "{$this->Operator} ( length({$column}) > 0  )";
		}
		// column is incorrect
		else {
			// throw an exception
			Throw new Exception('giQuery->whereNotNull() : Column name is invalid');
		}
		// return self to the next method
		return($this);
	}
	public function whereTrue($column) {
		// if column is set
		if($column and !is_numeric($column)) {
			// secure the column name
			$column = $this->secure($column);
			// save the condition
			$this->Conditions[] = "{$this->Operator} ( {$column} = 1 OR {$column} = :{$column} )";
			// save the value
			$this->Values[":{$column}"] = 'true';
		}
		// column is incorrect
		else {
			// throw an exception
			Throw new Exception('giQuery->whereTrue() : Column name is invalid');
		}
		// return self to the next method
		return($this);
	}
	public function whereFalse($column) {
		// if column is set
		if($column and !is_numeric($column)) {
			// secure the column name
			$column = $this->secure($column);
			// save the condition
			$this->Conditions[] = "{$this->Operator} ( {$column} IS NULL OR {$column} = 0 OR {$column} = :{$column} OR {$column} = :empty_{$column} )";
			// save the value
			$this->Values[":{$column}"] = 'false';
			// save the value
			$this->Values[":empty_{$column}"] = '';
		}
		// column is incorrect
		else {
			// throw an exception
			Throw new Exception('giQuery->whereTrue() : Column name is invalid');
		}
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
				// secure the column name
				$column = $this->secure($column);
				// push it
				$this->Order[] = "{$column} $direction";
			}
		}
		// return self to the next method
		return($this);
	}
	
	// add a group clause
	public function groupBy($columns) {
		// if the parameter is an array
		if(is_array($columns)) {
			// for each given parameter
			foreach($columns as $column) {
				// secure the column name
				$column = $this->secure($column);
				// push it
				$this->Groups[] = $column;
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

	// execute the query
	public function execute() {
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
		
		// if the action is delete
		if($this->Action == 'DELETE') {
			// if the table is missing
			if(!$this->Table) {
				// throw an exception
				Throw new Exception('giQuery->execute() : Missing table to delete from');	
			}
			// set the query
			$this->Query = 'DELETE ';
		}
		// if the action has a from table
		if($this->Action == 'SELECT' or $this->Action == 'DELETE') {
			// add source table
			$this->Query .= "FROM $this->Table";
		}
		// if action is an update
		if($this->Action == 'UPDATE') {
			// if the table is missing
			if(!$this->Table) {
				// throw an exception
				Throw new Exception('giQuery->execute() : No table to update');	
			}
			// if there is nothing to update
			if(!count($this->Updates)) {
				// throw an exception
				Throw new Exception('giQuery->execute() : No columns to update');	
			}
			// assemble the updates
			$this->Updates = implode(', ',$this->Updates);
			// prepare the update query
			$this->Query = "UPDATE $this->Table SET $this->Updates";
		}
		// if the select has joined tables
		if($this->Action == 'SELECT' and count($this->Joins)) {
			// assemble the joinds
			$this->Joins = implode(' ',$this->Joins);
			// assemble the query
			$this->Query .= " $this->Joins";
		}
		// if the action needs conditions
		if($this->Action == 'SELECT' or $this->Action == 'UPDATE' or $this->Action == 'DELETE') {
			// if conditions are provided
			if(count($this->Conditions)) {
				// assemble the conditions
				$this->Conditions = trim(implode(' ',$this->Conditions),'AND /OR ');
				// assemble the query
				$this->Query .= " WHERE $this->Conditions";
			}
		}
		// if groupings options are set
		if($this->Action == 'SELECT' and count($this->Groups)) {
			// assemble groups
			$this->Groups = implode(' , ',$this->Groups);
			// assemble query
			$this->Query .= " GROUP BY $this->Groups";
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
		// if cache is enabled and query is a SELECT or a passtrhu starting with SELECT
		if($this->Cache['enabled'] and ( $this->Action == 'SELECT' or ($this->Action == 'QUERY' and substr($this->Query,0,6) == 'SELECT'))) {
			// check if it exists in cache
			$cached = $this->isInCache();
			// if cache provided actual result
			if($cached !== null) {
				// return the cached data
				return($cached);	
			}
		}
		// prepare the statement
		$this->Prepared = $this->Database['handle']->prepare($this->Query);
		// if prepare failed
		if(!$this->Prepared) {
			// prepare informations to be thrown
			$exception_infos = implode(":",$this->Database['handle']->ErrorInfo()).":$this->Query";
			// throw an exception
			Throw new Exception("giQuery->execute() : Failed to prepare query [{$exception_infos}]");
		}
		// execute the statement
		$this->Success = $this->Prepared->execute($this->Values);
		// if execution failed
		if($this->Success === false) {
			// prepare informations to be thrown
			$exception_infos = implode(":",$this->Database['handle']->ErrorInfo()).":$this->Query";
			// throw an exception
			Throw new Exception("giQuery->execute() : Failed to execute query [{$exception_infos}]");
		}
		// fetch all results
		$this->Result = $this->Prepared->fetchAll(
			// fetch as an object
			PDO::FETCH_CLASS,
			// of this specific class
			'giRecord',
			// and pass it some arguments
			array(trim($this->Table,"'"))
		);
		// if action was a pathtru and starts with UPDATE, INSERT or DELETE and Table was set and it succeeded
		if($this->Action == 'QUERY' AND in_array(substr($this->Query,0,6),array('INSERT','UPDATE','DELETE')) AND $this->Table AND $this->Success) {
			// we must notify the cache that this table has changed to prevent giving outdated cached data later on
			$this->updateOutdated();
		}
		// if action was DELETE or UPDATE or INSERT and succeeded, it altered a table state
		if(($this->Action == 'UPDATE' or $this->Action == 'DELETE' or $this->Action == 'INSERT') and $this->Success) {
			// we must notify the cache of the new modification date for this table
			$this->updateOutdated();
		}
		// if action succeeded and has some kind of useful result (SELECT or SELECT via a QUERY) and has a table set
		if(($this->Action == 'SELECT' or ($this->Action == 'QUERY' and substr($this->Query,0,6) == 'SELECT')) and $this->Table and $this->Success) {
			// place result in cache
			$this->putInCache();
		}
		// if action was UPDATE or DELETE or one of those via QUERY
		if(in_array($this->Action,array('UPDATE','DELETE')) or ($this->Action == 'QUERY' AND in_array(substr($this->Query,0,6),array('UPDATE','DELETE')) AND $this->Table)) {
			// return the number of affected rows
			return($this->Prepared->rowCount());
		}
		// if the query was an insert and it succeeded
		if($this->Action == 'INSERT' and $this->Success) {
			// instanciate a new query
			$this->Result = new giQuery($this->Database,$this->Cache);
			// get the newly inserted element from its id
			return($this->Result
				->select()
				->from($this->Table)
				->where(array('id'=>$this->Database['handle']->lastInsertId()))
				->execute()
			);
		}
		// return the results
		return($this->Result);
	}
	
	// if we want to tolerate some outdated queries to be retrieved from the cache
	public function addLag($lag) {
		// set how much outdated we are allowing
		$this->Lag = $lag*3600;
	}
	
	// set the table last modification so all older objects will be disregarded
	private function updateOutdated() {
		// if caching is enabled
		if($this->Cache['enabled']) {
			// try to update
			$oudatedUpdate = $this->Cache['handle']->replace($this->Cache['prefix'].'_lu_'.trim($this->Table,"'"),time());	
			// if the update failed
			if(!$outdatedUpdate) {
				// set the last modification date fot this table
				$this->Cache['handle']->set($this->Cache['prefix'].'_lu_'.trim($this->Table,"'"),time());
			}
		}
	}
	
	// check if an sql query is in cache
	private function isInCache() {
		// the cache is enabled
		if($this->Cache['enabled']) {
			// if we don't know on which table we're working
			if(!$this->Table) {
				// we cannot use the cache
				return(null);
			}
			// generate a hash for this specific query
			$this->generateQueryHash();
			// get the last date of the last time this query was cached
			$lastCachedQuery = $this->Cache['handle']->get("{$this->Cache['prefix']}_qt_{$this->Hash}");
			// if this query is totaly absent from the cache
			if(!$lastCachedQuery) {
				// nothing to return
				return(null);
			}
			// check the last time the table has changed (insert, update, delete)
			$lastTableUpdate = $this->Cache['handle']->get("{$this->Cache['prefix']}_lu_".trim($this->Table,"'"));
			// if we have no idea when the table last changed
			if(!$lastTableUpdate) {
				// assume the worst, that it just changed now
				$lastTableUpdate = time();
				// set the last modification date fot this table
				$this->Cache['handle']->set($this->Cache['prefix'].'_lu_'.trim($this->Table,"'"),$lastTableUpdate);
			}
			// if there is no lag tolerance
			if(!$this->Lag) {
				// if the cached query is posterior to the last time the table was updated
				if($lastCachedQuery > $lastTableUpdate) {
					// get the cached data and return it
					return($this->Cache['handle']->get("{$this->Cache['prefix']}_qd_{$this->Hash}"));	
				}
				// the last cached query is anterior to the last table update
				else {
					// nothing coherent to return but do not delete the cached query has some other requests might be more tolerant of lag time
					return(null);
				}
			}
			// else there is a lag tolerance
			else {
				// if the last cached query is fresh enough
				if($lastCachedQuery + $this->Lag > time()) {
					// get the cached data and return it
					return($this->Cache['handle']->get("{$this->Cache['prefix']}_qd_{$this->Hash}"));
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
	private function putInCache() {
		// if the cache is enabled
		if($this->Cache['enabled']) {
			// generate the query hash
			$this->generateQueryHash();
			// put the query result in cache
			$success = $this->Cache['handle']->set("{$this->Cache['prefix']}_qd_{$this->Hash}",$this->Result,MEMCACHE_COMPRESSED);
			// if the query successfuly made it into the cache
			if($success) {
				// set the query time
				$this->Cache['handle']->set("{$this->Cache['prefix']}_qt_{$this->Hash}",time());
			}
		}
	}
	
	// create a signature for this request
	private function generateQueryHash() {
		// hash based on query and associated values
		$this->Hash = md5(json_encode(array($this->Query,$this->Values)));
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
			if(substr_count($value,'/') == 2) {
				// set the separator
				$separator = '/';
			}
			// if the date is formated with two dots
			elseif(substr_count($value,'.') == 2) {
				// set the separator
				$separator = '.';
			}
			// if the date is formated with two -
			elseif(substr_count($value,'-') == 2) {
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
	
	// secure a column name
	private function secure($column) {
		// removes everything but letter and underscore
		$column = preg_replace('/[^a-zA-Z0-9_\.]/', '', $column);	
		// return cleaned column
		return($column);
	}

}

?>