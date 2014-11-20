<?php

class giHelper {
	
	// truncate a string
	public static function truncateString($string,$length) {
		// if string is longer than authorized
		if(strlen($string) > $length) {
			// truncate
			return(trim(mb_substr(strip_tags($string),0,$length-2,'UTF-8')).'…');
		}
		// string is ok
		else {
			// return as is
			return($string);
		}
	}
	
	// convert bytes to human size
	public static function humanSize($size,$precision=1) {
		// declare units
		$unit=array('b','Ko','Mo','Go','To','Po');
		// make human readable
		return @round($size/pow(1024,($i=floor(log($size,1024)))),$precision).' '.$unit[$i];
	}
	
	public static function relativeDate($time) {
		// get current time
		$now = time();
		// get difference
		$elapsed = $now - $time;
		// if more than a week ago
		if($elapsed > 3600 * 24 * 7) {
			return('Le '.date('d/m/y',$time));
		}
		// if more than two days ago
		elseif($elapsed > 3600 * 24 * 2) {
			return('Il y\'a '.round($elapsed/(3600*24)).' jours');
		}
		// if more than 24 hours
		elseif($elapsed > 3600 * 24) {
			return('Hier');
		}
		// if less than a day
		elseif($elapsed > 3600) {
			return('Il y\'a '.round($elapsed/(3600)).' heures');
		}
		// if less than an hour
		elseif($elapsed > 60) {
			return('Il y\'a '.round($elapsed/(60)).' minutes');
		}
		// if less than a minute
		elseif($elapsed > 5) {
			return('Il y\'a '.round($elapsed).' secondes');
		}
		// if less than five seconds
		else {
			return('À l\'instant');
		}
		
	}


	// slugify method
	public static function slugify($string,$preserve=false) {
		// if we want to preserve extension
		if($preserve) {
			// do something
			// ------------	
		}
		// list of allowed caracters
		$allowed	= '#[^a-z0-9\-]#';
		// separator
		$separator	= '-';
		// equivalents of accentuated caracters
		$accents_list = explode(".","à.á.â.ã.ä.ç.è.é.ê.ë.ì.í.î.ï.ñ.ò.ó.ô.õ.ö.ù.ú.û.ü.ý.ÿ.À.Á.Â.Ã.Ä.Ç.È.É.Ê.Ë.Ì.Í.Î.Ï.Ñ.Ò.Ó.Ô.Õ.Ö.Ù.Ú.Û.Ü.Ý. ._.'");
		$accents_free = explode(".","a.a.a.a.a.c.e.e.e.e.i.i.i.i.n.o.o.o.o.o.u.u.u.u.y.y.A.A.A.A.A.C.E.E.E.E.I.I.I.I.N.O.O.O.O.O.U.U.U.U.Y.-.-.-");
		// remove accents
		$slug = str_replace($accents_list, $accents_free, $string);
		// lowercase the string
		$slug = strtolower($slug);
		// replace all but 0-9 a-z
		$slug = preg_replace($allowed,$separator,$slug);
		// remove doubles
		$slug = str_replace($separator.$separator,$separator,$slug);
		// trim the edges
		$slug = trim($slug,$separator);
		// if string is empty
		if(strlen($slug) < 1) {
			// return null string
			$slug = 'null'; 
		}
		// if string is too long
		if(strlen($slug) > 244) {
			// shorten it
			$slug = substr($slug,0,244);
		}
		// return the slugified string
		return($slug);
	}
	
	// build html options/tags
	public static function buildHtmlOption($options) {
		if(is_array($options)) {
			$string	= '';
			foreach($options as $key => $value) {
				$string	.= $key.'="'.strip_tags($value).'" ';
			}
			$string		= " $string";
			return($string);
		}
		else {
			return('');
		}
	}
	
	public static function input($name,$value=null,$options=null) {
		$options = giHelper::buildHtmlOptions($options);
		$input = '<input name="'.$name.'" type="text" value="'.strip_tags($value).'"'.$options.'/>';
		return($input);
	}
	
	public static function password($name,$value=null,$options=null) {
		$options = giHelper::buildHtmlOptions($options);
		$password =  '<input name="'.$name.'" type="password" value="'.strip_tags($value).'"'.$options.'/>';
		return($password);
	}

	public static function textarea($name,$value=null,$options=null) {
		$options = giHelper::buildHtmlOptions($options);
		$textarea = '<textarea name="'.$name.'"'.$options.'>'.$value.'</textarea>';
		return($textarea);
	}

	public static function checkbox($name,$value=null,$options=null) {
		$options = giHelper::buildHtmlOptions($options);
		if($value == 'on') {
			$checked = ' checked="checked"';	
		}
		else {
			$checked = '';
		}
		$textarea = '<input type="checkbox"'.$checked.' name="'.$name.'"'.$options.' />';
		return($textarea);
	}

	public static function giSelect($name,$list,$value=null,$options=null) {
		$options = giHelper::buildHtmlOptions($options);
		$select = '<select name="'.$name.'" '.$options.'>';
		$opt_groups = array();
		
		foreach($list as $aListKey => $aListValue) {
			
			// if we have a multidim array with opt_groups
			if(is_array($aListValue)) {
				if(array_search($aListKey,$opt_groups)) {
					continue;	
				}
				$opt_groups[] = $aListKey;
				$select .= '<optgroup label="'.giStringFor($aListKey).'">';
				foreach($list[$aListKey] as $aListRealKey => $aListRealValue) {
					if(is_array($value) and in_array($aListRealKey,$value)) {
						$selected = ' selected="selected"';
					}
					elseif((string)$aListRealKey == (string)$value) {
						$selected = ' selected="selected"';
					}
					else {
						$selected = '';	
					}
					$select .= '<option value="'.$aListRealKey.'"'.$selected.'>'.giStringFor($aListRealValue).'</option>';	
				}
				$select .= '</optgroup>';
			}
			// else we have a simple list or multiple list
			else {
				if(is_array($value)) {
					if(in_array($aListKey,$value)) {
						$selected = ' selected="selected"';
					}
					else {
						$selected = '';	
					}
				}
				elseif((string)$aListKey == (string)$value) {
					$selected = ' selected="selected"';
				}
				else {
					$selected = '';	
				}				
				$select .= '<option value="'.$aListKey.'"'.$selected.'>'.giStringFor($aListValue).'</option>';	
			}
		
		
		}
		$select .= '</select>';
		return($select);
	}


} 

?>