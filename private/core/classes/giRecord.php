<?php

// represent an entry (or record) retrieved from the database
class giRecord {
	
	// stores internal information of the object
	private		$_;

	// instanciate a child
	public function __construct($table) {
		// set internals
		$this->_ = array(
			// id of the record
			'id'		=>$this->id,
			// source table of the object
			'table'		=>$table,
		);
	}

	public function __toString() {
		// format a string named according to the object
		return(ucfirst($this->_['table']).':'.$this->_['id']);	
	}

	public function asArray($raw=false) {
		// delcare an array to be returned
		$convertedToArray = array();
		// for each attribute
		foreach(get_object_vars($this) as $anAttributeKey => $anAttributeValue) {
			// if we're not iterating over the internal data array
			if($anAttributeKey != '_') {
				// if we want a raw object
				if($raw and strpos($anAttributeKey,'_date') !== false) {
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
	
	// get the content of a column
	public function get($column) {
		// if the column contains an array
		if(strpos($column,'_array') !== false) {
			// decode the array
			return(json_decode($this->{$column}));
		}
		// if the column contains a serialized file
		elseif(strpos($column,'_file') !== false) {
			// get the file informations
			// -------------------------
		}
		// if the column contains a size value
		elseif(strpos($column,'_size') !== false) {
			// convert to human size
			return(giHelper::humanSize($this->{$column}));
		}
		// if the column contains a date
		elseif(strpos($column,'_date') !== false) {
			// if the value is set
			if(!empty($this->{$column})) {
				// create the date using raw unix epoch
				return(date('d/m/y',$this->{$column}));
			}
			// value is empty
			else {
				// return an empty string
				return('');	
			}
		}
		// column is normal
		else {
			// return the raw contents
			return(stripslashes($this->{$column}));
		}
	}
	
	// get the raw content of an attribute
	public function getRaw($column) {
		// return as is
		return($this->{$column});
	}
	
	// set an attribute
	public function set($column,$value) {
		// set the value as is
		$this->{$column} = $value;
	}

	// escape html entities
	public function getScreenSafe($column) {
		// escape all html entities
		return(htmlentities($this->get($column)));
	}
	
	// get as an interger
	public function getInteger($column) {
		// convert
		return(intval($this->get($column)));
	}

	// get a float value
	public function getFloat($column) {
		// convert
		return(floatval($this->get($column)));
	}

	public function getSlug($column,$extension=false) {
		return(giHelper::slugify($this->get($column),$extension));
	}
	
	public function getInput($column,$options=null) {
		return(
			giHelper::input(
				"{$this->Table}[$column]",
				$this->get($column),
				$options
			)
		);
	}
	
	public function getTextarea($column,$options=null) {
		return(
			giHelper::textarea(
				"{$this->Table}[$column]",
				$this->get($column),
				$options
			)
		);	
	}
	
	public function getSelect($column,$list,$options=null) {
		return(
			giHelper::select(
				"{$this->Table}[$column]",
				$list,
				$this->get($column),
				$options
			)
		);	
	}
	
	public function getSelectFor($column,$list,$key,$options=null) {
		$value = $this->get($column);
		return(
			giHelper::selectFor(
				"{$this->Table}[$column][$key]",
				$list,
				$value[$key],
				$options
			)
		);	
	}
		
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
	
	// save self record
	public function save() {
		// if id is missing
		if(!$this->_['id']) {
			// throw an exception
			Throw new Exception('giRecord->save() : Cannot save record without its id');
		}
		// if the table is missing
		if(!$this->_['table']) {
			// throw an exception
			Throw new Exception('giRecord->save() : Cannot save record without its table name');
		}
		// access the app
		global $app;
		// get a new query
		$query = $app->Database->query();
		// build the save query
		$status = $query
			->update($this->_['table'])
			->set($this->asArray(true))
			->where(array('id'=>$this->_['id']))
			->execute();
		// return the status
		return($status);
	}
	
	// delete self record
	public function delete() {
		// if id is missing
		if(!$this->_['id']) {
			// throw an exception
			Throw new Exception('giRecord->save() : Cannot delete record without its id');
		}
		// if the table is missing
		if(!$this->_['table']) {
			// throw an exception
			Throw new Exception('giRecord->save() : Cannot delete record without its table name');
		}
		// access the app
		global $app;
		// get a new query
		$query = $app->Database->query();
		// build the save query
		$status = $query
			->delete()
			->from($this->_['table'])
			->where(array('id'=>$this->_['id']))
			->execute();
		// return the status
		return($status);
	}
	

}
?>