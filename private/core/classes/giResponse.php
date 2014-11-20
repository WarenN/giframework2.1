<?php


class giResponse {

	protected $pageDefaults;
	protected $Type;				// handle the type of output (html, json, xml, csv, text, file)
	protected $Charset;				// the output encoding charset
	protected $Obfuscate;			// if we should obfuscate code
	protected $Indent;				// if we should indent source code
	protected $Headers;				// all headers in this array
	protected $Checksum;			// if the checksum is enabled
	protected $ExecutionTime;		// stores the execution time
	protected $StartTime;			// stores the start time
	protected $Formatted;			// if the content has been formated already
	protected $Caching;				// if the browser is allowed to cache this
	protected $Freeze;				// number or hours the file should be cached locally 
	protected $Redirect;			// url where we should be redirected
	protected $Environment;			// dev/prod
	protected $Modification;		// handle the modification date of the document
	protected $Length;				// handle the length of the document
	protected $Content;				// handle the page content
	protected $Javascripts;			// handle the list of javascript files
	protected $Stylesheets;			// handle the list of css files
	protected $Meta;				// handle the meta tags

	public function __construct() {
		$this->Formatted	= false; 	// the content is not formatted until it is!
		$this->BrowserCache = true; 	// default is to authorize browser to cache content
		$this->Cache		= false;	// default is to not cache the output
		$this->Charset		= 'utf-8'; 	// default encoding is always UTF8
		$this->Obfuscate	= false;	// default is not to obfuscate
		$this->Indent		= false;	// default is not to indent either
		$this->Checksum		= false;	// default is to disable checksum as it's resource consuming
		$this->Headers		= array();	// default is no headers at all
		$this->Meta			= array();	// default is no meta tags
		$this->Freeze		= false;	// default is to not freeze the output at all
	}
	
	// this methods set the default configuration
	public function setConfiguration($response=array(),$assets=array(),$meta=array(),$start_time,$environment) {
		
		// set default JS and CSS
		$this->setJs($assets['js']);
		$this->setCss($assets['css']);
		
		// set default meta tags
		$this->setMeta($meta);
		
		// set default parameters
		$this->Indent 		= (boolean) $response['enable_indentation'];
		$this->Checksum 	= (boolean) $response['enable_checksum'];
		$this->Obfuscate 	= (boolean) $response['enable_obfuscation'];
		$this->Compress 	= (boolean) $response['enable_compression'];
		$this->Cache 		= (boolean) $response['enable_cache'];
		$this->Type 		= (string) 	$response['default_type'];
		
		// set environment
		$this->Environment = $environment;
		
		// set start time
		$this->StartTime = $start_time;
		
	}
	
