<?php

// represent an entry (or record) retrieved from the database
class giRecord {
	
	// stores internal information of the object
	private		$_;
	
	public function __construct($Table,$Database) {
		
		// set internals
		$this->_ = array(
			// id of the record
			'id'		=>$this->id,
			// database handle
			'database'	=>&$Database,
			// source table of the object
			'table'		=>$Table,
		);
		
	}

	// when this object is being put to sleep in memcached
	public function __sleep() {
	
		// remove the database connexion
		$this->_['database'] = null;
		
		// return self
		return($this);
		
	}
	
	// when ti object is retrieved by memcached
	public function __wakeup() {
		
	}

	public function __toString() {
		// format a string named according to the object
		return(ucfirst($this->_['table']).':'.$this->_['id']);	
	}

	public function asArray($raw=null) {
		// delcare an array to be returned
		$convertedToArray = array();
		// for each attribute
		foreach(get_object_vars($this) as $anAttributeKey => $anAttributeValue) {
			// if we're not iterating over the internal data array
			if($anAttributeKey != '_') {
				// if we want a raw object
				if($raw) {
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
		// return the converted object
		return($convertedToArray);
	}

	public function get($column) {
		if(strpos($column,'_array') !== false) {
			return(unserialize($this->{$column}));
		}
		/*
		elseif(strpos($column,'size') !== false) {
			return(giHumanSize($this->{$column}));
		}
		*/
		elseif(strpos($column,'_date') !== false) {
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
	
	public function getRaw($column) {
		return($this->{$column});
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