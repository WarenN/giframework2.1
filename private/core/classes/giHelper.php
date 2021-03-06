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

class giHelper {
	
	// truncate a string
	public static function truncate($string,$length) {
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
	
	public static function formatPhone($number) {
		// if there is no number
		if(!$number) {
			// nothing to return
			return(''); 
		}
		// clean the number
		$number = preg_replace("/[^0-9]/","",$number);
		// format it
		$number = substr($number,0,2) . ' ' . substr($number,2,2) . ' ' . substr($number,4,2) . ' ' . substr($number,6,2) . ' ' . substr($number,8,2);
		// return it
		return($number);	
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
	public static function buildHtmlOptions($options) {
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

	public static function select($name,$list,$value=null,$options=null) {
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
				$select .= '<optgroup label="'.giHelper::locale($aListKey).'">';
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
					$select .= '<option value="'.$aListRealKey.'"'.$selected.'>'.giHelper::locale($aListRealValue).'</option>';	
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
				$select .= '<option value="'.$aListKey.'"'.$selected.'>'.giHelper::locale($aListValue).'</option>';	
			}
		
		
		}
		$select .= '</select>';
		return($select);
	}
	
	public static function locale($string) {
		// access the app
		global $app;
		// return the translation
		return($app->Localization->translate($string));	
	}
	
	// this function generates a password
	public static function generatePassword($length=6) {
		// prepare a list of characters
		$passwordCharacters = 'abcdefghijklmnopqrstuvwxyz?+@&-,:_%=;!/.$[]*{}()#ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789?+@&-,:_%=;!/.$[]*{}()#';
		// get the length of this list
		$passwordCharactersLength = strlen($passwordCharacters) - 1;
		// initialize the password variable
		$generatedPassword = '';
		// iterate for each random character
		for($i=0;$i<$length;$i++) {
			// genereate one character at a time
			$generatedPassword .= $passwordCharacters[rand(0,$passwordCharactersLength)];
		}
		// if the generated password does not contain chars + capital leters + number we retry
		if(!preg_match('#((?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{4,255})#',$generatedPassword)) {
			// if the generated password isn't strong enough generate again
			return(giHelper::generatePassword($length));
		}
		else {
			// return the generated password
			return($generatedPassword);
		}
		
	}


} 

?>