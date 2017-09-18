<?php
//require_once('PHPMailer/class.phpmailer.php');
//require_once('PHPMailer/class.smtp.php');
require_once 'PHPMailer/PHPMailerAutoload.php';

class Email {
	public $from = 'noreply@cf139.com';
	public $host 	 = 'localhost';
	public $username = 'openunix@163.com';
	public $password = 'passw0rd';
	public $replyto	 = 'noreply@cf139.com';
	public $name	 = '创富金融';
	public $debug	 = 0;

	public function __construct(){
	}
	public function group($to, $subject, $body, $option = null){
                //$smtp = array('173.254.223.53','173.254.223.54','173.254.223.55','173.254.223.56','173.254.223.57');
                $smtp = array('173.254.223.53','45.33.242.42','47.90.44.87');
                //$smtp = array('172.18.53.90','172.18.53.91');
                //$smtp = array('172.18.53.89','172.18.53.90','172.18.53.91');
                $this->host = $smtp[array_rand($smtp)];
		return $this->smtp($to, $subject, $body, $option);
	}
	public function single($to, $subject, $body, $option = null){
		$this->host = '173.254.223.53';
                return $this->smtp($to, $subject, $body, $option);
	}
	public function smtp($to, $subject, $body, $option = null){
		
		$ignore  = file('/etc/postfix/ignore.lst');

		$ignore = array_flip($ignore);

		if(in_array($to, $ignore)){
			return("ignore ".$to);
		}


		if(is_array($option)){
			if(array_key_exists('name', $option)){
				$this->name = $option['name'];
			}
			if(array_key_exists('debug', $option)){
				$this->debug = $option['debug'];
			}
		}
		$result = $this->host;	
		$mail             = new PHPMailer(true);
		try{
			$mail->IsSMTP(); // telling the class to use SMTP
			$mail->CharSet = 'UTF-8';
			$mail->XMailer = ' ';
			$mail->IsHTML(true);
			//$mail->SMTPSecure = 'tls';
			$mail->SMTPDebug  = $this->debug;   // enables SMTP debug information (for testing)
												// 1 = errors and messages
												// 2 = messages only
			$mail->Host       = $this->host; 	// sets the SMTP server
			$mail->Port       = 25;                    // set the SMTP port for the GMAIL server

			$mail->SMTPAuth   = false;                  // enable SMTP authentication
			$mail->Username   = $this->username; // SMTP account username
			$mail->Password   = $this->password; // SMTP account password

			$mail->SetFrom($this->from, $this->name);
			$mail->AddReplyTo($this->replyto, $this->name);
			
			$mail->Subject    = $subject;
			//$mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
			//$body             = eregi_replace("[\]",'',$body);
			$mail->MsgHTML($body);
			
			$mail->ClearAddresses();
			$mail->AddAddress($to, $this->name);

			//$mail->AddAttachment("images/phpmailer.gif");      // attachment
			//$mail->AddAttachment("images/phpmailer_mini.gif"); // attachment

			if(!$mail->Send()) {
				$result = "Mailer Error: " . $mail->ErrorInfo;
			}
		
		} catch (phpmailerException $e) {
			$result =  $e->errorMessage(); //Pretty error messages from PHPMailer
		} catch (Exception $e) {
			$result =  $e->getMessage(); //Boring error messages from anything else!
		} finally {
			//$mail->smtpClose();
			//usleep(50000);
		}
		return($result);
	}
}

//$email = new Email();
//$email->smtp('Neo Chen','openunix@163.com','Helloworld','How are you?');