	// this methods formats content and set headers according to the paremeters
	public function formatContent() {
		
		// choose the proper mimetype
		switch($this->Type) {
			
			// in case we want to output html
			case 'html':
			
				// if a redirection is required
				if($this->Redirect and $this->Delay) {
				
					// build the redirection meta tag	
					$redirectionMetaTag = '<meta http-equiv="refresh" content="'.$this->Delay.'; url='.$this->Redirect.'">';
					
				}
				
				// no redirect meta tag
				else {
					
					// empty
					$redirectionMetaTag = '';
					
				}
			
				// set proper header
				$this->setHeader('Content-type','text/html; charset='.$this->getCharset());
				
				// build the page
				$temporaryContent = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="content-type" content="text/html; charset='.$this->getCharset().'" />'.$redirectionMetaTag;
		
				// for each meta tag
				foreach($this->Meta as $aMetaKey => $aMetaValue) {
					
					// build the meta
					$temporaryContent .= $this->buildMeta($aMetaKey,$aMetaValue);
					
				}
				
				// build stylesheets
				$temporaryContent .= $this->buildStylesheets();
				
				// build javascript
				$temporaryContent .= $this->buildJavascript();
				
				// actual content
				$temporaryContent .= '</head><body>'.$this->getContent().'</body></html>';		

				// set the formated content				
				$this->setContent($temporaryContent);

				// if we want to indent the code
				if($this->Indent == true) {
					// indent it
					$this->indentCode();	
				}
				// if we want to obfuscate
				elseif($this->Obfuscate == true) {
					// obfuscate it
					$this->obfuscateCode();	
				}					

			break;
			
			// output as plaintext
			case 'text':
			
				// set proper header
				$this->setHeader('Content-type','text/plain; charset='.$this->getCharset());
				
				// if a redirection is required
				if($this->Redirect and $this->Delay) {
				
					// header redirect
					header('Refresh: '.$this->Delay.'; url='.$this->Redirect);	
					
				}
				
				// if the content is an array
				if(is_array($this->getContent())) {
					
					// format that content so that is can be dumped
					$this->setContent(var_export($this->getContent(),true));
				}
			
			break;
			
			// output as a csv table
			case 'csv':
			
				// set proper header
				$this->setHeader('Content-type','text/csv; charset='.$this->getCharset());
				
				// if the content is an array
				if(is_array($this->getContent())) {
					
					// format at proper csv
					$this->setContent($this->arrayToCSV($this->getContent()));	
				}

			break;
			
			// output as json array
			case 'json':
			
				// set proper header
				$this->setHeader('Content-type','application/json; charset='.$this->getCharset());
				
				// convert the content
				$this->setContent(json_encode($this->getContent()));
				
			break;
			
			// output as xml
			case 'xml':
			
				// set proper header
				$this->setHeader('Content-type','text/xml; charset='.$this->getCharset());

				// creating object of SimpleXMLElement
				$xmlContent = new SimpleXMLElement("<?xml version=\"1.0\"?><root></root>");
				
				// function call to convert array to xml
				$this->arrayToXML($this->getContent(),$xmlContent);
				
				// set the formated content
				$this->setContent($xmlContent->asXML());
				
				
				// if we want to indent the code
				if($this->Indent == true) {
					// indent it
					$this->indentCode();	
				}
				// if we want to obfuscate
				elseif($this->Obfuscate == true) {
					// obfuscate it
					$this->obfuscateCode();	
				}	

			break;
			
			// output as css
			case 'css':
			
				// set proper header
				$this->setHeader('Content-type','text/css; charset='.$this->getCharset());
				
				// if we decide to obfuscate the code
				if($this->Obfuscate == true) {
		
					// obfuscate it
					$this->obfuscateCode();	
					
				}		

			break;
			
			// output as javascript
			case 'js':
			
				// set proper header
				$this->setHeader('Content-type','text/javascript; charset='.$this->getCharset());

				// if we decide to obfuscate the code
				if($this->Obfuscate == true) {
		
					// obfuscate it
					$this->obfuscateCode();	
					
				}	

			break;
			
			// in case of a file we would have to know : path
			case 'file':
				
				// output the proper modification date
				$this->setHeader('Last-Modified',date('r',filemtime($this->getContent())));
				
				// get mimetype
				$Fileinfo = finfo_open(FILEINFO_MIME);
				
				// set mimetype
				$this->setHeader('Content-type',finfo_file($Fileinfo,$this->getContent()));
				
				
			break;
			
			// missing type
			default:
			
				// output an error
				$this->error500('giResponse->formatContent() : Missing type');
				
			break;
			
		}

		// first this is to protect php's version
		$this->setHeader('X-Powered-By','Undisclosed');

		// second this is to protect the server's name
		$this->setHeader('Server','Undisclosed');
		
		// Content-MD5
		if($this->Checksum and $this->Type != 'file') {
			
			// compute and set the checksum
			$this->setHeader('Content-MD5',md5($this->getContent()));
			
		}
		
		// else if the type is a file
		elseif($this->Checksum and $this->Type == 'file') {
		
			// compute and set the checksum of the file
			$this->setHeader('Content-MD5',md5_file($this->Content));
			
		}

		// if the type is file
		if($this->Type == 'file') {
			
			// get the length of the file
			$this->setHeader('Content-length',filesize($this->getContent()));
				
		}
		// else normal file
		else {
		
			// get the length of the content string
			$this->setHeader('Content-length',strlen($this->getContent()));
			
		}

		// if we have a modification date
		if($this->Modification) {
			
			// output the proper modification date
			$this->setHeader('Last-Modified',date('r',$this->Modification));

		}
		
		// if cache is disabled -> specify Cache-control headers
		if(!$this->BrowserCache) {
		
			// output specific headers
			$this->setHeader('Cache-Control','must-revalidate, post-check=0, pre-check=0');
			
		}
			
		// set content language (DISABLED BECAUSE OF SCOPE ISSUES)
		//$this->setHeader('Content-Language',$giLocalization->getLanguage());
		
		// if in local mode
		if($this->Environment == 'local') {
		
			// provide the memory usage
			$this->setHeader('X-Memory-Usage',giHelper::humanSize(memory_get_usage(true)));
			
		}
		
		// compute the final execution time
		$this->ExecutionTime = round(microtime(true) - $this->StartTime,3).' sec';

		// set in header
		$this->setHeader('X-Execution-Time',$this->ExecutionTime);
		
		// if execution time is higher that 1 sec
		if($this->ExecutionTime >= 1) {
			
			// access the logger
			global $giLogger;
			
			// log a bad performance notice
			$giLogger->notice('Awful Performance '.$this->ExecutionTime);
			
		}
		// if execution time is higher that 1/3 sec
		elseif($this->ExecutionTime >= 0.33) {
			
			// access the logger
			global $giLogger;
			
			// log a bad performance notice
			$giLogger->notice('Bad Performance '.$this->ExecutionTime);
			
		}
		
	}
	
