<?php

class CommunauteController extends Zend_Controller_Action
{

    public function indexAction()
    {

		$front = Zend_Controller_Front::getInstance();
		// Pour redéfinir le titre et description depuis un controleur
		$this->view->headTitle()->offsetSet(0, ":: ".$front->getParam('aloa_site_name')." - Communauté, conseils pratiques");
		$this->view->headMeta()->offsetSetName(1, 'description', "Retrouvez ici tous les membres du site. N'hésitez pas à visiter leurs pages personnelles pour mieux les connaitre et échanger. Dans cet espace vous trouverez également tous les conseils pratiques de la communauté.");

	
		$type_filter = $this->_getParam('type', null);
	
		// Trombinoscope des utlisateurs dans l'ordre décroissant de leur arriver
		$tuser = new Aloa_Mdl_Tuser();
		$this->view->trombi = $tuser->getLastUsers();
		
		
		// Les conseils pratiques
		if(Aloa_Hypertopic_Wrapper::check()){
			$adv_t = Aloa_Hypertopic_Wrapper::getThemeFromCommunityViewpoint('name', 'Conseils pratiques');
			$adv_t_obj = Aloa_Hypertopic_Wrapper::__callStatic("getTopic", array($adv_t['uri']));
		}else{
			$adv_t_obj = null;
		}
		
		$ids = array();
		
		foreach($adv_t_obj->entities as $ent){
			list($e_type, $e_id) = explode('_',$ent['entitypath']);
			
			

			// On filtre sur le type de contribution - si le filtre est document alors seuls les documents sont ajoutés / si pas de filtre tous sont ajoutés
			switch($type_filter){
				case 'document':
					if($e_type == Aloa_Mdl_Tcontribution::DOCUMENT){
						$ids[] = $e_id;
					}
					break;
				case 'qr':
					if($e_type == Aloa_Mdl_Tcontribution::QUESTION || $e_type == Aloa_Mdl_Tcontribution::ANSWER){
						$ids[] = $e_id;
					}
					break;						
				case 'story':
					if($e_type == Aloa_Mdl_Tcontribution::STORYTHEME || $e_type == Aloa_Mdl_Tcontribution::USERSTORY || $e_type == Aloa_Mdl_Tcontribution::REACTION){
						$ids[] = $e_id;
					}
					break;						
				case 'pgperso':
					if($e_type == Aloa_Mdl_Tcontribution::NEWMEMBER || $e_type == Aloa_Mdl_Tcontribution::PUBLICNEWS || $e_type == Aloa_Mdl_Tcontribution::NEWS){
						$ids[] = $e_id;
					}
					break;
				default:
					$ids[] = $e_id;
					break;
			}
			
			
		}

		if(count($ids) > 0){
			$tcontrib = new Aloa_Mdl_Tcontribution();
			$select = $tcontrib->select();
			$select->where('id IN (?)', $ids)->order('nbUp DESC');
			$this->view->advices = $tcontrib->fetchAll($select);
		}else{
			$this->view->advices = null;
		}
		
		//$this->view->advices = $tcontrib->find($ids);
		$this->view->type_filter = $type_filter;
		
		//$this->_helper->actionStack('index', 'sidebar', 'default', array('ttl' => 'Communauté'));
		$this->_helper->actionStack('index', 'sidebar', 'default', array('chatid' => Aloa_Mdl_Tchat::CHAT_COMMUNITY));
    }
	
	
	public function searchAction(){
	
		$rqt = $this->_getParam('rqt', null);
		$hits = null;
		
		$this->view->rqt = $rqt;

		$front = Zend_Controller_Front::getInstance();
		$index = $front->getParam('aloa_search_index');
		
		$query = Zend_Search_Lucene_Search_QueryParser::parse('+'. Aloa_Mdl_Tcontribution::SEARCH_ENGINE_PREFIX .'type:('. Aloa_Mdl_Tcontribution::NEWMEMBER .' '. Aloa_Mdl_Tcontribution::NEWS.' '. Aloa_Mdl_Tcontribution::PUBLICNEWS .') +'.$rqt);
					
		$hits = $index->find($query);
		
		$this->view->results = $hits;
		$this->_helper->actionStack('index', 'sidebar', 'default');
		
	}
	
/* Configuration - Modification des paramètres du compte */

