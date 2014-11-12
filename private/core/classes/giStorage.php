<?php

class giStorage {

	/****************************/
	/* CONFIGURATION PARAMETERS */
	
	protected $storageTable;
	protected $storagePath;
	
	// construction of the storage engine
	public function __construct() {
		
		$this->storagePath	= '../private/data/storage/files/';
		$this->storageTable	= 'Files';
	
	}


	// store the file
	public function storeFile($path,$remote_table=null,$remote_id=null,$type=null,$name=null) {
		
		// detect the mimetype
		
		
		// detect size
		
		
		// detect original name
		
		
		// return the stored ID in case of success, or return false in case of error
		
		
	}
	
	public function deleteFile($id) {
	
		
	}
	
	
	// tells the browser to show the file
	public function outputFile() {
	
		
	}
	
	// tell the browser to download the file
	public function downloadFile() {
	
		
	}

	// retrieve a file
	private function getFile($id) {
	
		
	}
	

}

?>