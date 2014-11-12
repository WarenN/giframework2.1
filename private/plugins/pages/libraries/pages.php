<?php

function parseStatic($path,&$mapping) {
	// foreach thing in the folder to scan
	foreach(scandir($path) as $something) {
		// if the file+folder starts with a . ignore it
		if(substr($something,0,1) == '.') {
			// skip
			continue;	
		}
		// if it's a folder
		if(is_dir($path.$something)) {
			// recursive 
			parseStatic($path.$something.'/',$mapping);
		}
		// if it's a file
		elseif(strpos($something,'.php')) {
			// if it is named index.php
			if($something == 'index.php') {
				// alter the mapping to answer at /some/path/
				$mapping[] = array(
					'url'=>str_replace(array('../private/plugins/pages/handlers'),'',$path),
					'handler'=>str_replace(array('../private/plugins/pages/handlers','.php'),'',$path.$something)
				);
			}
			// it is a normal page
			else {
				// normal mapping
				$mapping[] = array(
					'url'=>str_replace(array('../private/plugins/pages/handlers','.php'),'',$path.$something).'.html',
					'handler'=>str_replace(array('../private/plugins/pages/handlers','.php'),'',$path.$something)
				);	
			}
			
		}
	}
}

?>