	// output the document to the browser
	public function output() {

		// compute the final execution time
		$this->ExecutionTime = round(microtime(true) - $this->StartTime,3).' sec';
		
		// format the document first
		$this->formatContent();

		// for each header
		foreach($this->Headers as $aHeaderKey => $aHeaderValue) {
			
			// output the header
			header("{$aHeaderKey}: {$aHeaderValue}");	
			
		}

		// if the type is file output from the file indicated as content
		if($this->Type == 'file') {
			
			// get the content from the file
			echo file_get_contents($this->getContent());
			
		}
		
		// content is not binary and is actually in the content variable
		else {
			
			// compress the generated content if we are authorized to
			$this->compressContent();
			
			// generated the cache file (if authorized)
			$this->freezeContent();
			
			// actually output the result
			echo $this->getContent();
			
		}
		
		// stop here
		exit();

	}
	
	// output the document to the disk
	public function save($destination,$keep_alive=false) {
		
		// if destination is missing
		if(!$destination) {
		
			// throw an error
			$this->error500('giOutput: missing_destination');
			
		}
		
		// format the document first
		$this->formatContent();
		
		// save it to the destination
		file_put_contents($destination,$this->getContent());
		
		// if we don't want to keep alive
		if(!$keep_alive) {
			
			// stop here
			exit();
			
		}

	}
	
	// set specific headers to force downloading the output
	public function download($filename,$force=false) {
		
		// if we want to force the download
		if($force) {
			
			// alter mimetype headers
			$this->setHeader('Content-Type','application/octet-stream');
			
		}
		
		// format the document
		$this->formatContent();
		
		// remove double quotes from filename
		$filename = str_replace('"',' ',$filename);

		// for each header
		foreach($this->Headers as $aHeaderKey => $aHeaderValue) {
			
			// output the header
			header("{$aHeaderKey}: {$aHeaderValue}");	
			
		}

		// set download headers
		header('Content-Description: File Transfer');
   		
   		// set filename and disposition
   		header('Content-Disposition: attachment; filename="'.$filename.'"');

		// if the type is file output from the file indicated as content
		if($this->Type == 'file') {
			
			// get the content from the file
			echo file_get_contents($this->getContent());
			
		}
		
		// content is not binary and is actually in the content variable
		else {
			
			// actually output the result
			echo $this->getContent();
			
		}
		
		// and exit
		exit();
		
	}

	// put the generated output in cache for X hours
	public function freezeFor($hours) {
		// set the freeze time
		$this->Freeze = intval($hours);
	}
	
	// compress the output if it's enabled in the configuration and authorized by the browser
	private function compressContent() {
		
		// if the configuration authorizes it
		if($this->Compress) {
			
			// gzip the content
			$this->setContent(gzencode($this->getContent()));
			
			// if checksum is enabled
			if($this->Checksum) {
				
				// update the checksum
				$this->Checksum = md5($this->getContent());
				
				// override the previous headers
				header('Content-MD5: '.$this->Checksum);
				
				// set headers for caching purpose
				$this->setHeader('Content-MD5',$this->Checksum);
				
			}
			
			// set encoding as gzip
			header('Content-Encoding: gzip');
			
			// set headers for caching purpose
			$this->setHeader('Content-Encoding','gzip');
			
			// update the length
			$this->Length = strlen($this->getContent());
			
			// ovveride the Content-Length 
			header('Content-Length: '.$this->Length);
			
			// set headers for caching purpose
			$this->setHeader('Content-Length',$this->Length);
			
		}
	}
	