	public function configurationAction(){
		// Attention penser au lien vers un exemple de Digest !!!
		if(Zend_Session::namespaceIsset('registred')){
			$user_session = new Zend_Session_Namespace('registred');
			$tuser = new Aloa_Mdl_Tuser();
			$user = $tuser->getByUname($user_session->uname, 1)->current();
			
			$cnfdef = array('digest' =>$user->digest, 'alert' => $user->alert);
			$cnfform = $this->getConfigForm($cnfdef);
			
			$persodef = array('firstname' => $user->firstname, 'lastname' => $user->lastname, 'email' => $user->email, 'role' => $user->role);
			$persoform = $this->getPersonalInfoForm($persodef);
			
			if($this->getRequest()->isPost()){
				$params = $this->getRequest()->getParams();
			
				if($persoform->isValid($params)){
					
					/*
					$user->alert = $params['alert'];
					$user->digest = $params['digest'];
					*/
					foreach($params as $k => $v){
						if(isset($user->$k) && $user->$k != $v){
							$user->$k = $v;
							$user_session->$k = $v;
						}
					}
					
					$user->save();
					
					$cnfdef = array('digest' =>$user->digest, 'alert' => $user->alert);
					$cnfform = $this->getConfigForm($cnfdef);
					
					$persodef = array('firstname' => $user->firstname, 'lastname' => $user->lastname, 'email' => $user->email, 'role' => $user->role);
					$persoform = $this->getPersonalInfoForm($persodef);
					
					$this->view->msg = "Vos paramètres ont été mis à jour avec succès.";
				}
			}
			
			$this->view->cnfform = $cnfform;
			$this->view->persoform = $persoform;
			
		}else{
		
			//throw new Exception('Vous devez être inscrit et connecté comme utilisateur pour pouvoir accéder à cet espace.');
			throw new Aloa_Exception_Authentification('Vous devez être inscrit et connecté comme utilisateur pour pouvoir accéder à cet espace.');
		}
		
		$this->_helper->actionStack('index', 'sidebar', 'default');
	}

	public function addnewsAction(){
	
		$params = $this->getRequest()->getParams();
		$form = $this->getNewsForm($params);
		
		if(Zend_Session::namespaceIsset('registred')){
			
			$this->view->form = $form;
			
				if($form->isValid($params)){
					
					// implication --> ou non
					if($params['isPublic'] == 0 || $params['title'] != ""){
					
						$user_session = new Zend_Session_Namespace('registred');
						$tcontrib = new Aloa_Mdl_Tcontribution();
						$contrib = $tcontrib->createRow();
						
						$contrib->type = Aloa_Mdl_Tcontribution::NEWS;
						
						if($params['isPublic'] == 1){
							$contrib->type = Aloa_Mdl_Tcontribution::PUBLICNEWS;
						}
						
						$contrib->dateTime = time();
						$contrib->author_id = $user_session->id;
						
						if(trim($params['title']) != ""){
							$contrib->title = $params['title'];
						}
						
						$contrib->content = $params['nouvelles'];
						
						$contrib->save();
						
						// On détermine l'url du billet
						$url = $this->view->url(array('action' => 'pageperso', 'controller' => 'communaute', 'uname' => $user_session->uname),'default',true);
						$front = Zend_Controller_Front::getInstance();
						
						// On ajoute la nouvelle (billet de description de sa situation)  à l'application Model						
						$resurl = $front->getParam('aloa_server').$url."#".$contrib->id;
						Aloa_Hypertopic_Wrapper::addEntityToApplicationModel($contrib->type, $resurl, $contrib->type.'_'.$contrib->id);
						
						// redirection vers la page perso au niveau du nouveau billet
						$this->_redirector = $this->_helper->getHelper('Redirector');
						$front = Zend_Controller_Front::getInstance();
						$this->_redirector->setGotoUrl($resurl);
					
					}else{
						$this->view->errmsg = "Si vous souhaitez diffuser vos nouvelles sur la page d'accueil, un titre doit être renseigné. Merci.";
					}
				}
		
		
		}else{
			throw new Aloa_Exception_Authentification('Vous devez être inscrit et connecté comme utilisateur pour pouvoir réaliser cette action.');
		}
	}
	

	
	public function usersendmailAction(){
	
		$uid = $this->_getParam('to', null);
		$params = $this->getRequest()->getParams();

		if(Zend_Session::namespaceIsset('registred')){
			if($uid != null){
				$form = $this->getUserMailForm($uid);
				
				if($form->isValid($params)){
					$tuser = new Aloa_Mdl_Tuser();
					$dest = $tuser->find($uid)->current();
					
					if($dest != null){
						$tuser->userMail($dest, $params['message']);
					}else{
						throw new Zend_Exception("Utilisateur inconnu.");
					}
					
					$url = $this->view->url(array('action' => 'pageperso', 'controller' => 'communaute', 'uname' => $dest->uname),'default',true);
					$this->_redirector = $this->_helper->getHelper('Redirector');
					$front = Zend_Controller_Front::getInstance();
					$this->_redirector->setGotoUrl($front->getParam('aloa_server').$url);
				}else{
					$this->view->form = $form;
				}				
			}else{
				throw new Zend_Exception("Paramètres incorrects.");
			}
		}else{
			throw new Aloa_Exception_Authentification('Vous devez être inscrit et connecté comme utilisateur pour pouvoir réaliser cette action.');
		}
	}


/* Forms */
	
