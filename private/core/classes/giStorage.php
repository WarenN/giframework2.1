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