	// set a specific header
	public function setHeader($key,$value) {
		// set key/value
		$this->Headers[$key] = $value;
	}
	
	// get a specific header
	public function getHeader($header) {
		// return that specific header's value
		return($this->Headers[$header]);
	}
	
	// get all headers
	public function getHeaders() {
		// return the whole array
		return($this->Headers);	
	}
	
	// set specific headers to force browser not to cache the output
	public function disableBrowserCache() {
		// set the cache as disabled
		$this->BrowserCache = false;
	}
		
	// enable MD5 checksum output in headers
	public function enableChecksum() {
		// enable checksum
		$this->Checksum = true;
	}
	
	// get the execution time of the script
	public function getExecutionTime() {
		// return the raw execution time
		return($this->ExecutionTime);
	}
	
	// malformed request
	public function error400($reason='400 Bad Request') {
		
		// clean previous buffer
		ob_get_clean();
		
		// force disable cache
		$this->disableBrowserCache();
		
		// set type text
		$this->setType('text');
		
		// set error header
		header('HTTP/1.0 400 Bad Request', true, 400);
		
		// set message
		$this->setContent(giStringFor($reason));
		
		// output
		$this->output();

	}
	
	// refuse access
	public function error403($reason='403 Forbidden') {
		
		// clean previous buffer
		ob_get_clean();
		
		// force disable cache
		$this->disableBrowserCache();
		
		// set type text
		$this->setType('text');
		
		// set error header
		header('HTTP/1.1 403 Forbidden', true, 403);
		
		// set message
		$this->setContent(giStringFor($reason));
		
		// output
		$this->output();
		
	}
	
	// file not found
	public function error404($reason='404 Not Found') {
		
		// clean previous buffer
		ob_get_clean();
		
		// force disable cache
		$this->disableBrowserCache();
		
		// set type text
		$this->setType('text');
		
		// set error header
		header('HTTP/1.1 404 Not Found', true, 404);
		
		// set message
		$this->setContent(giStringFor($reason));
		
		// output
		$this->output();
		
	}
	
	// declare internal error
	public function error500($reason='500 Internal Server Error') {
		
		// clean previous buffer
		ob_get_clean();
		
		// force disable cache
		$this->disableBrowserCache();
		
		// set type text
		$this->setType('text');
		
		// set error header
		header('HTTP/1.1 500 Internal Server Error', true, 500);
		
		// set message
		$this->setContent(giStringFor($reason));
		
		// output
		$this->output();
		
	}
	
	// service unavailable
	public function error503($reason='503 Service Unavailable') {
		
		// clean previous buffer
		ob_get_clean();
		
		// force disable cache
		$this->disableBrowserCache();
		
		// set type text
		$this->setType('text');
		
		// set error header
		header('HTTP/1.1 503 Service Unavailable', true, 503);
		
		// set message
		$this->setContent(giStringFor($reason));
		
		// output
		$this->output();
		
	}
	
	// hard redirect no wait
	public function redirect($destination) {
		// purge the buffer
		ob_get_clean();
		// force disable cache
		$this->disableBrowserCache();
		// hard location
		header('Location: '.$destination);
		// stop execution
		die();
	}
	
	// soft/hard redirect with a delay
	public function redirectAfter($destination,$delay=3) {
		
		// set the delay
		$this->Delay = intval($delay);
		
		// set the destination
		$this->Redirect = $destination;
		
	}
	
	// mandatory : set the type of the output
	public function setType($type) {
		$this->Type = $type;
	}
	
	// set the encoding of the output
	public function setCharset($charset) {
		$this->Charset = $charset;
	}
	
	// set the encoding of the output
	public function getCharset() {
		return(strtolower(strip_tags($this->Charset)));
	}
	
