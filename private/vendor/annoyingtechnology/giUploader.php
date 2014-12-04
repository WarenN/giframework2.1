<?php

class giUploader {

	protected $originalFile;
	protected $originalFullPath;

	protected $authorizedFormats;
	protected $authorizedSize;

	protected $fileSize;
	protected $fileType;
	protected $fileOriginalName;
	protected $fileExtension;
	protected $filePath;
	protected $fileName;
	protected $fileFullPath;

	protected $returnInformations;
	protected $uploadFailed;

	public function __construct($originalFile, $filePath, $fileName=null, $authorizedFormats=null, $authorizedSize=null) {
		
		$this->originalFile			= (array)	$originalFile;						// the original file upload element
		$this->filePath				= (string)	$filePath;							// the path where we should put the uploaded file
		$this->fileOriginalName		= (string)	$this->originalFile['name'];		// the original file name
		$this->fileSize				= (integer)	$this->originalFile['size'];		// the original file size
		$this->fileType				= (string)	$this->originalFile['type'];		// the original mimtype
		$this->originalFullPath		= (string)	$this->originalFile['tmp_name'];	// the temporary file
		$this->fileExtension		= (string)	$this->obtainFileExtension();		// obtain the files extension
		$this->fileName				= (string)	$this->obtainDestinationName		($fileName);
		$this->fileFullPath			= (string)	$this->filePath.$this->fileName;	// the whole uploaded file path
		$this->authorizedFormats	= $this				->obtainAuthorizedFormats	($authorizedFormats);
		$this->authorizedSize		= $this				->obtainAuthorizedSize		($authorizedSize);
		
		// check upload errors
		if($this->originalFile['error'] != 0) {
			$this->uploadFailed		= (boolean)	true;
			return(false);	
		}
		// check file size
		if(!$this->checkSize()) {
			$this->uploadFailed		= (boolean)	true;
			return(false);
		}
		// check file type
		if(!$this->checkType()) {
			$this->uploadFailed		= (boolean)	true;
			return(false);
		}
		// try to move the file and return a bunch of information if it succeeds
		if(move_uploaded_file($this->originalFullPath,$this->fileFullPath)) {
			$this->uploadFailed		= (boolean)	false;
			return(true);
		}
		// the moving failed
		else {
			$this->uploadFailed		= (boolean)	true;
			return(false);
		}
	}

	public function obtainFileExtension() {
		$explodedName = (array)	explode('.',$this->fileOriginalName);	
		return($explodedName[count($explodedName) - 1]);
	}		

	// check for the file size
	public function checkSize() {
		if($this->authorizedSize == null) {
			return(true);	
		}
		elseif($this->fileSize < $this->authorizedSize) {
			return(true);
		}
		else {
			return(false);
		}
	}
	
	// check for the file type
	public function checkType() {
		if($this->authorizedFormats == null) {
			return(true);	
		}
		elseif(in_array($this->fileType,$this->authorizedFormats)) {
			return(true);
		}
		else {
			return(false);
		}
	}

	public function obtainAuthorizedSize($authorizedSize) {
		if(!is_int($authorizedSize)) {
			$this->authorizedSize		= null;
		}
		else {
			$this->authorizedSize		= (integer)	$authorizedSize;
		}	
	}

	public function obtainAuthorizedFormats($authorizedFormats=null) {
		if(!is_array($authorizedFormats)) {
			$this->authorizedFormats	= null;
		}
		else {
			$this->authorizedFormats	= (array)	$authorizedFormats;
		}
	}

	public function obtainDestinationName($destinationName) {
		if($destinationName == null) {
			return(@sha1_file($this->originalFullPath).'.'.$this->fileExtension);
		}
		else {
			return($destinationName.'.'.$this->fileExtension);	
		}
	}

	public function returnInfos() {
		return(
			array(
				'filePath'			=> (string)	$this->filePath,
				'fileName'			=> (string)	$this->fileName,
				'fileOriginalName'	=> (string)	$this->fileOriginalName,
				'fileFullPath'		=> (string) $this->fileFullPath,
				'fileType'			=> (string)	$this->fileType,
				'fileSize'			=> (integer)$this->fileSize,
				'fileExtension'		=> (string) $this->fileExtension
			)
		);
	}
	
	public function getInformations() {
		if($this->uploadFailed == false) {
			return($this->returnInfos());
		}
		else {
			return(false);	
		}
	}
		
}

?>