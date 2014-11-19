<?php

class giQuery {

	protected	$Database;
	protected	$Query;
	
	protected	$Selects;
	protected	$Joins;
	protected	$Operators;
	protected	$Conditions;
	protected	$Updates;
	protected	$Inserts;
	protected	$Order;
	protected	$Limit;

	// instanciate a new query
	public function __construct() {
	
	}

	// on garbage collection	
	public function __destruct() {

	}
	
	// passrthu a query with values in needed
	public function query($query,$values=null) {
		
	}
	
	// first main method
	public function select($array=null) {
		
	}
	
	// second main method
	public function update($columns_and_values) {
		
	}
	
	// insert data
	public function insert($columns_and_values) {
		
	}
	
	// select the table
	public function from($table) {
		
	}
	
	// select another table to join on
	public function join($table_and_id) {
		
	}
	
	// add a condition
	public function where($conditions) {
		
	}
	
	// add into for inserts
	public function into($table) {
		
	}
	
	public function addAnd() {
	}
	
	public function addOr() {
	}
	
	// shortcuts
	public function whereStartsWith($column,$value) {
	}
	public function whereEndWith($column,$value) {
	}
	public function whereContains($column,$value) {
	}
	public function whereMatch($column,$value) {	
	}
	public function whereHigherThan($column,$value) {
	}
	public function whereLowerThan($column,$value) {
	}
	public function whereBetween($column,$lower,$higher) {
	}
	public function whereEmpty($column) {
	}
	public function whereNull($column) {
	}
	public function whereNotNull($column) {
	}
	public function whereTrue($column) {
	}
	public function whereFalse($column) {
	}
	
	// add an order clause
	public function orderBy() {
		
	}
	
	// add a limit clause
	public function limitTo() {
		
	}

	// execute the query
	public function execute() {
		
	}
	
	// get the results
	public function fetchObjects() {
		
	}
	
	// get the results
	public function fetchArray() {

	}


}

?>