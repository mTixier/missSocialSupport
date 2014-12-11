<?php
class LoginController extends Zend_Controller_Action{

	
	public function indexAction(){
		
		$params = $this->getRequest()->getParams();
		$form = $this->getLoginForm();
		$this->view->form = $form;
		
		// Problématique car si on fait une requête vide la sidebar est vide
		if(array_key_exists('email', $params) && $this->getRequest()->isPost()){
			if($form->isValid($params)){
			// Idéalement ce serait plus propre de passer par un validateur standard ou d'étendre
				$login = $params['email'];
				$pwd = $params['pwd'];
				
				$tbl = new Aloa_Mdl_Tuser();
				$select = $tbl->select();
				$select->from($tbl, array('id', 'uname', 'dateTime', 'tagLine', 'zip', 'city', 'avatar_id', 'role', 'rights', 'lastVisit'))
					->where("email = ?", $login)
					->where("pwd = ?", sha1(trim($pwd)))
					->where("activationKey IS NULL");
					
				$user = $tbl->fetchRow($select);
			
				if($user != null){
					// Mise à jour de la dernière visite
					$user->lastVisit = time();
					$user->save();
					
					// Gérer le rememberMe
					if(isset($params['remember_me']) && $params['remember_me'] = 1){
						// Si le remember_me est activé alors on se souvient de la session pour 2 semaines.
						Zend_Session::rememberMe(1209600);
					}
					
					$user_p = $user->toArray();
					$this->makeUserSession($user_p);
								
					$this->_redirector = $this->_helper->getHelper('Redirector');
					$front = Zend_Controller_Front::getInstance();
					$cmplt = "";
					
					// Une fois loggé, on renvoi l'utilisateur sur la page où il se trouvait 
					if(Zend_Session::namespaceIsset('visiteur')){
						$visitor = new Zend_Session_Namespace('visiteur');
						$cmplt = ($visitor->precedingUrl == null)?"":$visitor->precedingUrl;
					}
					$this->_redirector->setGotoUrl($front->getParam('aloa_server').$cmplt);
					
				}else{
					$this->view->errmsg = "Utilisateur inconnu ou mot de passe incorrects.";
					$this->render('error', 'default', false);
					$this->_helper->actionStack('hide', 'sidebar', 'default');
					
				}
				//$this->render('index', 'sidebar', false);
			}else{
				$this->render('error', 'default', false);
				$this->_helper->actionStack('hide', 'sidebar', 'default');
			}
		
		}else{
			$this->render('index', 'sidebar', false);
		}
		
		//$this->render('index', 'sidebar', false);
		//$this->_helper->actionStack('index', 'sidebar', 'default');
	}
	
	public function errorAction(){
		$form = $this->getLoginForm();
		$this->view->form = $form;
		$this->_helper->actionStack('hide', 'sidebar', 'default');
	}
	
/* Form */

	public function getLoginForm($def=array()){
		$form = new Zend_Form();
		$form->setMethod('post');
		$form->setAction($this->view->url(array('action' => 'index', 'controller' => 'login'), false, 'default'));

		$email = new Zend_Form_Element_Text('email');
		$email->setLabel("Email :");
		$email->addValidator('EmailAddress');
		$email->setRequired(true);
		//$email->setDescription('*');
		$form->addElement($email);
		
		$mdp = new Zend_Form_Element_Password('pwd');
		$mdp->setLabel("Mot de passe :");
		$mdp->addValidator('StringLength', false, array(6, 20));
		$mdp->setRequired(true);
		//$mdp->setDescription('*');
		$form->addElement($mdp);
		
		$remember = new Zend_Form_Element_Checkbox('remember_me');
		$remember->setLabel("Se souvenir de moi.");
		$form->addElement($remember);
		
		$submit = new Zend_Form_Element_Submit('submit', array('label' => "  Me connecter  ", 'class' => 'btn'));
		$form->addElement($submit);	
	
		return $form;
	}

