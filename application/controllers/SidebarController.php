<?php
class SidebarController extends Zend_Controller_Action{

	public function indexAction(){
	
		$this->_helper->viewRenderer->setNoRender();
		
		// Paramètre de la side bar pour signaler l'existence ou non de bavardages sur la page
		$has_chat = 0;
		
		if(Zend_Session::namespaceIsset('registred')){	
			
			// Le paramètre transmis détermine si l'on se trouve dans le cas d'un chat définit par défaut par l'application ou s'il est adjoint à une contribution.
			$chatid = $this->_getParam('chatid', null);
			$contribid = $this->_getParam('contribid', null);
			$userid = $this->_getParam('userid', null);
			$ttl = $this->_getParam('ttl', null);
				
			// bavardages par defaut sur une page
			if($chatid != null){
			
				// On ne signale le chat que si des contribution existent - clignottement si dernière intervention date de moins de 5 minutes
				$tutt = new Aloa_Mdl_Tutterance();
				$select = $tutt->select()->where('chat_id = ?', $chatid)->order('dateTime DESC')->limit(3);
				$res = $tutt->fetchAll($select);
				if(count($res) > 0){
					$has_chat = 1;
					// Si la discussion date de moins de 5 minutes...
					$lastutt = $res->current();
					if($lastutt->dateTime > (time()- 60*5)){
						$has_chat = 2;
					}
				}
				
				$this->_helper->actionStack('chat', 'sidebar', 'default', array('chatid' => $chatid));
			// bavardages sur page personnelle
			}else if($userid != null){

				$tchat = new Aloa_Mdl_Tchat();
				$select = $tchat->select($tchat, array('id'));
				$select->where('related_id = ?', $userid)->where('related_type = ?', Aloa_Mdl_Tchat::TYPE_USER);
				// Pour le moment on en reste à un chat par page
				$chat = $tchat->fetchRow($select);
				
				// On crée le chat s'il n'existe pas
				if($chat == null){
					
					$tuser = new Aloa_Mdl_Tuser();
					$user = $tuser->find($userid)->current();
					
					if($user != null){
				
						$chat = $tchat->createRow();
						$chat->dateTime = time();
						$chat->type = Aloa_Mdl_Tcontribution::CHAT;
						$chat->title = $user->uname;
						$chat->related_id = $user->id;
						$chat->related_type = Aloa_Mdl_Tchat::TYPE_USER;
						$chat->save();
				
					}else{
						throw new Zend_Exception('Paramètres incorrects.');
					}
				}
				
				// On ne signale le chat que si des contribution existent - clignottement si dernière intervention date de moins de 5 minutes
				$tutt = new Aloa_Mdl_Tutterance();
				$select = $tutt->select()->where('chat_id = ?', $chat->id)->order('dateTime DESC')->limit(3);
				$res = $tutt->fetchAll($select);
				if(count($res) > 0){
					$has_chat = 1;
					// Si la discussion date de moins de 5 minutes...
					$lastutt = $res->current();
					if($lastutt->dateTime > (time()- 60*5)){
						$has_chat = 2;
					}
				}
				

					$this->_helper->actionStack('chat', 'sidebar', 'default', array('chatid' => $chat->id));
				
			// bavardages sur contribution
			}else if($contribid != null){
				
				$tchat = new Aloa_Mdl_Tchat();
				$select = $tchat->select($tchat, array('id'));
				$select->where('related_id = ?', $contribid)->where('related_type = ?', Aloa_Mdl_Tchat::TYPE_CONTRIBUTION);
				// Pour le moment on en reste à un chat par page
				$chat = $tchat->fetchRow($select);
				
				// On crée le chat si la contribution existe et qu'il n' a pas encore été crée
				if($chat == null){
					
					$tcontrib = new Aloa_Mdl_Tcontribution();
					$contrib = $tcontrib->find($contribid)->current();
					
					if($contrib != null){
					
						$chat = $tchat->createRow();
						$chat->dateTime = time();
						$chat->type = Aloa_Mdl_Tcontribution::CHAT;
						$chat->title = $contrib->title;
						$chat->related_id = $contrib->id;
						$chat->related_type = Aloa_Mdl_Tchat::TYPE_CONTRIBUTION;
						$chat->save();
						
					}else{
						throw new Zend_Exception('Paramètres incorrects.');
					}
				}
				
				// On ne signale le chat par clignottement que si des contribution existent
				$tutt = new Aloa_Mdl_Tutterance();
				$select = $tutt->select()->where('chat_id = ?', $chat->id)->order('dateTime DESC')->limit(3);
				$res = $tutt->fetchAll($select);
				if(count($res) > 0){
					$has_chat = 1;
					// Si la discussion date de moins de 5 minutes...
					$lastutt = $res->current();
					if($lastutt->dateTime > (time()- 60*5)){
						$has_chat = 2;
					}
				}
				
				$this->_helper->actionStack('chat', 'sidebar', 'default', array('chatid' => $chat->id));
				
				// Recherche de contenus associés pour les contributions -- Trop de redondances ce n'est pas propre
				$tcontrib = new Aloa_Mdl_Tcontribution();
				$contrib = $tcontrib->find($contribid)->current();
				if($contrib != null){
					$this->_helper->actionStack('associatedcontent', 'sidebar', 'default', array('relatesTo' => urlencode($contrib->title), 'contribType' => $contrib->type));
				}
			}
			
			// Génération du menu personnel
			$this->_helper->actionStack('menuperso', 'sidebar', 'default');
		
		}else{
			$this->_helper->actionStack('index', 'login', 'default');
		}
		
		$this->_helper->actionStack('sidebarmenu', 'sidebar', 'default', array('has_chat' => $has_chat));
		// Comme son nom l'indique l'actionStack est une pile : First-in - First-out
	}
	
