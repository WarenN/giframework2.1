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

include(dirname(__FILE__).'/class.phpmailer.php');
include(dirname(__FILE__).'/class.smtp.php');
//include(dirname(__FILE__).'/class.pop3.php');


/**
 * giMailer - PHPMailer extension for email storage in database.
 * @package giFramework
 * @link https://github.com/AnnoyingTechnology/giframework2.1
 * @author Julien Arnaud (AnnoyingTechnology) <e10ad5d4ab72523920e7cbe55ba6c91c@gribouille.eu@gribouille.eu>
 */
class giMailer extends PHPMailer {
	
	/**
     * Insert header
     * @return boolean
     */
    public function addHeader($path) {
    	// if the file does no exist
    	if(!file_exists($path)) {
    		// return false
    		return(false);	
    	}
    	// file exists
    	else {
    		// include it
    		$this->Body .= file_get_contents($ath);	
    		// return true
    		return(true);
    	}
    }
    
    /**
     * Insert footer
     * @return boolean
     */
    public function addFooter($path) {
    	// use add header as alias
    	return($this->addHeader($path));
    }
    
    /**
     * Add content with linebreaks converted
     * @return null
     */
    public function addContent($content) {
    	// if using html
    	if($this->ContentType == 'text/html') {
    		// convert linebreaks to br
    		$content = nl2br($content);
    	}
		// append the content
		$this->Body .= $content;    	
    }
	
	/**
     * Add content with linebreaks converted
     * @return null
     */
    public function addLine($line) {
    	// append the content
		$this->Body .= $line; 
    	// if using html
    	if($this->ContentType == 'text/html') {
    		// add a br
    		$this->Body .= '<br />';
    	}
    	// plain text
    	else {
    		// add a line break
    		$this->Body .= "\n";
    	} 	
    }
	
	/**
     * Loads a message from the database
     * @return boolean false on error
     */
	public function getMessage($id) {
		// access app
		global $app;
		// load from the database
		list($this->Record) = $app->Database->query()
			->select()
			->from('emails')
			->where(array('id'=>$id))
			->execute();
		// if the mail is found
		if($this->Record) {
			// update the message
			$this->From			= $this->Record->get('from_email');
			$this->FromName		= $this->Record->get('from_name');
			$this->Subject		= $this->Record->get('subject');
			$this->Body			= $this->Record->get('body');
			$this->ContentType	= $this->Record->get('type');
			$this->ReplyTo		= $this->Record->get('reply_to_array');
			$this->to			= $this->Record->get('recipients_array');
			$this->cc			= $this->Record->get('cc_array');
			$this->bcc			= $this->Record->get('bcc_array');
			// return true
			return(true);
		}
		// failed to load the message
		else {
			// return false
			return(false);
		}
	}
	
	/**
     * Create a message and send it.
     * Uses the sending method specified by $Mailer.
     * Uses giQuery to store the message in database with its sending status
     * @throws phpmailerException
     * @return boolean false on error - See the ErrorInfo property for details of the error.
     */
	public function FullSend() {
		// access application
		global $app;
		// new insert query
		$message = array(
			'from_email'		=>$this->From,
			'from_name'			=>$this->FromName,
			'subject'			=>$this->Subject,
			'body'				=>$this->Body,
			'type'				=>$this->ContentType,
			'reply_to_array'	=>$this->ReplyTo,
			'recipients_array'	=>$this->to,
			'cc_array'			=>$this->cc,
			'bcc_array'			=>$this->bcc,
			'sending_date'		=>time(),
			'is_sent'			=>'0'
		);
		// try sending the message
		$sent = $this->Send();
		// save the message
		if($sent) {
			// update the message
			$message['is_sent'] = '1';
		}
		// if we are using a message from the database
		if($this->Record) {
			// update it
			$app->Database->query()
				->update('emails')	
				->set($message)
				->where(array('id'=>$this->Record->get('id')))
				->execute();
		}
		// we have a new message to insert
		else {
			// set the creation date
			$message['creation_date'] = time();
			// create a new record
			$app->Database->query()
				->insert($message)	
				->into('emails')
				->execute();
		}
		// return status
		return($sent);
	}
	
}

?>