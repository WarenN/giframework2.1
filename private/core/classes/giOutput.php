<?php

interface iOutput {
	
}

class giOutput {

	protected $pageDefaults;
	protected $Type;				// handle the type of output (html, json, xml, csv, text, file)
	protected $Charset;				// the output encoding charset
	protected $Obfuscate;			// if we should obfuscate code
	protected $Indent;				// if we should indent source code
	protected $Headers;				// all headers in this array
	protected $Checksum;			// if the checksum is enabled
	protected $ExecutionTime;		// stores the execution time
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
	protected $Title;				// handle the page title
	protected $Description;			// handle the page description
	protected $Keywords;			// handle the page keywords
	protected $Language;			// handle the page language
	protected $Copyright;			// handle the page copyright
	protected $Author;				// handle the page author
	protected $Company;				// handle the page company
	protected $RevisitAfter;		// handle the page meta revisit
	protected $RobotRule;			// handle the robot rules
	protected $Position;			// handle the page geo position
	protected $Placename;			// handle the page geo placename
	protected $Region;				// handle the page geo region
	protected $googleAnalyticsId;	// handle the google analytics id
	protected $googleSiteVerification;

	public function __construct() {
		$this->Formatted	= false; 	// the content is not formatted until it is!
		$this->Caching		= true; 	// default is to authorize browser to cache content
		$this->Charset		= 'utf-8'; 	// default encoding is always UTF8
		$this->Obfuscate	= false;	// default is not to obfuscate
		$this->Indent		= false;	// default is not to indent either
		$this->Checksum		= false;	// default is to disable checksum as it's resource consuming
		$this->Headers		= array();	// default is no headers at all
		$this->Freeze		= false;	// default is to not freeze the output at all
	}
	
	// this methods allows to preconfigure a document using an object of class giConfiguration
	public function autoConfigure($giConfiguration) {

		// get the whole configuration
		$giConfigurationArray			= $giConfiguration->getConfiguration();
	
		// general output parameters	
		$this->Obfuscate				= (boolean)	$giConfiguration->isObfuscationEnabled();
		$this->Indent					= (boolean)	$giConfiguration->isIndentationEnabled();
		$this->Environment				= (string)	$giConfiguration->getEnvironment();
		
		// set default meta options
		$this->Title					= $giConfigurationArray['pageTitle'];
		$this->Description				= $giConfigurationArray['pageDescription'];
		$this->Keywords					= $giConfigurationArray['pageKeywords'];
		$this->Language					= $giConfigurationArray['pageLanguage'];
		$this->Author					= $giConfigurationArray['pageAuthor'];
		$this->Copyright				= $giConfigurationArray['pageCopyright'];
		$this->Company					= $giConfigurationArray['pageCompany'];
		$this->RevisitAfter				= $giConfigurationArray['pageRevisitRate'];
		$this->RobotRule				= $giConfigurationArray['pageRobotRule'];
		$this->Position					= $giConfigurationArray['pageGeoPosition'];
		$this->Placename				= $giConfigurationArray['pageGeoPlacename'];
		$this->Region					= $giConfigurationArray['pageGeoRegion'];
		$this->googleSiteVerification	= $giConfigurationArray['pageGoogleSiteVerification'];
		$this->googleAnalyticsId		= $giConfigurationArray['pageGoogleAnalyticsId'];

		// set default JS and CSS
		$this->setJs($giConfigurationArray['pageJavascript']);
		$this->setCss($giConfigurationArray['pageStylesheets']);
		
	}
	
