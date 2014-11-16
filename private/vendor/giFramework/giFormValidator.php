<?php

interface iFormValidator {
	
}

class giFormValidator implements iFormValidator {
	
	protected $formRegex;
	protected $savedFields;
	protected $failedFields;
	protected $formSeparator;

	public function __construct($addFieldTypes=null) {
		$this->formSeparator = (string) '--';
		$this->formRegex = array(
			'email' 		=> (string)	'#^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})$#i',
			'phone' 		=> (string)	'#^([0-9\s\+]{6,16})$#i',
			'numerical' 	=> (string)	'#^[0-9]+$#i',
			'puretext' 		=> (string)	'#^[a-zA-Z\s\-\s\']+$#i',
			'numtext' 		=> (string)	'#^[0-9a-zA-Z\-\s\']+$#i',
			'richtext' 		=> (string)	'#^(\n|\r|.)+$#i'
		);
		// if we want to add specific fields
		if(is_array($addFieldTypes)) {
			foreach($addFieldTypes as $aFieldType => $aFieldRegex) {
				$this->addFieldType($aFieldType,$aFieldRegex);
			}
		}
	}

	// add a new type of field with a specific regex
	public function addFieldType($fieldType,$fieldRegex) {
		$this->formRegex[$fieldType] = $fieldRegex;
	}

	// validate a whole form
	public function validateForm($formContent,$strictMode=false) {
		// for each field
		foreach($formContent as $fieldName => $fieldValue) {
			// if the field has to be validated
			if(strpos($fieldName,$this->formSeparator)) {
				// split the field name to obtain the type
				@list($fieldTitle,$fieldType,$isMandatory) = (array) explode($this->formSeparator,$fieldName);
				// if the field passed verification
				if($this->validateField($fieldValue,$fieldType)) {
					// add the value of the field to the saved results
					$this->savedFields[$fieldTitle] = strip_tags($fieldValue);
				}
				else {
					// add the error to the errors array
					$this->failedFields[$fieldTitle] = (string) '<br />Incorrect <!--<small>(type: '.$fieldType.')</small>-->';
					// set this field as empty
					$this->savedFields[$fieldTitle]	= (string) '';
				}	
			}
			elseif(substr($fieldName,0,3) != 'fv-') {
				// field is not subject to validation
				if(is_array($fieldValue)) {
					$this->savedFields[$fieldName]	= $fieldValue;
				}
				else {
					$this->savedFields[$fieldName]	= strip_tags($fieldValue);
				}
			}
		}

		if(count($this->failedFields) > 0) {
			// if at least one field failed to pass verifications we return an array of errors
			return(false);
		}
		else {
			if(@$formContent['fv-spam'] == '' and @$formContent['fv-repost'] == $_COOKIE['fv-repost']) {
				// if the spam field is empty and the cookie sessions match
				return(true);
			}
			else {
				// else we return false
				return(false);
			}
		}
		
	}
	
	// validate a specific field
	public function validateField($fieldContent,$fieldType,$strictMode=false) {
		$fieldContent = $this->removeAccents($fieldContent);
		if(is_array($this->formRegex[$fieldType])) {
			if(array_search($fieldContent,$this->formRegex[$fieldType]) !== false) {
				return(true);
			}
			else {
				return(false);
			}
		}
		else {
			if(preg_match($this->formRegex[$fieldType],$fieldContent)) {
				return(true);	
			}
			else {
				return(false);	
			}	
		}	
	}
	
	public function readPassedField($fieldName) {
		return(@$this->savedFields[$fieldName]);
	}
	
	public function readFailedField($fieldName) {
		if(@$this->failedFields[$fieldName] != '') {
			return('<i style="color: #cf0000;">'.$this->failedFields[$fieldName].'</i>');	
		}
			
	}
	
	public function readWholeForm() {
		return($this->savedFields);	
	}
	
	public function resetWholeForm() {
		$this->savedFields 		= array();
		$this->failedFields 	= array();
		setcookie('fv-repost',null);
	}
	
	public function removeAccents($string) {
		$search 	= (array)	explode(".","à.á.â.ã.ä.ç.è.é.ê.ë.ì.í.î.ï.ñ.ò.ó.ô.õ.ö.ù.ú.û.ü.ý.ÿ.À.Á.Â.Ã.Ä.Ç.È.É.Ê.Ë.Ì.Í.Î.Ï.Ñ.Ò.Ó.Ô.Õ.Ö.Ù.Ú.Û.Ü.Ý");
		$replace 	= (array)	explode(".","a.a.a.a.a.c.e.e.e.e.i.i.i.i.n.o.o.o.o.o.u.u.u.u.y.y.A.A.A.A.A.C.E.E.E.E.I.I.I.I.N.O.O.O.O.O.U.U.U.U.Y");
		$string 	= (string)	str_replace($search,$replace,$string);
		return($string);
	}
	
	public function generateFormSession() {
		$formSession = md5((integer)rand(5,999) * time() / rand(2,5));
		setcookie('fv-repost',$formSession,time() + 12 * 3600);
		return($formSession);	
	}
	
}

?>