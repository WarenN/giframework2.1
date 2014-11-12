<?php

class Email {
	
	protected $phpMailer;
	protected $savedEmail;
	protected $recipients;
	protected $subject;
	protected $body;
	protected $status;
	
	public function __construct($savedEmailId=null) {
		// access the database
		global $db;
		// initialize recipients
		$this->recipients = array();
		
		// if an id is provided we should extract the mail from the database
		if($savedEmailId) {
			// set the id for later
			$this->savedEmail 	= $db->get('Emails',$savedEmailId);
			// extract the recipients
			$this->recipients 	= $this->savedEmail->get('recipients_array');
			// extract the subject
			$this->subject		= $this->savedEmail->get('subject');
			// extract the body
			$this->body			= $this->savedEmail->get('body');
		}
		
	}

	public function Send() {
		// access the database
		global $db,$giConfiguration;
		// get the current environment
		$configuration = $giConfiguration->getConfiguration();
		// build the mailer
		$this->phpMailer = new giMailer();
		// get the prepend/append
		$prepend 	= file_get_contents('../private/data/mails/header.txt');
		$append 	= file_get_contents('../private/data/mails/footer.txt');
		$prepend 	= str_replace('%%%%',$configuration['mail']['url'],$prepend);
		$append 	= str_replace('%%%%',$configuration['mail']['url'],$append);
		$this->body = str_replace('%%%%',$configuration['mail']['url'],$this->body);
		// rebuild the php mailer
		$this->phpMailer->Subject	= $this->subject;
		$this->phpMailer->Body		= $prepend.nl2br($this->body).$append;
		$this->phpMailer->From		= $configuration['mail']['email'];
		$this->phpMailer->FromName	= $configuration['mail']['name'];
		// add each recipient
		foreach($this->recipients as $aRecipient) {
			$this->phpMailer->AddAddress($aRecipient);
		}
		// if we have extracted an email from the database
		if($this->savedEmail) {
			// update an email that was already stored in the database
			
		}
		else {
			// if the mail is sent properly
			if($this->phpMailer->Send()) {
				// status is sent
				$status = 'sent';
			}
			// else we failed
			else {
				// status is failed
				$status = 'failed';
			}
			// insert
			$db->insert('Emails',array(
				'recipients_array'	=> $this->recipients,
				'subject'			=> $this->subject,
				'body'				=> $this->body,
				'status'			=> $status
			));
		}
	}
	
	public function To($to) {
		// if its an array of emails
		if(is_array($to)) {
			// for each email
			foreach($to as $aRecipient) {
				// add the address
				$this->recipients[] = $aRecipient;
			}
		}
		// else its a single mail
		else {
			// add the address
			$this->recipients[] = $to;
		}
	}
	
	public function Subject($subject) {
		// set the subject
		$this->subject = $subject;
	}
	
	public function Body($body) {
		// set the body
		$this->body = $body;
	}
	
}

?>