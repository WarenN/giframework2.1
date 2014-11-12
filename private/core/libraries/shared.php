<?php

// provides a string meeting some requierments
function giFormatString($aString,$someOptions=null) {
	$aString			= (string)	$aString;
	if(isset($someOptions['truncate'])) {
		$truncateLength	= (integer)	intval($someOptions['truncate']);
		if(strlen($aString) > $truncateLength) {
			$aString = trim(mb_substr(strip_tags($aString),0,$truncateLength-2,'UTF-8')).'...';
		}
	}
	if(isset($someOptions['case'])) {
		switch($someOptions['case']) {
			case 'first':
				$aString = strtolower($aString);
				$aString = ucfirst($aString);
			break;
			case 'allfirst':
				$aString = strtolower($aString);
				$aString = ucwords($aString);
			break;
			case 'lower':
				$aString = strtolower($aString);
			break;
			case 'upper':
				$aString = strtoupper($aString);
			break;
			default;	
		}
	}
	if(isset($someOptions['escape'])) {
		$aString = strip_tags($aString);
	}
	return($aString);
}

// include a file from a partial folder (from a plugin)
function giPartial($partial) {		
	// access the request handler
	global $giRequest;
	// remove double points as a security
	str_replace('..','.',$partial);
	// build the fullpath
	$partialPath = dirname($giRequest->getHandler()).'/../partials/'.$partial.'.php';
	// if the file is there
	if(file_exists($partialPath)) {
		// include that file
		include($partialPath);
	}
	// the file is missing
	else {
		// access the output
		global $giOutput;
		// provide an error
		$giOutput->error500('missing_partial '.$partialPath);	
	}
}

// include a file from the components folder
function giComponent($component) {
	// include the component
	include('../private/core/components/'.$component.'.php');
}


// return a string for the specified key, depending on the current language
function giStringFor($localeKey) {
	global $giLocalization;
	return($giLocalization->getLocale($localeKey));
}

// if the method is post
function giIsMethodPost() {
	if($_SERVER['REQUEST_METHOD'] == 'POST') {
		return(true);	
	}
	else {
		return(false);
	}
}

// if the method is get
function giIsMethodGet() {
	if($_SERVER['REQUEST_METHOD'] == 'GET') {
		return(true);	
	}
	else {
		return(false);
	}
}

// convert bytes to human size
function giHumanSize($bytes, $precision = 1) {  
    $kilobyte = 1000;
    $megabyte = $kilobyte * 1000;
    $gigabyte = $megabyte * 1000;
    $terabyte = $gigabyte * 1000;
    if (($bytes >= 0) && ($bytes < $kilobyte)) {
        return $bytes . ' B';
    } elseif (($bytes >= $kilobyte) && ($bytes < $megabyte)) {
        return round($bytes / $kilobyte, $precision) . ' Ko';
    } elseif (($bytes >= $megabyte) && ($bytes < $gigabyte)) {
        return round($bytes / $megabyte, $precision) . ' Mo';
    } elseif (($bytes >= $gigabyte) && ($bytes < $terabyte)) {
        return round($bytes / $gigabyte, $precision) . ' Go';
    } elseif ($bytes >= $terabyte) {
        return round($bytes / $terabyte, $precision) . ' To';
    } else {
        return $bytes . ' B';
    }
}

// get the date relative to now
function getRelativeDate($previousTime) {
	
	$currentTime = time();
	
	$elapsedTime = $currentTime - $previousTime;
	
	if($elapsedTime > 3600 * 24 * 7) {
		return('Le '.date('d/m/y',$previousTime));
	}
	elseif($elapsedTime > 3600 * 24 * 2) {
	
		return('Il y\'a '.round($elapsedTime/(3600*24)).' jours');
	
	}
	elseif($elapsedTime > 3600 * 24) {
		return('Hier');
	}
	elseif($elapsedTime > 3600) {
		return('Il y\'a '.round($elapsedTime/(3600)).' heures');
	}
	elseif($elapsedTime > 60) {
		return('Il y\'a '.round($elapsedTime/(60)).' minutes');
	}
	else {
		return('Il y\'a '.round($elapsedTime).' secondes');
	}

}


// slugify method
function giSlugify($aString, $appendExtension = true) {
	$extensionAppend	= (string)	'.html';
	$authorizedChars	= (string)	'#[^a-z0-9\-]#';
	$separatorSymbol	= (string)	'-';
	$accentsList		= (array)	explode			(".","à.á.â.ã.ä.ç.è.é.ê.ë.ì.í.î.ï.ñ.ò.ó.ô.õ.ö.ù.ú.û.ü.ý.ÿ.À.Á.Â.Ã.Ä.Ç.È.É.Ê.Ë.Ì.Í.Î.Ï.Ñ.Ò.Ó.Ô.Õ.Ö.Ù.Ú.Û.Ü.Ý. ._.'");
	$accentsFree		= (array)	explode			(".","a.a.a.a.a.c.e.e.e.e.i.i.i.i.n.o.o.o.o.o.u.u.u.u.y.y.A.A.A.A.A.C.E.E.E.E.I.I.I.I.N.O.O.O.O.O.U.U.U.U.Y.-.-.-");
	$aSlugifiedString	= (string)	str_replace		($accentsList, $accentsFree, $aString);
	$aSlugifiedString	= (string)	strtolower		($aSlugifiedString);
	$aSlugifiedString	= (string)	preg_replace	($authorizedChars, $separatorSymbol, $aSlugifiedString);
	$aSlugifiedString	= (string)	str_replace		($separatorSymbol.$separatorSymbol,$separatorSymbol,$aSlugifiedString);
	$aSlugifiedString	= (string)	trim			($aSlugifiedString, $separatorSymbol);
	
	if(strlen($aSlugifiedString) < 1) {
		$aSlugifiedString	= (string)	'null'; 	
	}
	if(strlen($aSlugifiedString) > 244) {
		$aSlugifiedString	= (string)	substr($aSlugifiedString,0,244);
	}
	if($appendExtension) {
		$aSlugifiedString	.=	$extensionAppend;
	}
	return($aSlugifiedString);
}