	public function sidebarmenuAction(){
		$this->view->has_chat = $this->_getParam('has_chat', 0);
		// Attention on a isoler le menu du layout - de fait deux balise div se trouve dans le layout pour ferme le menu de la sidebar afin de réliser l'imbrication du contenu
		$this->render('sidebarmenu', 'sidebar', false);
	}
	
	public function menupersoAction(){
		$this->render('menuperso', 'sidebar', false);
	}
	
	public function associatedcontentAction(){
	
		$rqt = $this->_getParam('relatesTo', null);
		$type = $this->_getParam('contribType', null);
		$this->view->related = array();
		
		if($rqt != null && $type != null){
			$front = Zend_Controller_Front::getInstance();
			$index = $front->getParam('aloa_search_index');
			
			switch($type){
				case Aloa_Mdl_Tcontribution::QUESTION:
					$tfilter = '+'. Aloa_Mdl_Tcontribution::SEARCH_ENGINE_PREFIX .'type:('. Aloa_Mdl_Tcontribution::DOCUMENT .' '. Aloa_Mdl_Tcontribution::USERSTORY .')';
					break;
				case Aloa_Mdl_Tcontribution::DOCUMENT:
					$tfilter = '+'. Aloa_Mdl_Tcontribution::SEARCH_ENGINE_PREFIX .'type:('. Aloa_Mdl_Tcontribution::QUESTION .' '. Aloa_Mdl_Tcontribution::ANSWER .' '. Aloa_Mdl_Tcontribution::USERSTORY .')';
					break;
				case Aloa_Mdl_Tcontribution::STORYTHEME:
					$tfilter = '+'. Aloa_Mdl_Tcontribution::SEARCH_ENGINE_PREFIX .'type:('. Aloa_Mdl_Tcontribution::QUESTION .' '. Aloa_Mdl_Tcontribution::ANSWER .' '. Aloa_Mdl_Tcontribution::DOCUMENT .')';
					break;
			}
			
			$query = Zend_Search_Lucene_Search_QueryParser::parse($tfilter.' +('.$rqt.')');
					
			$this->view->related = $index->find($query);
			
		}else{
			throw new Zend_Exception('Requête vide.');
		}
		
		$this->render('associatedcontent', 'sidebar', false);
	
	}
	
	public function chatAction(){
		
		$chatid = $this->_getParam('chatid', null);
		
		if(Zend_Session::namespaceIsset('registred')){	
			$user_session = new Zend_Session_Namespace('registred');
		}
		
		$tchat = new Aloa_Mdl_Tchat();
		$chat = $tchat->find($chatid)->current();

		if($chat != null && $user_session != null){
			$this->view->ttl = $chat->title;
			$this->view->id = $chat->id;
			$this->view->current_author = $user_session->uname;
		}
		
		$this->render('chat', 'sidebar', false);
	}

/* Gestion des état de la side bar */
	
	// met à jour l'état de la sidebar dans la session nmspc visiteur
	public function updateAction(){
	
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$state = $this->_getParam('state', null);
		
		if(Zend_Session::namespaceIsset('visiteur')){
			$visiteur = new Zend_Session_Namespace('visiteur');
			$visiteur->sidebarState = $state;
		}
	
	}
	
	public function hideAction(){
		$this->render('hide', 'sidebar', false);
	}
	
	public function maximizeAction(){
		$this->render('maximize', 'sidebar', false);
	}
	
	public function defaultAction(){
		$this->render('default', 'sidebar', false);
	}
	
	public function minimizeAction(){
		$this->render('minimize', 'sidebar', false);
	}
}
?>