<?php
/**
 * PHP Version 5
 * @package giFramework
 * @link https://github.com/AnnoyingTechnology/giframework2.1
 * @author Julien Arnaud (AnnoyingTechnology) <e10ad5d4ab72523920e7cbe55ba6c91c@gribouille.eu@gribouille.eu>
 * @copyright 2015 - 2015 Julien Arnaud
 * @license http://www.gnu.org/licenses/lgpl.txt GNU General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * uses http://php.net/manual/en/book.fileinfo.php
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */

class giUploader {

	// source file
	protected	$Source;
	// destination of uploaded file
	protected	$Destination;
	// type of the file being uploaded
	protected	$Type;
	// size of the file being uploaded
	protected	$Size;
	// limitations on the size and mimetypes allowed
	protected	$Limits;
	// list of errors
	protected	$Errors;
	
	// constructor
	public function __contruct() {
		
		// set the default constraints
		$this->Limits = new stdClass();
		$this->Limits->Size = null;
		$this->Limits->Types = null;
		
	}

	// set the source file path
	public function source($path) {
		
	}
	
	// set the destination
	public function destination($path) {
		
	}
	
	// limit size to $max bytes
	public function limitSize($max) {
		
	}
	
	// limit types to array or mimetypes
	public function limitTypes($allowed) {
		
	}
	
	// actually move the file
	public function execute() {
		
	}
	
	// retieve informations about all that hapened
	public function infos() {
		
	}
	
}

?>