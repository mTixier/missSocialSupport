<?php

class NotificationController extends Zend_Controller_Action
{

    public function indexAction()
    {

		$capId = $this->_getParam('captcha_id', null);
		$capVal = $this->_getParam('captcha_input', null);
		$location = $this->_getParam('location', null);
		$report = $this->_getParam('report', null);
	
		$this->_helper->layout->disableLayout();
		
		$user_session = null;
		
		if($report != ""){
			if(Zend_Session::namespaceIsset('registred')){
				$user_session = new Zend_Session_Namespace('registred');
				// Envoi du mail
				$this->alertMail($report, $location, $user_session->uname);
				$msg = "ok:success";

			}else{
				
				if(Zend_Session::namespaceIsset('Zend_Form_Captcha_'.$capId)){
					$capSession = new Zend_Session_Namespace('Zend_Form_Captcha_'.$capId);
					$iter = $capSession->getIterator();
					$word = $iter['word'];
					if($word){
					
						if($capVal == $word){
						
							$this->alertMail($report, $location);
							
							$msg = "ok:success.";
						}else{
							$msg = "error:Le code renseigné et celui de l'image ne correspondent pas.";
							//"#".$captchaId;
						}

					}else{
						$msg = "error:Erreur technique.";
					}

				}else{
					$msg = "error:Paramètres incorrects.";
				}
				
			}
		}else{
			$msg = "error:Vous devez remplir le champs message. Merci.";
		}
		
		if($user_session == null){
			// Régénrer un capcha et pour mise à jour...
			$captcha=new Zend_Captcha_Image();
			$captcha->setWordLen('4')
				->setHeight('60')
				->setFont('./font/OldSansBlack.ttf')
				->setImgDir('./upload')
				->setDotNoiseLevel('3')
				->setLineNoiseLevel('3');
			$newCapId=$captcha->generate();
			
			$this->view->msg = $msg."#".$newCapId;
		}else{
			$this->view->msg = $msg;
		}
		
	
	}
	
	protected function alertMail($report, $location, $from_uname = null){
	
		$from_uname = ($from_uname == null)?"Utilisateur":$from_uname;

		$mail = new Zend_Mail('UTF-8');
		
		$mail->setFrom(Zend_Controller_Front::getInstance()->getParam('aloa_send_mail'), 'Aloa');
		$mail->addTo(Zend_Controller_Front::getInstance()->getParam('aloa_mail_admin'), 'Admin');
		$mail->setSubject('Notification');
		
		$mail->addHeader('MIME-Version', '1.0');
		$mail->addHeader('Content-Type', 'text-plain; charset=utf-8');
		$mail->addHeader('X-Mailer', 'PHP');
		
		$mail->setBodyText("Bonjour,\r\n \r\n ".$from_uname." vous a envoyé une notifiaction : \r\n".$report." \r\n La notification a été envoyée depuis la page : \r\n ".$location."\r\n \r\nSi vous pensez que ce mail ne vous était pas destiné, nous nous en excusons et vous remercions de nous le signaler par retour de mail.");
		// version HTML ?
		
		
		try{
			$mail->send();
		}catch(Zend_Mail_Exception $e){
			
		}
	
	}
	
	
	
}

?>