	// this methods formats content and set headers according to the paremeters
	public function formatContent() {
		
		// access global configuration + localization handler + request handler
		global $giConfiguration,$giRequest,$giLocalization;
		
		// choose the proper mimetype
		switch($this->Type) {
			
			// in case we want to output html
			case 'html':
			
				// if a redirection is required
				if($this->Redirect) {
				
					// build the redirection meta tag	
					$redirectionMetaTag = "\n\t\t".'<meta http-equiv="refresh" content="'.$this->Delay.'; url='.$this->Redirect.'">';
					
				}
				
				// no redirect meta tag
				else {
					
					// empty
					$redirectionMetaTag = '';
					
				}
			
				// set proper header
				$this->setHeader('Content-type','text/html; charset='.$this->getCharset());
				
				// build the page
				$this->setContent('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="content-type" content="text/html; charset='.$this->getCharset().'" />'.$redirectionMetaTag.'
'.$this->buildTitle().'
'.$this->buildMeta('title',						$this->getTitle()).'
'.$this->buildMeta('description',				$this->getDescription()).'
'.$this->buildMeta('keywords',					$this->getKeywords()).'
'.$this->buildMeta('language',					$giLocalization->getLanguage()).'
'.$this->buildMeta('copyright',					$this->getCopyright()).'
'.$this->buildMeta('author',					$this->getAuthor()).'
'.$this->buildMeta('company',					$this->getCompany()).'
'.$this->buildMeta('robots',					$this->getRobotRule()).'
'.$this->buildMeta('revisit-after',				$this->getRevisitAfter()).'
'.$this->buildMeta('geo.position',				$this->getPosition()).'
'.$this->buildMeta('geo.placename',				$this->getPlacename()).'
'.$this->buildMeta('geo.region',				$this->getRegion()).'
'.$this->buildMeta('google-site-verification',	$this->getGoogleSiteVerification()).'
'.$this->buildStylesheets().'
'.$this->buildJavascript().'
	</head>
	<body>
'.$this->getContent().'
	</body>
</html>');
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
				
				// get and set the mimetype
				$Fileinfo = finfo_open(FILEINFO_MIME);
				$this->setHeader('Content-type',finfo_file($Fileinfo,$this->getContent()));
				
				
			break;
			
			// missing type
			default:
			
				// output an error
				$this->error500('giOutput: missing_type');
				
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
		if(!$this->Caching) {
		
			// output specific headers
			$this->setHeader('Cache-Control','must-revalidate, post-check=0, pre-check=0');
			
		}
			
		// set content language
		$this->setHeader('Content-Language',$giLocalization->getLanguage());
		
		// compute the final execution time
		$this->ExecutionTime = round(microtime(true) - $giConfiguration->getStartTime(),3).' sec';

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
		
		// access required objects
		global $giConfiguration, $giRequest, $giDebug, $giAuthentication;
		
		// if type is html AND debug is enabled in the config AND debug enabled by the request
		if($this->Type == 'html' and $giConfiguration->isDebugEnabled() and $giRequest->isDebugEnabled()) {
			
			// compute the final execution time
			$this->ExecutionTime = round(microtime(true) - $giConfiguration->getStartTime(),3).' sec';
			
			// add the debugging code to the actual content
			$this->Content .= $giDebug->getDebugHTML();
			
		}
		// if content is an array and debug is enabled
		elseif(is_array($this->Content)  and $giConfiguration->isDebugEnabled() and $giRequest->isDebugEnabled()) {
	
			// push the debug elements
			$this->Content['debug'] = $giDebug->getDebugArray();
			
		}

		// if type is html and a google tracking id is set and we're in production
		if($this->Type == 'html' and $this->googleAnalyticsId and $giConfiguration->getEnvironment() == 'prod') {

			// push the tracking code
			$this->Content .= $this->getTrackerCode();

		}
		
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
			
			// first disable caching
			$this->disableCache();
			
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
	public function compressContent() {
	
		// access the configuration and request
		global $giConfiguration,$giRequest;
		
		// if the configuration authorizes it
		if($giConfiguration->isCompressionEnabled() and $giRequest->isCompressionEnabled()) {
			
			// gzip the content
			$this->setContent(gzencode($this->getContent()));
			
			// if checksum is enabled
			if($this->Checksum) {
				
				// update the checksum
				$this->Checksum = md5($this->getContent());
				
				// override the previous headers
				header('Content-MD5: '.$this->Checksum);
				$this->setHeader('Content-MD5',$this->Checksum);
				
			}
			
			// override the Content-Encoding
			header('Content-Encoding: gzip');
			$this->setHeader('Content-Encoding','gzip');
			
			// update the length
			$this->Length = strlen($this->getContent());
			
			// ovveride the Content-Length 
			header('Content-Length: '.$this->Length);
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
	public function disableCache() {
		// set the cache as disabled
		$this->Caching = false;
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
		ob_get_clean();
		header('HTTP/1.0 400 Bad Request', true, 400);
		header('Content-type: text/plain');
		die(giStringFor($reason));
	}
	
	// refuse access
	public function error403($reason='403 Forbidden') {
		ob_get_clean();
		header('HTTP/1.1 403 Forbidden');
		header('Content-type: text/plain');
		die(giStringFor($reason));
	}
	
	// file not found
	public function error404($reason='404 Not Found') {
		ob_get_clean();
		header('HTTP/1.1 404 Not Found');
		header('Content-type: text/plain');
		die(giStringFor($reason));
	}

	// method not allowed
	public function error405($reason='405 Method Not Allowed') {
		ob_get_clean();
		header('HTTP/1.1 405 Method Not Allowed');
		header('Content-type: text/plain');
		die(giStringFor($reason));
	}
	
	// declare internal error
	public function error500($reason='500 Internal Server Error') {
		ob_get_clean();
		header('HTTP/1.1 500 Internal Server Error');
		header('Content-type: text/plain');
		die(giStringFor($reason));
	}
	
	// service unavailable
	public function error503($reason='503 Service Unavailable') {
		ob_get_clean();
		header('HTTP/1.1 503 Service Unavailable');
		header('Content-type: text/plain');
		die(giStringFor($reason));
	}
	
	// hard redirect no wait
	public function redirect($destination) {
		// purge the buffer
		ob_get_clean();
		// hard location
		header('Location: '.$destination);
		// stop execution
		die();
	}
	
	// soft redirect with a delay
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
	
	/* ********************** */
	
	// this method will freeze the generated content if caching is enabled
	private function freezeContent() {
		
		// access the request
		global $giRequest,$giConfiguration;
		
		// if caching is disabled or an error occured
		if(!$giConfiguration->isCachingEnabled()) {
		
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
			$aSignature = $giRequest->getSignature();
		
			// put the content in the cache
			file_put_contents('../private/data/cache/output/'.$aSignature.'.raw',$this->getContent());
			
			// put the content in the cache
			file_put_contents('../private/data/cache/output/'.$aSignature.'.json',json_encode($this->getHeaders()));
			
			// set the modification time to time()+ $this->Freeze
			touch('../private/data/cache/output/'.$aSignature.'.raw',time()+intval($this->Freeze)*3600);
			
			// set the modification time to time()+ $this->Freeze
			touch('../private/data/cache/output/'.$aSignature.'.json',time()+intval($this->Freeze)*3600);
			
		}	
		
	}
	
	private function buildMeta($metaName,$metaValue) {
		$output =  "\t\t".'<meta name="'.$metaName.'" content="'.$metaValue.'" />';
		return($output);
	}

	private function buildJavascript() {
		$this->Javascripts = array_unique($this->Javascripts);
		$output = '';
		foreach($this->Javascripts as $aJsFile) {
			$output .= "\t\t".'<script type="text/javascript" src="'. $aJsFile .'"></script>'."\n";
		}	
		return($output);
	}

	private function buildStylesheets() {
		$this->Stylesheets = array_unique($this->Stylesheets);
		$output = '';
		foreach($this->Stylesheets as $aCssFile) {
			$output .= "\t\t".'<link rel="stylesheet" media="all" type="text/css" href="'.$aCssFile.'" />'."\n";
		}
		return($output);
	}
	
	private function buildTitle() {
		return("\t\t".'<title>'.$this->getTitle().'</title>');
	}
	
	private function isNotNull($aString) {
		if($aString != null and $aString != '') {
			return(true);	
		}
		else {
			return(false);	
		}
	}

	public function setTitle($Title) {
		if($this->isNotNull($Title)) {
			if($this->isNotNull($this->Title)) {
				$this->Title = (string)	$Title . ' - ' . $this->Title;
			}
			else {
				$this->Title = (string)	$Title;
			}
				
		}
	}
	public function getTitle() {
		return(strip_tags($this->Title));
	}
	public function setDescription($Description) {
		if($this->isNotNull($Description)) {
			$this->Description = (string)	$Description;
		}	
	}
	public function getDescription() {
		return(strip_tags($this->Description));
	}
	public function setKeywords($Keywords) {
		if($this->isNotNull($Keywords)) {
			$this->Keywords = (string)	$Keywords;
		}	
	}
	public function getKeywords() {
		return(strip_tags($this->Keywords));
	}
	public function setLanguage($Language) {
		if($this->isNotNull($Language)) {
			$this->Language = (string)	$Language;
		}
	}
	public function setCopyright($Copyright) {
		if($this->isNotNull($Copyright)) {
			$this->Copyright = (string)	$Copyright;	
		}	
	}
	public function getCopyright() {
		return(strip_tags($this->Copyright));
	}
	public function setAuthor($Author) {
		if($this->isNotNull($Author)) {
			$this->Author = (string)	$Author;	
		}	
	}
	public function getAuthor() {
		return(strip_tags($this->Author));
	}
	public function setCompany($Company) {
		if($this->isNotNull($Company)) {
			$this->Company = (string)	$Company;
		}
	}
	public function getCompany() {
		return(strip_tags($this->Company));
	}
	public function setRevisitAfter($RevisitRate) {
		if($this->isNotNull($RevisitRate)) {
			$this->RevisitAfter = $RevisitRate;	
		}	
	}
	public function getRevisitAfter() {
		return(strip_tags($this->RevisitAfter));
	}
	public function getGoogleSiteVerification() {
		return($this->googleSiteVerification);
	}
	public function setDebugCode($debugCode) {
		if($this->isNotNull($debugCode)) {
			$this->Obfuscate	= (boolean)	false;
			$this->Indent	= (boolean)	true;
		}
		if($this->isNotNull($this->Content)) {
			$this->Content 			.= $debugCode;
		}
		else {
			$this->Content 			= (string)	$debugCode;
		}
		
	}
	public function setContent($Content) {
		// force setting content
		$this->Content = $Content;
	}
	
	public function addContent($Content) {
		
	}
	
	public function getContent() {
		// return content as-is
		return($this->Content);		
	}
	
	public function setRobotRule($pageRobotRule) {
		if($this->isNotNull($pageRobotRule)) {
			$this->RobotRule = $pageRobotRule;	
		}	
	}
	public function getRobotRule() {
		return(strip_tags($this->RobotRule));	
	}

	public function setPosition($Position) {
		if($this->isNotNull($Position)) {
			$this->Position = $Position;	
		}	
	}
	public function getPosition() {
		return(strip_tags($this->Position));
	}
	
	public function setPlacename($Placename) {
		if($this->isNotNull($Placename)) {
			$this->Placename = $Placename;	
		}	
	}
	public function getPlacename() {
		return(strip_tags($this->Placename));
	}
	
	public function setRegion($Region) {	
		if($this->isNotNull($Region)) {
			$this->Region = $Region;	
		}	
	}
	public function getRegion() {
		return(strip_tags($this->Region));
	}

	public function setJs($pageJs) {
		if(is_array($pageJs) and count($pageJs) > 0) {
			foreach($pageJs as $aJsFile) {
				if(substr($aJsFile,0,1) == '/' or substr($aJsFile,0,4) == 'http') {
					$this->Javascripts[] = (string) $aJsFile;
				}
				else {
					$this->Javascripts[] = (string) '/js/'.$aJsFile;	
				}
			}	
		}	
		else {
			if(substr($pageJs,0,1) == '/' or substr($pageJs,0,4) == 'http') {
				$this->Javascripts[] = (string) $aJsFile;
			}
			else {
				$this->Javascripts[] = (string) '/js/'.$pageJs;	
			}	
		}
	}
	public function getJs() {
		return($this->Javascripts);
	}
	
	public function setCss($pageCss) {
		if(is_array($pageCss) and count($pageCss) > 0) {
			foreach($pageCss as $aCssFile) {
				if(substr($aCssFile,0,1) == '/' or substr($aCssFile,0,4) == 'http') {
					$this->Stylesheets[] = (string) $aCssFile;	
				}
				else {
					$this->Stylesheets[] = (string) '/css/'.$aCssFile;	
				}
			}	
		}
		else {
			if(substr($pageCss,0,1) == '/' or substr($pageCss,0,4) == 'http') {
				$this->Stylesheets[] = (string) $pageCss;	
			}
			else {
				$this->Stylesheets[] = (string) '/css/'.$pageCss;	
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

	public function getTrackerCode() {

			global $giConfiguration,$giAuthentication;
return('
		<script type="text/javascript">
			var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
			document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
		</script>
		<script type="text/javascript">
			try {
			var pageTracker = _gat._getTracker("'.$giConfiguration->getAnalyticsId().'");
			pageTracker._setCustomVar(
				1,
				"giLogin",
				"'.$giAuthentication->getSelfLogin().'",
				1
			);
			pageTracker._trackPageview();
			} catch(err) {}
		</script>
');			

		
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
		foreach($contentArray as $key => $value) {
			if(is_array($value)) {
				if(!is_numeric($key)){
					$subnode = $xmlContent->addChild("$key");
					$this->arrayToXML($value, $subnode);
				}
				else{
					$subnode = $xmlContent->addChild("item$key");
					$this->arrayToXML($value, $subnode);
				}
			}
			else {
				$xmlContent->addChild("$key","$value");
			}
		}
	}

	private function obfuscateCode() {
		$sourceCode = $this->getContent();
		$source		= array("\t","\n","\r");
		$sourceCode = str_replace($source,"",$sourceCode);
		$sourceCode = str_replace("  "," ",$sourceCode);
		$this->setContent($sourceCode);
	}

	private function indentCode() {
		$sourceCode = $this->getContent();
		$indenter	= "\t";
		$sourceCode 	= str_replace("\n", '', $sourceCode);
		$sourceCode 	= str_replace("\r", '', $sourceCode);
		$sourceCode 	= str_replace("\t", '', $sourceCode);
		$sourceCode 	= ereg_replace(">( )*", ">", $sourceCode);
		$sourceCode 	= ereg_replace("( )*<", "<", $sourceCode);
		$level 		= 0;
		$sourceCode_len = strlen($sourceCode);
		$pt 		= 0;
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
		$sourceCode = implode($array, "\n");
		$this->setContent($sourceCode);
	}

}

?>