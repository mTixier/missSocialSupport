<?php
/*

id  	int(11)  	 	  	Non  	 	auto_increment
	dateTime 	int(11) 				
	uname 	varchar(25) 	utf8_bin 		unique
	tagLine 	varchar(140) 	utf8_bin 
	firstname 	varchar(50) 	utf8_bin 
	lastname 	varchar(50) 	utf8_bin
	zip 	varchar(10) 	utf8_bin 
	city 	varchar(50) 	utf8_bin
	email 	varchar(120) 	utf8_bin 	
	pwd 	varchar(40) 	utf8_bin 	
	avatar_id 	int(11) 		
	role 	varchar(50) 	utf8_bin 	
	activationKey 	varchar(40) 	utf8_bin 		Non 		
	viewpoint 	varchar(255) 	utf8_bin 		Non 		
	keywords 	text 	utf8_bin 		Oui 	NULL 	
	alert 	tinyint(1) 			Non 	1 	
	digest	enum('daily', 'always', 'weekly', 'bi-weekly', 'monthly')  	utf8_bin  	  	Non  	always  	

*/


class Aloa_Mdl_Tuser extends Zend_Db_Table_Abstract{

	protected $_name="user";
	protected $_primary='id';

	protected $_referenceMap = array(
								'Avatar' => array(
													'columns' => 'avatar_id', 
													'refTableClass' => 'Aloa_Mdl_Tavatar'
													),
								);
	

	public function getLastUsers($limit=0){
		$select = $this->select();
		
		$select->order('dateTime DESC');
		
		if($limit > 0){
			$select->limit($limit);
		}
		
		return $this->fetchAll($select);
	}

	public function getByUname($rqt, $limit=0){
		
		$select = $this->select();
		
		$select->where('uname LIKE ?', $rqt.'%');
		
		if($limit > 0){
			$select->limit($limit);
		}
		
		$select->order('uname ASC');
		
		return $this->fetchAll($select);	
	}

/* fonctions de gestions de la colonne keywords */
// Gagnerai à gérer les majuscules/minuscules...
// Est utilisé notamment par l'hypertopic controler et le document controller
// Pb : c'est une redondance avec les même fonctions disponibles pour pour les Tcontribution --> trouver une solution plus élégante

	public function addKeywords($past_kw, $kw){
		
		if(is_array($kw)){
			$kw = implode(',', $kw);
		}
		
		if($past_kw == null){
			$res = $kw;
		}else{
			$kwlist = array_unique(explode(',', $past_kw.','.$kw));
			$res = implode(',', $kwlist);
		}
		return $res;
	}
	
	public function rmvKeywords($past_kw, $kw){
	
		if($past_kw == null){
			$res = null;
		}else{
			$res = array();
			$kwlist = explode(',', $past_kw);
			
			if(!is_array($kw)){
				$kw = array($kw);
			}
			
			foreach($kwlist as $v){
				if(!in_array($v, $kw)){
					$res[] = $v;
				}
			}
		
			// Si la liste est vide alors null
			if(count($res) > 0){
				$res = implode(',',$res);
			}else{
				$res = null;
			}
		}
		return $res;
	}

	
/* Mail */	
	
	public function welcomeMail(){
		if(Zend_Session::namespaceIsset('visiteur')){
			$visiteur = new Zend_Session_Namespace('visiteur');
		}
		
		if(isset($visiteur->inscription)){
			
			$mail = new Zend_Mail('UTF-8');
			
			$mail->setFrom(Zend_Controller_Front::getInstance()->getParam('aloa_send_mail'), 'Aloa');
			$mail->addTo($visiteur->inscription['email'], $visiteur->inscription['uname']);
			$mail->setSubject('Bienvenue sur Aloa');
			
			$mail->addHeader('MIME-Version', '1.0');
			$mail->addHeader('Content-Type', 'text-plain; charset=utf-8');
			$mail->addHeader('X-Mailer', 'PHP');
			
			$mail->setBodyText("Bonjour ".$visiteur->inscription['uname'].",\r\n \r\n \r\nBienvenue sur Aloa. Nous vous contactons afin d'effectuer la dernière étape de l'inscription pour activer votre compte utilisateur sur le site. Cette procédure nous permet de prévenir le site du spam et des pirates. Il vous suffit de cliquer sur le lien suivant pour activer votre compte et découvrir votre page personnelle :  \r\n \r\n".Zend_Controller_Front::getInstance()->getParam('aloa_server').'/login/activate/key/'.base64_encode($visiteur->inscription['uname'].":".$visiteur->inscription['activationKey'])." \r\n \r\n Nous espérons que vous apprécierez le site web et participerez à faire d'Aloa un espace riche en discussion et agréable.\n\n A trés bientôt sur Aloa. \r\n \r\nCordialement \r\n \r\nL'équipe Aloa \r\n \r\n \r\n \r\nP.S. : Si vous pensez que ce mail ne vous était pas destiné, nous nous en excusons et vous remercions de nous le signaler par retour de mail à cette adresse : ".Zend_Controller_Front::getInstance()->getParam('aloa_mail_admin'));
			// version HTML ?
			
			
			try{
				$mail->send();
			}catch(Zend_Mail_Exception $e){
				
			}
		
		}else{
			throw new Zend_Exception('Données d\'inscription indisponibles.');
		}
	}
	
	public function mdpOublieMail($user){
	
			$mail = new Zend_Mail('UTF-8');
			
			$mail->setFrom(Zend_Controller_Front::getInstance()->getParam('aloa_send_mail'), 'Aloa');
			$mail->addTo($user->email, $user->uname);
			$mail->setSubject('Aloa - Renouvellement de vos informations de connexions.');
			
			$mail->addHeader('MIME-Version', '1.0');
			$mail->addHeader('Content-Type', 'text-plain; charset=utf-8');
			$mail->addHeader('X-Mailer', 'PHP');
			
			$mail->setBodyText("Bonjour ".$user->uname.",\r\n \r\n \r\nNous vous contactons suite à votre demande de renouvellement de mot de passe. Cette procédure nous permet de prévenir le site du spam et des pirates. Il vous suffit de cliquer sur le lien suivant ou de le copier-coller dans la barre d'adresse pour vous rendre sur la page de renouvellement de votre mot de passe :  \r\n \r\n".Zend_Controller_Front::getInstance()->getParam('aloa_server').'/login/newmdp/key/'.base64_encode($user->uname.":".$user->activationKey)." \r\n \r\n A trés bientôt sur Aloa. \r\n \r\nCordialement \r\n \r\nL'équipe Aloa \r\n \r\n \r\n \r\nP.S. : Si vous pensez que ce mail ne vous était pas destiné, nous nous en excusons et vous remercions de nous le signaler par retour de mail à cette adresse : ".Zend_Controller_Front::getInstance()->getParam('aloa_mail_admin'));
			// version HTML ?
			
			
			try{
				$mail->send();
			}catch(Zend_Mail_Exception $e){
				
			}
	
	}
	
	public function userMail($dest, $msg){
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
			
			$mail->setBodyText("Bonjour ".$dest->uname.",\r\n \r\n \r\n
			".$user_session->uname." vous a écrit : \r\n \r\n ".$msg." \r\n \r\n
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