	public function getMdpOublieForm($def=array()){
	
		$form = new Zend_Form();
		$form->setMethod('post');
		$form->setAction($this->view->url(array('action' => 'mdpoublie', 'controller' => 'login'), false, 'default'));

		$email = new Zend_Form_Element_Text('email');
		$email->setDescription("Email : <font class='redstar'>*</font>");
		$email->addValidator('EmailAddress');
		$email->setRequired(true);
		$form->addElement($email);
		
		$form->setElementDecorators(array(
			'ViewHelper', 
			'Errors',
			array('HtmlTag', array('tag' => 'span', 'class' => 'inpt')),
			array('Description', array('placement' => 'prepend', 'escape' => false)),
			array(array('elementP' => 'HtmlTag'), array('tag'=> 'p'))
			));
		
		$submit = new Zend_Form_Element_Submit('submit', array('label' => "  Envoyer  ", 'class' => 'btn'));
		$form->addElement($submit);	
	
		return $form;
	}
	
	public function getChangeMdpForm($key){

		$form = new Zend_Form();
		$form->setMethod('post');
		$form->setAction($this->view->url(array('action' => 'newmdp', 'controller' => 'login'), false, 'default'));
	
		$hidden = new Zend_Form_Element_Hidden('key');
		$hidden->setValue($key);
		$form->addElement($hidden);
	
		$mdp = new Zend_Form_Element_Password('mdp');
		$mdp->setDescription("Mot de passe : <font class='redstar'>*</font>");
		$mdp->addValidator('StringLength', false, array(6, 20));
		$mdp->setRequired(true);
		$form->addElement($mdp);

		$c_mdp = new Zend_Form_Element_Password('c_mdp');
		$c_mdp->setDescription("Confirmer le mot de passe : <font class='redstar'>*</font>");
		$c_mdp->addValidator('StringLength', false, array(6, 20));
		// Attention penser au setToken au moment de la validation !!!  $passTwice->getValidator('Identical')->setToken($data['password']); cf http://www.emanaton.com/code/php/validateidenticalfield
		$c_mdp->addValidator('Identical');
		$c_mdp->setRequired(true);
		$form->addElement($c_mdp);
	
		$form->setElementDecorators(array(
			'ViewHelper', 
			'Errors',
			array('HtmlTag', array('tag' => 'span', 'class' => 'inpt')),
			array('Description', array('placement' => 'prepend', 'escape' => false)),
			array(array('elementP' => 'HtmlTag'), array('tag'=> 'p'))
			));
		
		$submit = new Zend_Form_Element_Submit('submit', array('label' => "  Envoyer  ", 'class' => 'btn'));
		$form->addElement($submit);	
	
		return $form;
	}
	
/* Form */
	
	public function quitAction(){
		$this->_helper->viewRenderer->setNoRender();
		//Zend_Session::forgetMe();
		Zend_Session::destroy();

		// il faudrait en refaire un controlleur, ce sera mieux de gérer le retour aux pages courantes après identification via les bloc init() des controlleurs d'action

		$url = $this->_getParam('last_url', $this->view->baseUrl());
		$this->_redirector = $this->_helper->getHelper('Redirector');
		$this->_redirector->setGotoUrl($url);
		$this->_redirector->gotoUrlAndExit();
		return;
	}
	
	protected function makeUserSession($user_p){
	
		$user_session = new Zend_Session_Namespace('registred');
		foreach($user_p as $k => $v){
			$user_session->$k = $v;
		}
	
	}
	
	public function delusersessionAction(){
		$this->_helper->viewRenderer->setNoRender();
		Zend_Session::destroy();
	}

	public function unsetnamespaceAction(){
		$nmspc = $this->_getParam('key', null);
		if($nmspc == null){
			throw new Zend_Exception('Erreur interne.');
		}else{
			$this->_helper->viewRenderer->setNoRender();
			$sess_nmspc = new Zend_Session_Namespace($nmspc);
			$sess_nmspc->unsetAll();
			Zend_Session::regenerateId();
		}
	}
	