	// this method will freeze the generated content if caching is enabled
	private function freezeContent() {
		
		// if caching is disabled or an error occured
		if(!$this->Cache) {
		
			// stop here
			return;
			
		}
			
		// if $this->Freeze is set
		if($this->Freeze) {
			
			// add a from cache header
			$this->setHeader('X-From-Cache','true');
			
			// add the date of caching
			$this->setHeader('X-Cached-On',date('r'));
			
			// tell when the cache will expire
			$this->setHeader('X-Cached-Until',date('r',time()+intval($this->Freeze)*3600));
			
			// generate a signature
			$aSignature = giRouter::getSignature();
		
			// put the content in the cache
			file_put_contents('../private/data/cache/response/'.$aSignature.'.raw',$this->getContent());
			
			// put the content in the cache
			file_put_contents('../private/data/cache/response/'.$aSignature.'.json',json_encode($this->getHeaders()));
			
			// set the modification time to time()+ $this->Freeze
			touch('../private/data/cache/response/'.$aSignature.'.raw',time()+intval($this->Freeze)*3600);
			
			// set the modification time to time()+ $this->Freeze
			touch('../private/data/cache/response/'.$aSignature.'.json',time()+intval($this->Freeze)*3600);
			
		}	
		
	}
	
	private function buildMeta($metaName,$metaValue) {
		// if a meta value is set
		if($metaValue) {
			// build the meta
			return('<meta name="'.$metaName.'" content="'.$metaValue.'" />');
		}
		// no meta value is set
		else {
			// nothing to return
			return('');
		}
		
	}

	private function buildJavascript() {
		$this->Javascripts = array_unique($this->Javascripts);
		$output = '';
		foreach($this->Javascripts as $aJsFile) {
			$output .= '<script type="text/javascript" src="'. $aJsFile .'"></script>';
		}	
		return($output);
	}

	private function buildStylesheets() {
		$this->Stylesheets = array_unique($this->Stylesheets);
		$output = '';
		foreach($this->Stylesheets as $aCssFile) {
			$output .= '<link rel="stylesheet" media="all" type="text/css" href="'.$aCssFile.'" />';
		}
		return($output);
	}
	
	private function buildTitle() {
		return('<title>'.$this->Meta['title'].'</title>');
	}
	
	private function isNotNull($aString) {
		if($aString != null and $aString != '') {
			return(true);	
		}
		else {
			return(false);	
		}
	}

	// set meta tags
	public function setMeta($meta_tags=array()) {
		
		// for each provided tag
		foreach($meta_tags as $aMetaKey => $aMetaValue) {
			
			// set the tag with it key
			$this->Meta[$aMetaKey] = $aMetaValue;
			
		}
		
	}
	
	public function setContent($Content) {
		// force setting content
		$this->Content = $Content;
	}
	
	public function addContent($Content) {
		// force adding content
		$this->Content .= $Content;
	}
	
	public function getContent() {
		// return content as-is
		return($this->Content);		
	}
	
	public function setJs($pageJs) {
		// if provided javascript is an array
		if(is_array($pageJs) and count($pageJs) > 0) {
			// for each javascript file
			foreach($pageJs as $aJsFile) {
				// if the file has an absolute path or is a remote file
				if(substr($aJsFile,0,1) == '/' or substr($aJsFile,0,4) == 'http') {
					// put it raw
					$this->Javascripts[] = $aJsFile;
				}
				// the file is relative
				else {
					// push it
					$this->Javascripts[] = "/assets/js/$aJsFile";	
				}
			}	
		}
		// only one file was provieded
		else {
			// if its absolute or remote
			if(substr($pageJs,0,1) == '/' or substr($pageJs,0,4) == 'http') {
				// put it raw
				$this->Javascripts[] = $aJsFile;
			}
			// path is relative
			else {
				// push it
				$this->Javascripts[] = "/assets/js/$pageJs";	
			}	
		}
	}
	public function getJs() {
		return($this->Javascripts);
	}
	
	public function setCss($pageCss) {
		// if provided stylesheets is an array
		if(is_array($pageCss) and count($pageCss) > 0) {
			// for each file
			foreach($pageCss as $aCssFile) {
				// if the file is absolute or remote
				if(substr($aCssFile,0,1) == '/' or substr($aCssFile,0,4) == 'http') {
					// put it raw
					$this->Stylesheets[] = $aCssFile;	
				}
				// file is relative
				else {
					// push it
					$this->Stylesheets[] = "/assets/css/$aCssFile";	
				}
			}	
		}
		// only one file was provided
		else {
			// if the file is absolute or remote
			if(substr($pageCss,0,1) == '/' or substr($pageCss,0,4) == 'http') {
				// put it raw
				$this->Stylesheets[] = $pageCss;	
			}
			// path is relative
			else {
				// push it
				$this->Stylesheets[] = (string) "/assets/css/$pageCss";	
			}
		}
	}
	public function getCss() {
		return($this->Stylesheets);
	}