	public function getPersonalInfoForm($def){
		if(Zend_Session::namespaceIsset('registred')){
			$user_session = new Zend_Session_Namespace('registred');
			
			$form = new Zend_Form();
			$form->setMethod('post');
			$form->setAttrib('enctype', 'multipart/form-data');
			$form->setAction($this->view->url(array('action'=>'configuration', 'controller' => 'communaute'),'default',true)); 	

			$prenom = new Zend_Form_Element_Text('firstname');
			$prenom->setLabel("Prénom :");
			$form->addElement($prenom);
			
			$nom = new Zend_Form_Element_Text('lastname');
			$nom->setLabel("Nom :");
			$form->addElement($nom);

			$email = new Zend_Form_Element_Text('email');
			$email->setLabel("Email :");
			$email->addValidator('EmailAddress');
			$email->addValidator('Db_NoRecordExists', false, array('table' => 'user', 'field' => 'email', 'exclude' => array('field' => 'id', 'value' => $user_session->id)));
			$form->addElement($email);
			
			if($user_session->role == 'aidant' || $user_session->role == 'supporter'){
				$role = new Zend_Form_Element_Radio('role', array('escape' => false, 'separator' => '<br/><br/>',
				'multiOptions' => array(
				'aidant' => "Je suis &quot;Aidant&quot; <img src='/images/aidant.png'>, j'accompagne un proche qui souffre de trouble de la mémoire.<br/>",
				'supporter' => "Je suis &quot;Supporter&quot; <img src='/images/supporter.png'>, je ne suis pas directement confronté à cette situation mais je me sens concerné.")
				));
				$form->addElement($role);
			}
			
			// Décorateurs
			$form->setElementDecorators(array(
				'ViewHelper',  
				'Errors',
				array('Description', array('placement' => 'prepend', 'escape' => false)),
				array('Label'),
				array(array('elementP' => 'HtmlTag'), array('tag'=> 'p', 'style' => 'clear:left;padding-bottom:15px'))
				));
			
			// On applique les valeurs par defaut, bien sur les éléments doivent être déclarer avant !
			$form->setDefaults($def);			
			
			$submit = new Zend_Form_Element_Submit('submit', array('label' => "  Mettre à jour mes paramètres  ", 'class' => 'btn'));
			$submit->setDecorators(array('ViewHelper'));
			$form->addElement($submit);	
			
			$reset = new Zend_Form_Element_Reset('reset', array('label' => "  Revenir aux paramètres d'origine  ", 'class' => 'btn'));
			$reset->setDecorators(array('ViewHelper'));
			$form->addElement($reset);	
			
			$form->addDisplayGroup(array('submit', 'reset'), 'end_btn');
			$form->setDisplayGroupDecorators(array(array('HtmlTag', array('tag' => 'p', 'style' => 'padding:10px')), 'FormElements'));
			
			return $form;
		}else{
			throw new Aloa_Exception_Authentification('Vous devez être inscrit et connecté comme utilisateur pour pouvoir accéder à cet espace.');
		}
	}
	
