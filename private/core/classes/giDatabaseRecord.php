<?php

// represent each entry retrieved from the database
class giDatabaseRecord {

	/*********************************************************************************/
	
	public		$id;
	private		$Table;
	private		$Database;

	/*********************************************************************************/

	public function __construct($Table,$Database) {
		$this->Table		= (string)	$Table;
		$this->Database		= (object)	$Database;
	}

	/*********************************************************************************/
	
	public function __wakeup() {
		global $giAbstractDatabase;
		$this->Database = $giAbstractDatabase->handle;
	}

	/*********************************************************************************/
	
	public function __sleep() {
		// remove the database connexion
		$this->Database = null;

		// attribute list
		$attributeList = array();

		// for each attribute
		foreach(get_object_vars($this) as $anAttributeKey => $anAttributeValue) {
			
			// inject in the table
			$attributeList[] = $anAttributeKey;
			
		}

		// list all attributes to serialize
		return($attributeList);
	}

	/*********************************************************************************/

	public function __toString() {
		return('[giDatabaseRecord:'.get_class($this).':'.ucfirst($this->Table).':'.$this->id.']');	
	}
	
	/*********************************************************************************/

	public function asArray($raw=null) {

		// delcare an array to be returned
		$convertedToArray = array();

		// for each attribute
		foreach(get_object_vars($this) as $anAttributeKey => $anAttributeValue) {
		
			// if we're not iterating over non-data elements
			if($anAttributeKey != 'Table' and $anAttributeKey != 'Database' and $anAttributeKey != 'quoteSymbol') {
				
				// if we want a raw object
				if($raw and (strpos($anAttributeKey,'_date') !== false or strpos($anAttributeKey,'date_') !== false)) {
					// insert the element
					$convertedToArray[$anAttributeKey] = $this->getRaw($anAttributeKey);

				}
				// not raw object
				else {
					// insert the element
					$convertedToArray[$anAttributeKey] = $this->get($anAttributeKey);
				}
			}
		}
		
		// return the converted element
		return($convertedToArray);

	}

	/*********************************************************************************/

	public function get($column) {
		if(strpos($column,'serialized') !== false or strpos($column,'array') !== false) {
			return(unserialize($this->{$column}));
		}
		/*
		elseif(strpos($column,'size') !== false) {
			return(giHumanSize($this->{$column}));
		}
		*/
		elseif(strpos($column,'_date') !== false or strpos($column,'date_') !== false) {
			if($this->{$column}) {
				return(date('d/m/y',$this->{$column}));
			}
			else {
				return('');	
			}
		}
		else {
			return((string)	stripslashes($this->{$column}));
		}
	}
	
	/*********************************************************************************/
	
	public function getRaw($column) {
		return((string)	$this->{$column});
	}
	
	/*********************************************************************************/
	
	public function set($column,$value) {
		if(strpos($column,'_array') !== false) {
			$this->{$column}	= (string)	json_encode($value);
		}
		else {
			$this->{$column}	= (string)	$value;
		}
	}

	/*********************************************************************************/
	
	public function getInteger($column) {
		return(intval($this->get($column)));
	}

	/*********************************************************************************/
	
	public function getFloat($column) {
		return(floatval($this->get($column)));
	}

	/*********************************************************************************/

	public function getSlug($column,$appendExtension=false) {
		return(giFramework::giSlugify($this->get($column),$appendExtension));
	}
	
	/*********************************************************************************/
	
	public function getInput($column,$options=null,$autocomplete=null) {
		return(
			giInput(
				$this->Table.'['.$column.']',
				$this->get($column),
				$options,
				$autocomplete
			)
		);
	}
	
	/*********************************************************************************/
	
	public function getTextarea($column,$options=null) {
		return(
			giTextarea(
				$this->Table.'['.$column.']',
				$this->get($column),
				$options
			)
		);	
	}
	
	/*********************************************************************************/
	
	public function getSelect($column,$list,$options=null) {
		return(
			giSelect(
				$this->Table.'['.$column.']',
				$list,
				$this->get($column),
				$options
			)
		);	
	}
	
	/*********************************************************************************/
	
	public function getSelectFor($column,$list,$key,$options=null) {
		$value = $this->get($column);
		return(
			giSelect(
				$this->Table.'['.$column.']['.$key.']',
				$list,
				$value[$key],
				$options
			)
		);	
	}
	
	/*********************************************************************************/
	
	public function getSelectMultiple($column,$list,$options=null) {
		$options['multiple'] = 'multiple';
		$value = $this->get($column);
		return(
			giSelect(
				$this->Table.'['.$column.'][]',
				$list,
				$value,
				$options
			)
		);
	}
	
	/*********************************************************************************/
	
	public function getCheckbox($column,$key,$options=null) {
		if($key != '') {
			$array	= $this->get($column);
			$field	= $array[$key];
			return(
				giCheckbox(
					$this->Table.'['.$column.']['.$key.']',
					$field,
					$options
				)
			);
		}
		else {
			return(
				giCheckbox(
					$this->Table.'['.$column.']',
					$this->get($column),
					$options
				)
			);
		}
	}
	
	
	/*********************************************************************************/
	
	public function save() {
		/********* THIS IS NOT SEXY BUT DOES THE TRICK *********/
		global $giDatabase;
		$result = $giDatabase->update($this->Table,$this->asArray(true),array('id'=>$this->id));
		return($result);
	}
	
	public function delete() {
		/********* THIS IS NOT SEXY BUT DOES THE TRICK *********/
		global $giDatabase;
		$result = $giDatabase->delete($this->Table,array('id'=>$this->id));
		return($result);
	}
	
	/*********************************************************************************/

}
?>