	public function clearJs() {
		$this->Javascripts = array();
	}

	public function clearCss() {
		$this->Stylesheets = array();
	}
	
	// converts an array to csv table
	private function arrayToCSV($contentArray) {
		// element to remove
		$removeFromCSV = array("\n","\t","\"");
		// delcare the csv content
		$csvContent = '';
		// for each line of the table
		foreach($contentArray as $aLine) {
			// for each element in that line
			foreach($aLine as $aField) {
				// remove special chars
				$aField = str_replace($removeFromCSV,' ',$aField);
				// add double quotes
				$csvContent .= '"'.$aField.'"'."\t";
			}	
			// end the line
			$csvContent .= "\n";
		}
		// return the formated content
		return($csvContent);
	}
	
	// converts an array to xml code
	private function arrayToXML($contentArray, &$xmlContent) {
		// for each element in the array
		foreach($contentArray as $key => $value) {
			// if the value itself is an array
			if(is_array($value)) {
				// if the key is numeric
				if(!is_numeric($key)){
					// add a subnode
					$subnode = $xmlContent->addChild("$key");
					// recurse
					$this->arrayToXML($value, $subnode);
				}
				else{
					// add a subnode and alter the key
					$subnode = $xmlContent->addChild("item$key");
					// recurse
					$this->arrayToXML($value, $subnode);
				}
			}
			// value is not an array
			else {
				// simply add the child
				$xmlContent->addChild("$key","$value");
			}
		}
	}
	
	// removes anything that is not necessary 
	private function obfuscateCode() {
		// get the content of the page
		$sourceCode = $this->getContent();
		// list characters to removes
		$source		= array("\t","\n","\r");
		// remove said characters
		$sourceCode = str_replace($source,"",$sourceCode);
		// remove double spaces
		$sourceCode = str_replace("  "," ",$sourceCode);
		// update the content
		$this->setContent($sourceCode);
	}

	// indent HTML code
	private function indentCode() {
		// get the current content
		$sourceCode = $this->getContent();
		// set the indenter symbol
		$indenter	= "\t";
		// remove all specific symbols
		$sourceCode 	= str_replace(array("\t","\n","\r"), '', $sourceCode);
		$sourceCode 	= ereg_replace(">( )*", ">", $sourceCode);
		$sourceCode 	= ereg_replace("( )*<", "<", $sourceCode);
		$level 		= 0;
		$sourceCode_len = strlen($sourceCode);
		$pt 		= 0;
		// parse each caracter
		while ($pt < $sourceCode_len) {
			if ($sourceCode{$pt} === '<') {
				$started_at = $pt;
				$tag_level = 1;
				if ($sourceCode{$pt+1} === '/') {
					$tag_level = -1;
				}
				if ($sourceCode{$pt+1} === '!') {
					$tag_level = 0;
				}
				while ($sourceCode{$pt} !== '>') {
					$pt++;
				}
				if ($sourceCode{$pt-1} === '/') {
					$tag_level = 0;
				}
				$tag_lenght = $pt+1-$started_at;
				if ($tag_level === -1) {
					$level--;
				}
				$array[] = @str_repeat($indenter, $level).substr($sourceCode, $started_at, $tag_lenght);
				if ($tag_level === 1) {
					$level++;
				}
			}
			if (($pt+1) < $sourceCode_len) {
				if ($sourceCode{$pt+1} !== '<') {
					$started_at = $pt+1;
					while ($sourceCode{$pt} !== '<' && $pt < $sourceCode_len) {
						$pt++;
					}
					if ($sourceCode{$pt} === '<') {
						$tag_lenght = $pt-$started_at;
						$array[] = @str_repeat($indenter, $level).substr($sourceCode, $started_at, $tag_lenght);
					}
				} else {
					$pt++;
				}
			} else {
				break;
			}
		}
		// re-assemble all the lines
		$sourceCode = implode($array, "\n");
		// update the content
		$this->setContent($sourceCode);
	}

}

?>