	public function getConfigForm($def){
		
		$form = new Zend_Form();
		$form->setMethod('post');
		$form->setAttrib('enctype', 'multipart/form-data');
		$form->setAction($this->view->url(array('action'=>'configuration', 'controller' => 'communaute'),'default',true)); 
		
		$alert = new Zend_Form_Element_Radio('alert', array('escape' => false, 'separator' => "<br class='f_left'/>",
		'multiOptions' => array(
        '1' => "Oui",
        '0' => "Non"))
		);
		
		$alert->setDescription("<h5> Alertes em@il </h5>
					<p>Les alertes email vous permettent d'être tenu informé chaque fois qu'un message vous est personnellement adressé. C'est un moyen pratique pour savoir si quelqu'un a répondu à une question que vous avez posé par exemple. <br/>Souhaitez-recevoir un email chaque fois qu'un message vous est personnellement adressé ?</p>");
		$form->addElement($alert);
			
		$digest = new Zend_Form_Element_Radio('digest', array('escape' => false, 'separator' => "<br/><br/>",
		'multiOptions' => array(
        'always' => "Plusieurs fois par semaine (selon l'activité sur le site)",
        'weekly' => "1 fois par semaine",
        'bi-weekly' => "1 fois toutes les 2 semaines",
        'no' => "Je ne souhaite pas recevoir de bulletin d'informations"))
		);
		
		$digest->setDescription("<h5> Bulletins d'informations </h5>
					<p>Le bulletin d'informations est le meilleur moyen de suivre l'actualité d'Aloa sans être obligé de se connecté quotidiennement. Les nouvelles questions posées et réponses apportées par la communauté, les derniers bavardages, documents, informations... grâce au bulletin d'informations vous ne ratez de ce qui se passe sur Aloa rien et participez quand vous le souhaitez. Le bulletin est diffusé par mail et vous pouvez choisir à quelle fréquence vous souhaitez le recevoir : <a href='/error/todo' title='Voir un exemple de bulletin d'informations d'Aloa'>(Voir un exemple)</a>
					</p>");
					
		$form->addElement($digest);

		// Décorateurs
		$form->setElementDecorators(array(
			'ViewHelper',  
			'Errors',
			array('Description', array('placement' => 'prepend', 'escape' => false)),
			array('Label'),
			array(array('elementP' => 'HtmlTag'), array('tag'=> 'p', 'style' => 'clear:left;padding-bottom:15px'))
			));

		// On applique les valeurs par defaut, bien sur les éléments doivent être déclarer avant !
		$form->setDefaults($def);			
		
		$submit = new Zend_Form_Element_Submit('submit', array('label' => "  Mettre à jour mes paramètres  ", 'class' => 'btn'));
		$submit->setDecorators(array('ViewHelper'));
		$form->addElement($submit);	
		
		$reset = new Zend_Form_Element_Reset('reset', array('label' => "  Revenir aux paramètres d'origine  ", 'class' => 'btn'));
		$reset->setDecorators(array('ViewHelper'));
		$form->addElement($reset);	
		
		$form->addDisplayGroup(array('submit', 'reset'), 'end_btn');
		$form->setDisplayGroupDecorators(array(array('HtmlTag', array('tag' => 'p', 'style' => 'padding:10px')), 'FormElements'));
	
		return $form;
	}
	
	public function getNewsForm($def = array()){
		
		$form = new Zend_Form();
		$form->setMethod('post');
		$form->setAttrib('enctype', 'multipart/form-data');
		$form->setAction($this->view->url(array('action'=>'addnews', 'controller' => 'communaute'),'default',true)); 
		
		$public = new Zend_Form_Element_Checkbox('isPublic', array('escape' => false));
		$public->setDescription("<img src='/images/evt_publicnews.png' class='fleft'/> <font class='arial'>Je veux que cette nouvelle soit diffusée en page d'accueil (titre obligatoire en ce cas).</font>");
		$form->addElement($public);	
		
		$ttl = new Zend_Form_Element_Text('title');
		$ttl->setDescription("Titre :");
		$form->addElement($ttl);		
		
		$news = new Zend_Form_Element_Textarea('nouvelles');
		//$news->setLabel("Donner des nouvelles :");
		$news->setDescription("Votre texte : <font class='redstar'>*</font>");
		$news->setAttrib("class","std_txtarea");
		//$news->setValue("Entrez votre texte ici...");
		$news->setRequired(true);
		$form->addElement($news);
		
		// On applique les valeurs par defaut, bien sur les éléments doivent être déclarer avant !
		$form->setDefaults($def);
		
		// Décorateurs
		$form->setElementDecorators(array(
			'ViewHelper', 
			'Errors',
			array('HtmlTag', array('tag' => 'span', 'class' => 'inpt')),
			array('Description', array('placement' => 'prepend', 'escape' => false)),
			array(array('elementP' => 'HtmlTag'), array('tag'=> 'p'))
			));

		$submit = new Zend_Form_Element_Submit('submit', array('label' => "  Publier  ", 'class' => 'btn'));
		$submit->setDecorators(array('ViewHelper'));
		
		$form->addElement($submit);
		
		// Petite correction du décorateur pour la checkbox
		$public = $form->getElement('isPublic');
		$public->removeDecorator('Description');
		$public->removeDecorator('HtmlTag');
		$public->removeDecorator('elementP');
		$public->addDecorator('Description', array('placement' => 'append', 'escape' => false, 'tag' => ''));

		
		return $form;
	}
	
	public function getUserMailForm($uid){

		$form = new Zend_Form();
		$form->setMethod('post');
		$form->setAttrib('enctype', 'multipart/form-data');
		$form->setAction($this->view->url(array('action'=>'usersendmail', 'controller' => 'communaute', 'to' => $uid),'default',true)); 
	
		$msg = new Zend_Form_Element_Textarea('message');
		$msg->setDescription("Votre message : <font class='redstar'>*</font>");
		$msg->setAttrib("class","std_txtarea");
		$msg->setRequired(true);
		$form->addElement($msg);

		// Décorateurs
		$form->setElementDecorators(array(
			'ViewHelper', 
			'Errors',
			array('HtmlTag', array('tag' => 'span', 'class' => 'inpt')),
			array('Description', array('placement' => 'prepend', 'escape' => false)),
			array(array('elementP' => 'HtmlTag'), array('tag'=> 'p'))
			));
		
		$submit = new Zend_Form_Element_Submit('submit', array('label' => "  Envoyer  ", 'class' => 'btn'));
		$form->addElement($submit);
		
		$form->addDisplayGroup(array('submit'), 'end_btn');
		$form->setDisplayGroupDecorators(array(array('HtmlTag', array('tag' => 'div')), 'FormElements'));

		return $form;
	}
	

	
/* Consultation des pages personnelles */

	public function pagepersoAction(){
		
		$uname = $this->_getParam('uname', '');
		// Message de bienvenue si requis
		$welcome = $this->_getParam('welcome', '');
		if(Zend_Session::namespaceIsset('registred')){
		
			if($uname != ''){
				
				$tbl = new Aloa_Mdl_Tuser();
				$select = $tbl->select();
				$select->from($tbl, array('id', 'uname', 'dateTime', 'tagLine', 'zip', 'city', 'avatar_id', 'role', 'lastVisit'))->where("uname = ?", $uname);
				// ->where("activationKey IS NULL");	il est délicat de gérer les évènement avec cette clause...
				$user = $tbl->fetchRow($select);
				
				if($user != null){
					
					$avatar = $user->findParentAloa_Mdl_Tavatar();
					
					$uri = Aloa_Hypertopic_Wrapper::getArgosUrl()."/entity/".$uname;
					
					$ent = Aloa_Hypertopic_Wrapper::__callStatic('getEntity', array($uri));
					
					$thm = array();

					if($ent != null){
						foreach($ent->topics as $t){
							if(Aloa_Hypertopic_Wrapper::getThemeFromUsersRootTheme('uri', $t['uri']) != null){
								$thm[] = $t;
							}
						}
					}
					
					$this->view->thm = $thm;
					
					$this->view->user_p = $user->toArray(); 
					$this->view->avatar =  $avatar->toArray();
					
					$tcontrib = new Aloa_Mdl_Tcontribution();
					// Trier dans l'autre sens ->order('dateTime ASC');
					$news = $tcontrib->getByAuthor($user->id, array(Aloa_Mdl_Tcontribution::NEWS, Aloa_Mdl_Tcontribution::PUBLICNEWS));
					$this->view->user_news = $news;
					
					$contribs = $tcontrib->getByAuthor($user->id, '*', 3);
					$this->view->user_contribs = $contribs;
					
					// Message de bienvenue lors de l'activation
					if($welcome != ''){
						$this->view->welcome = "Félicitations, votre compte utilisateur est maintenant activé.";
					}
					
					
					// Formulaire d'ajout de thèmes
					if(Aloa_Hypertopic_Wrapper::check()){
						$urootThm = Aloa_Hypertopic_Wrapper::getUsersRootTheme();
						$thmfrm = new Aloa_Form_Addtheme($user->uname, "Ajouter une aide", $urootThm->relatedTopics, null, $urootThm->viewpointId, $urootThm->topicId);
						$this->view->addthmform = $thmfrm->getView();
					}
					
					
					// Formulaire "Donner des nouvelles"
					$this->view->newsform = $this->getNewsForm();
					
					// Formulaire envoyer un mail à l'utilisateur
					$this->view->mailform = $this->getUserMailForm($user->id);
					
					// Affichage de la sidebar
					$this->_helper->actionStack('index', 'sidebar', 'default', array('userid' => $user->id));
					
					
				}else{
					throw new Zend_Exception('Utilisateur inconnu.');
				}
			}else{
				throw new Zend_Exception('Paramètres incorrects.');
			}
		
		}else{
			//throw new Exception('Vous devez être inscrit et connecté comme utilisateur pour pouvoir voir les pages personnelles');
			throw new Aloa_Exception_Authentification('Vous devez être inscrit et connecté comme utilisateur pour pouvoir voir les pages personnelles.');
		}
		
	
	}

	
	public function listuserAction(){
	// Notamment utiliser pour permettre aux utilisateurs d'établir des listes d'invitations
		$this->_helper->layout->disableLayout();
		// Attention il faut vérifier qu'il y a bien une session démarrée
		$rqt = $this->_getParam('needle', '');
		if(Zend_Session::namespaceIsset('registred')){
			if(strlen($rqt) >= 2){
				$tuser = new Aloa_Mdl_Tuser();
				$ulist = $tuser->getByUname($rqt);
				$rps = array();
				foreach($ulist as $user){
					$avtr = $user->findParentAloa_Mdl_Tavatar();
					$rps[]= $user->uname."#/images/avatar/".$avtr->file;
				}
				
				if(count($rps) > 0){
					$this->view->msg = implode(";", $rps);
				}else{
					$this->view->msg = "Nous n'avons pas trouvé d'utilisateurs correspondant à votre recherche.";
				}
			}else{
				$this->view->msg = "Nous avons besoin d'au moins 3 lettres pour effectuer la recherche. Merci.";
			}
		}else{
			$this->view->msg = "Action non autorisée. Vous devez être inscrits pour accèder à la liste des utilisateurs.";
		}
	}
}

?>