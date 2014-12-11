<?php
/*

  	id  	tinyint(11) 
	dateTime 	tinyint(11)
	from_id	tinyint(11) 
	gift	varchar(50)
	to_id		tinyint(11) 
	message	text
	contrib_id	tinyint(11)  NULL

*/

class Aloa_Mdl_Tgift extends Zend_Db_Table_Abstract{

	protected $_name="gift";
	protected $_primary='id';

	protected $_referenceMap = array(
							'RelatedAuthor' => array(
												'columns' => 'author_id', 
												'refTableClass' => 'Aloa_Mdl_Tuser'
												)
									);
									
	public function getByRecepientId($id){
		$select = $this->select();
		$select->where('recepient_id = ?', $id);
		return $this->fetchRow($select);
	}
	
	public function giftMail($dest, $msg, $relatedUrl){
		if(Zend_Session::namespaceIsset('registred')){
			$user_session = new Zend_Session_Namespace('registred');
		}else{
			$user_session = null;
		}
		
		if($user_session != null){
			
			$mail = new Zend_Mail('UTF-8');
			
			$mail->setFrom(Zend_Controller_Front::getInstance()->getParam('aloa_send_mail'), 'Aloa');
			$mail->addTo($dest->email, $dest->uname);
			$mail->setSubject($user_session->uname.' vous écrit depuis Aloa');
			
			$mail->addHeader('MIME-Version', '1.0');
			$mail->addHeader('Content-Type', 'text-plain; charset=utf-8');
			$mail->addHeader('X-Mailer', 'PHP');
			
			// Attention texte à revoir
			$mail->setBodyText("Bonjour ".$dest->uname.",\r\n \r\n \r\n
			".$user_session->uname." vous offre un part de gâteau pour vous remercier d'une de vos contributions : \r\n".$relatedUrl."\r\n \"\"\"\r\n ".$msg." \r\n \"\"\"\r\n
			Vous pouvez répondre à ".$user_session->uname." depuis sa page personnelle : \r\n \r\n".Zend_Controller_Front::getInstance()->getParam('aloa_server').'/communaute/pageperso/uname/'.$user_session->uname."#ecrire
			
			A trés bientôt sur Aloa. \r\n \r\n
			\r\n \r\nP.S. : Si vous pensez que ce mail ne vous était pas destiné, nous nous en excusons et vous remercions de nous le signaler par retour de mail à cette adresse : ".Zend_Controller_Front::getInstance()->getParam('aloa_mail_admin'));
			// version HTML ?
			
			
			try{
				$mail->send();
			}catch(Zend_Mail_Exception $e){
				
			}
		
		}else{
			throw new Aloa_Exception_Authentification('Vous devez être inscrit et connecté comme utilisateur pour pouvoir effectuer cette action.');
		}
	}
	
}

?>