	public function activateAction(){
		
		$this->_helper->viewRenderer->setNoRender();
		$key = $this->_getParam('key', null);
		if($key != null){
			$ref = explode(":", base64_decode($key));

			
		if(isset($ref[1])){
				$tbl = new Aloa_Mdl_Tuser();
				$select = $tbl->select();
				$select->from($tbl, array('id', 'uname', 'dateTime', 'tagLine', 'zip', 'city', 'avatar_id', 'role', 'rights', 'lastVisit','activationKey'))
						->where("uname = ?", $ref[0])
						->where("activationKey = ?", $ref[1]);
						
				$comp = $tbl->fetchRow($select);
				
				if($comp == null){
					throw new Zend_Exception('Inconsistance des clés.');
				}else{
					// destruction de l'activationKey : NULL
					$comp->lastVisit = time();
					$comp->activationKey = NULL;
					$comp->save();
					
					$user_p = $comp->toArray();
					$this->makeUserSession($user_p);
					
					$user_session = new Zend_Session_Namespace('registred');
					
					// redirection vers la page personnel de l'utilisateur
					$url = $this->view->url(array('action' => 'pageperso', 'controller' => 'communaute', 'uname' => $user_session->uname, 'welcome' => $user_session->uname),'default',true);
					$this->_redirector = $this->_helper->getHelper('Redirector');
					$front = Zend_Controller_Front::getInstance();
					$this->_redirector->setGotoUrl($front->getParam('aloa_server').$url);
					
					//$this->_redirector->gotoUrlAndExit();
					//$this->_helper->actionStack('pageperso', 'communaute', 'default', array('uname' => $user_session->uname));
					
				}
		}else{
			throw new Zend_Exception('Une tentative d\'accès non conforme a été détectée.');
		}
	
		}else{
			throw new Zend_Exception('Données indisponibles.');
		}
	}
	
	public function newmdpAction(){
		
		$params = $this->getRequest()->getParams();
		$key = $this->_getParam('key', null);
		$flag = false;
		
		$this->_helper->actionStack('hide', 'sidebar', 'default');
		
		if($key != null){
			//
			$form = $this->getChangeMdpForm($key);
			$this->view->form = $form;
			
			if($this->getRequest()->isPost()){
				// Ajout des tokens pour vérifier l'unicité des confirmation de mot de passe
				$c_mdp = $form->getElement('c_mdp');
				$c_mdp->getValidator('Identical')->setToken($params['mdp']);
				
				if($form->isValid($params)){
					// check de l'activation key
					$ref = explode(":", base64_decode($key));
					
					$tbl = new Aloa_Mdl_Tuser();
					$select = $tbl->select();
					$select->where("uname = ?", $ref[0])
							->where("activationKey = ?", $ref[1]);
							
					$comp = $tbl->fetchRow($select);
					
					if($comp == null){
						throw new Zend_Exception('Inconsistance des clés.');
					}else{
						// destruction de l'activationKey : NULL
						$comp->lastVisit = time();
						$comp->activationKey = NULL;
						// mise à jour du mot de passe
						$comp->pwd = sha1(trim($params['mdp']));
						$comp->save();
						$flag = true;
						
					}
					
				}
				
				$this->view->flag = $flag;
				
			}
		}else{
			throw new Zend_Exception('Une tentative d\'accès non conforme a été détectée.');
		}
	}
	
	public function mdpoublieAction(){
		
		$params = $this->getRequest()->getParams();

		$form = $this->getMdpOublieForm();
		// Drapeaux pour le changement d'état
		$flag = false;
		
		if($this->getRequest()->isPost()){
			if($form->isValid($params)){
				// On retrouve l'utilisateur dans la base
				$tbl = new Aloa_Mdl_Tuser();
				$select = $tbl->select()->where('email = ?', $params['email']);
				$user = $tbl->fetchRow($select);

				if($user != null){
					// Regénérer une clé d'activation
					$activationKey = sha1($user->uname.time());
					$user->activationKey = $activationKey;
					$user->save();
				
				// Envoi du mail.
				$tbl->mdpOublieMail($user);
				
				$flag = true;
				}else{
					$this->view->errmsg = "Utilisateur inconnu";
				}
			}	
		}
		$this->view->form = $form;
		$this->view->flag = $flag;
		
		$this->_helper->actionStack('hide', 'sidebar', 'default');
		
	}
	
}
?>