function giBuildHtmlOptions($optionsArray=null) {
	if($optionsArray) {
		$optionsString		= (string)	'';
		foreach($optionsArray as $optionKey => $optionValue) {
			$optionsString	.= $optionKey.'="'.strip_tags($optionValue).'" ';
		}
		$optionsString		= ' '.$optionsString;
		return($optionsString);
	}
	else {
		return('');
	}
}

function giInput($name,$value=null,$options=null,$autocomplete=null) {
	// autocompletion support
	if($autocomplete['table'] and $autocomplete['field'] and $autocomplete['class']) {
		// if no id is set
		if(!$options['id']) {
			// randimize an id
			$options['id'] = md5(rand(99999,999999999));	
		}
		// access the apage variable
		global $aPage;
		// inject the javascript library
		$aPage['pageJavascript'][] = 'giAutocomplete.js';
		// append the javascript
		$append			= '<script type="text/javascript">';
		$append			.= 'window.addEvent("domready",function(){';
		$append			.= 'giAutocomplete(\''.$options['id'].'\',\''.$autocomplete['table'].'\',\''.$autocomplete['field'].'\',\''.$autocomplete['class'].'\');';
		$append			.= '});';
		$append			.= '</script>';	
	}
	// if to autocomplete
	else {
		$append			= (string)	'';
	}
	$options			= (string)	giBuildHtmlOptions($options);
	$input				= (string)	'<input name="'.$name.'" type="text" value="'.strip_tags($value).'"'.$options.'/>';
	return($input.$append);
}

function giPassword($name,$value=null,$options=null) {
	$options			= (string)	giBuildHtmlOptions($options);
	$password			= (string)	'<input name="'.$name.'" type="password" value="'.strip_tags($value).'"'.$options.'/>';
	return($password);
}

function giTextarea($name,$value=null,$options=null) {
	$options			= (string)	giBuildHtmlOptions($options);
	$textarea			= (string)	'<textarea name="'.$name.'"'.$options.'>'.$value.'</textarea>';
	return($textarea);
}

function giSelect($name,$list,$value=null,$options=null) {
	$options			= (string)	giBuildHtmlOptions($options);
	$select				= (string)	'<select name="'.$name.'" '.$options.'>';
	$optgroups			= array();
	
	foreach($list as $aListKey => $aListValue) {
		
		// if we have a multidim array with optgroups
		if(is_array($aListValue)) {
			if(array_search($aListKey,$optgroups)) {
				continue;	
			}
			$optgroups[]	= $aListKey;
			$select			.= '<optgroup label="'.giStringFor($aListKey).'">';
			foreach($list[$aListKey] as $aListRealKey => $aListRealValue) {
				if(is_array($value) && in_array($aListRealKey,$value)) {
					$selected	= (string)	' selected="selected"';
				}
				elseif((string)$aListRealKey == (string)$value) {
					$selected	= (string)	' selected="selected"';
				}
				else {
					$selected	= (string)	'';	
				}
				$select		.= '<option value="'.$aListRealKey.'"'.$selected.'>'.giStringFor($aListRealValue).'</option>';	
			}
			$select			.= '</optgroup>';
		}
		// else we have a simple list or multiple list
		else {
			if(is_array($value)) {
				if(in_array($aListKey,$value)) {
					$selected	= (string)	' selected="selected"';
				}
				else {
					$selected	= (string)	'';	
				}
			}
			elseif((string)$aListKey == (string)$value) {
				$selected	= (string)	' selected="selected"';
			}
			else {
				$selected	= (string)	'';	
			}				
			$select			.= '<option value="'.$aListKey.'"'.$selected.'>'.giStringFor($aListValue).'</option>';	
		}
	
	
	}
	$select				.= '</select>';
	return($select);
}

function giCheckbox($name,$value=null,$options=null) {
	$options			= (string)	giBuildHtmlOptions($options);
	if($value == 'on') {
		$checked		= (string)	' checked="checked"';	
	}
	else {
		$checked		= (string)	'';
	}
	$textarea			= (string)	'<input type="checkbox"'.$checked.' name="'.$name.'"'.$options.' />';
	return($textarea);
}

if(!function_exists('getallheaders')) { 
	function getallheaders() { 
		$headers = ''; 
		foreach ($_SERVER as $name => $value) { 
			if (substr($name, 0, 5) == 'HTTP_') { 
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
			} 
		} 
		return $headers; 
	} 
} 

?>