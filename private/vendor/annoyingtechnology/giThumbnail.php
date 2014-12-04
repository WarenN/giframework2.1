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

class giThumbnail {

	// source image
	protected	$Source;
	// destination of thumbnail
	protected	$Destination;
	// type of the image
	protected	$Type;
	// size of the image
	protected	$Size;
	// quality of the generated thumbnail
	protected	$Quality;
	// limitations on the size of the image
	protected	$Limits;
	// list of errors
	protected	$Errors;

	public function __contruct() {
		
		// set the default constraints
		$this->Limits = new stdClass();
		$this->Limits->Width = 320;
		$this->Limits->Height = 320;
		// set defautl quality
		$this->Quality = null;
			
	}
	
	// set the source image path
	public function source($path) {
	
	}
	
	// set the destination of the thumbnail
	public function destination($path) {
		
	}
	
	// limit the width of the picture
	public function limitWidth($pixels) {
		
	}
	
	// limits height of the picture
	public function limitHeight($pixels) {
		
	}
	
	// sets a maximum for both width and height
	public function limitBoth($pixels) {
		
	}
	
	// limit the quality
	public function limitQuality($quality) {
		
	}
	
	// actually generated the thumbnail
	public function execute() {
		
	}
	
	// retrieve informations about all that happened
	public function infos() {
		
	}
}

/*
class giThumbnail {
	

	public function __construct($sourceImageFile,$destinationImageFile,$desiredDimension,$desiredQuality=50) {
		$this->sourceImageFile		= (string)	$sourceImageFile;
		$this->destinationImageFile	= (string)	$destinationImageFile;
		$this->desiredDimension		= (integer)	$desiredDimension;
		$this->desiredQuality		= (integer)	$desiredQuality;
		// load the image
		$this->loadImage();
		// rotate image if necessary
		$this->rotateImage();
		// generate the sized image
		$this->generateSizedImage();
		// save the generated image
		$this->saveGeneratedImage();
	}
	
	private function loadImage() {
		// get image type
		$explodedFileName	= (array)	explode('.',$this->sourceImageFile);
		$this->sourceImageName	= (string)	$explodedFileName[count($explodedFileName) - 2];
		$this->sourceImageType	= (string)	strtolower($explodedFileName[count($explodedFileName) - 1]);
		switch($this->sourceImageType){
			case 'png':
				break;
			case 'jpeg':
			case 'jpg' :
			default:
				$this->sourceImageType = 'jpg';
		}

		// try to get orientation
		// work only with JPEG images
		// require EXIF
		$this->sourceImageInfos['Orientation'] = 1;
		if($this->sourceImageType == 'jpg') {
			if( function_exists ("exif_read_data")){
				$this->sourceImageInfos	= (array) @exif_read_data($this->sourceImageFile );
			}
		}
		// load image
		switch($this->sourceImageType){
			case 'png':
				$this->loadedImage	= imagecreatefrompng($this->sourceImageFile);
				break;
			case 'jpg':
			default:
				$this->loadedImage	= imagecreatefromjpeg($this->sourceImageFile);
		}
		$this->sourceImageWidth 	= imageSX($this->loadedImage);
		$this->sourceImageHeight 	= imageSY($this->loadedImage);

	}

	private function generateSizedImage() {
		// if the picture is vertical
		if($this->sourceImageHeight > $this->sourceImageWidth) {
			// set the width
			$desiredWidth 	= (integer)	$this->desiredDimension;
			// set the height
			$desiredHeight 	= (integer)	round($this->desiredDimension * $this->sourceImageHeight / $this->sourceImageWidth , 0);
		}
		// else the picture is horizontal or cubic
		else {
			// set the height
			$desiredHeight = (integer)	$this->desiredDimension;
			// set the width 
			$desiredWidth = (integer)	round($this->desiredDimension * $this->sourceImageWidth / $this->sourceImageHeight , 0);
		}	
		// generate the canvas
		$sizedImage		= ImageCreateTrueColor($desiredWidth,$desiredHeight);
		// resize and inject into the canvas
		imagecopyresampled(
			$sizedImage,
			$this->loadedImage,
			0,
			0,
			0,
			0,
			$desiredWidth,
			$desiredHeight,
			$this->sourceImageWidth,
			$this->sourceImageHeight
		);
		$this->loadedImage	= $sizedImage;
	}

	private function rotateImage(){
		$rotate = 0;
		$flip = false;
		
		switch($this->sourceImageInfos['Orientation']) {
			case 1:
				$rotate = 0;
				$flip = false;
			break;
			
			case 2:
				$rotate = 0;
				$flip = true;
			break;
			
			case 3:
				$rotate = 180;
				$flip = false;
			break;
			
			case 4:
				$rotate = 180;
				$flip = true;
			break;
			
			case 5:
				$rotate = 90;
				$flip = true;
			break;
			
			case 6:
				$rotate = 90;
				$flip = false;
			break;
			
			case 7:
				$rotate = 270;
				$flip = true;
			break;
			
			case 8:
				$rotate = 270;
				$flip = false;
			break;
		}
		// Rotate if necessary
		if( $rotate != 0 ){
			$this->loadedImage = imagerotate($this->loadedImage, 360-$rotate, 0);
		}

	}

	private function saveGeneratedImage() {

		$explodedFilePath	= (array)	explode('/',$this->destinationImageFile);
		$explodedFileName	= (array)	explode('.',$explodedFilePath[count($explodedFilePath) - 1]);
		$outputFormat		= (string)	strtolower($explodedFileName[count($explodedFileName) - 1]);

		// create headers and save image to the disk
		switch($outputFormat){
			case 'png':
				// convert 'jpeg quality' to 'png quality'
				$quality = round(9 * $this->desiredQuality / 100 , 0);
				imagepng($this->loadedImage,$this->destinationImageFile,$quality);
				break;
			case 'jpg':
			case 'jpeg':
			default:
				imagejpeg($this->loadedImage,$this->destinationImageFile,$this->desiredQuality);
				break;
		}
	}

	public function __destruct() {
		// destroy the loaded image to avoid memory leaks
		imagedestroy($this->loadedImage);
	}

}
*/
?>
