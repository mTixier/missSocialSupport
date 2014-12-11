<?php
class InscriptionController extends Zend_Controller_Action{

	public function indexAction(){
		// On cache la sidebar
		$this->_helper->actionStack('hide', 'sidebar', 'default');
		
		// Récupération des paramètre de la requête le cas échéant
		$params = $this->getRequest()->getParams();
		
		// Le flag fait office de frein pour empêcher la validation consécutive de deux étapes (car apparition de messages d'erreurs alors que l'utilisateur n'a encore rien remplis)
		$flag = true;
		
		//Marqueur d'étape
		$this->view->step = 1;
		
		
		if(Zend_Session::namespaceIsset('visiteur')){
			$visiteur = new Zend_Session_Namespace('visiteur');
			
			if(isset($visiteur->inscription) && array_key_exists('step', $visiteur->inscription)){
			
				// Mécanisme de retour à l'étape pécédente
				if(array_key_exists('back', $params)){
					$visiteur->inscription['step'] = $params['back'];
				}
				
				// Traitement de l'étape 1
				if($visiteur->inscription['step'] == 1 ){
					// Création du formulaire pour la validation et chargement des valeurs par defaut stockée en session (cf fonction retour à l'étape précédente)
					$form = $this->getStep1Form($visiteur->inscription);
					
					// Si des données sont présentes, on les valide.
					if($this->getRequest()->isPost()){
						
						// Ajout des tokens pour vérifier l'unicité des confirmation de mail et de mot de passe
						$c_mdp = $form->getElement('c_mdp');
						$c_mdp->getValidator('Identical')->setToken($params['mdp']);
						$c_email = $form->getElement('c_email');
						$c_email->getValidator('Identical')->setToken($params['email']);
						
						// Si les données sont valides, on les stock en session et on passe à l'étape suivante.
						if($form->isValid($params)){
							$visiteur->inscription['step'] = 2;
							$visiteur->inscription = array_merge($visiteur->inscription, $params);
							// On ne stock pas le mot de passe lui même en session
							$visiteur->inscription['enc_mdp'] = sha1(trim($params['mdp']));
							unset($visiteur->inscription['mdp']);
							unset($visiteur->inscription['c_mdp']);
							
							
							$flag = false;
						}
					}
					
					$this->view->form = $form;
				}
				//
				
				// Traitement de l'étape 2
				if($visiteur->inscription['step'] == 2 ){
					
					$form = $this->getStep2Form($visiteur->inscription);
				
					// Si des données sont présentes, on les valide. 
					if($this->getRequest()->isPost() && $flag){
					
						// Si les données sont valides, on les stockent en session et on passe à l'étape suivante.
						if($form->isValid($params)){
						
						$trsfr = $form->avatar_file->getTransferAdapter();
						
						if($trsfr->receive()){
							if($form->avatar_file->isUploaded()){
								$visiteur->inscription['avatar_file'] = $form->avatar_file->getFileName();
							}else{
								$visiteur->inscription['avatar_file'] = "Error";
							}
						}else{
							$visiteur->inscription['avatar_file'] = "Error";
						}
							$visiteur->inscription['step'] = 3;
							$visiteur->inscription = array_merge($visiteur->inscription, $params);
							$flag = false;

						}	
					}
					
					$this->view->avtr_sel = $this->getAvatar();
					$this->view->form = $form;
				}
				//
				
				// Traitement de l'étape 3
				if($visiteur->inscription['step'] == 3 ){
					
					$form = $this->getStep3Form($visiteur->inscription);
					// Si des données sont présentes, on les valide. 
					if($this->getRequest()->isPost() && $flag){
					
						// Si les données sont valides, on les stockent en session et on passe à l'étape suivante.
						if($form->isValid($params)){
							$visiteur->inscription['step'] = 4;
							$visiteur->inscription = array_merge($visiteur->inscription, $params);
							$flag = false;
						}
					}
					
					$this->view->form = $form;
				}
				//
				
				// Traitement de l'étape 4
				if($visiteur->inscription['step'] == 4 ){
					// Destruction de la session d'inscription après l'envoi du mail d'activation - ActionStack > pop()
					//$this->_helper->actionStack('delusersession', 'login', 'default');
					$this->_helper->actionStack('unsetnamespace', 'login', 'default', array('key' => 'visiteur'));
					$this->registerUser();
					$visiteur->inscription['step'] = 5;	
				}
				//
				
				// Fin de l'inscription 
				if($visiteur->inscription['step'] == 5 ){
					$this->view->form = $this->getStep4Form();
					
				}
				
				//Marqueur d'étape - Forcément à la fin compte tenu des traitements
				$this->view->step = $visiteur->inscription['step'];
				
			}else{
				// Etape 0 - Initialisation de la session
				$this->view->form = $this->getStep1Form();
				$visiteur->inscription = array();
				$visiteur->inscription['step'] = 1;
				//
			}
		}
	}
	
/* useful */
	
	public function addUserViewpoint($uname){
		// sera à null si jamais la connexion n'a pas pu se faire...
		$argosUrl = Aloa_Hypertopic_Wrapper::getArgosUrl();
		$vpt = Aloa_Hypertopic_Wrapper::__callStatic('addViewpoint', array($argosUrl, $uname));
		//On ajoute par defaut le thème Non classé.
		Aloa_Hypertopic_Wrapper::__callStatic('addTopic', array($vpt, 'Non classé'));
		
		return $vpt;
	}
	
	public function addUserEntity($uname, $thm = null, $other = null){
		if(Aloa_Hypertopic_Wrapper::check()){		
				$keywords = array();
				
				// Enregistrer la page personnelle comme entité sur Argos en lui affectant les thèmes d'aide selectionné avec ajout si besoin
				//addEntity($serverUrl, $entityPath, $topics = null, $attributes = null, $resources = null, $username = null, $password = null);
				$topics = array();

				if($thm != null){
					
					if(in_array('other', $thm) && $other != null){
						// Utiliser plutôt le UserViewpoint !!!
						$newTopicUri = Aloa_Hypertopic_Wrapper::__callStatic('addTopic', array(Aloa_Hypertopic_Wrapper::getCommunityViewpoint()->uri, $other));
						// other est forcément à la fin
						array_pop($thm);
						$obj = Aloa_Hypertopic_Wrapper::__callStatic('getTopic', array($newTopicUri));
						// Tranlitération car add entity gère les topics comme des array et non comme des objets...
						$topics[] = array("viewpointid" => $obj->viewpointId,
											"name" => $obj->topicName,
											"topicid" => $obj->topicId,
											"uri" => $obj->uri,
										);
						$keywords[] = $obj->topicName;
					}
					
					foreach($thm as $uri){
						//$t = Aloa_Hypertopic_Wrapper::getThemeFromCommunityViewpoint('uri', $uri);
						$t = Aloa_Hypertopic_Wrapper::getThemeFromUsersRootTheme('uri', $uri);
						$topics[] = $t;
						$keywords[] = $t['name'];
					}
					
				}
				// Il faut penser à rajouter la page perso dans le modèle de l'application par la suite si la connexion à Agorae n'a pu se faire.
				$topics[] = Aloa_Hypertopic_Wrapper::getThemeFromApplicationModel('name', 'Page personnelle');
				
				// On ajoute la page personnelle comme ressource - bof, ce serait mieux un vrai objet ressource (mais si c'est comme pour les thèmes...)
				$front = Zend_Controller_Front::getInstance();
				$resources = array(array('resourcename' => 'html', 'resourceurl' => $front->getParam('aloa_server').$this->view->url(array('controller'=>'communaute', 'action'=>'pageperso', 'uname' => $uname),'default',true)));
				
				$argosUrl = Aloa_Hypertopic_Wrapper::getArgosUrl();
				$entityPath = '/'.$uname;
				
				Aloa_Hypertopic_Wrapper::__callStatic('addEntity', array($argosUrl, $entityPath, $topics, null, $resources));
				
				// Renvoi la liste des nom de thèmes pour les keywords destinés au moteur de recherche
				$keywords[] = $uname;
				return implode(", ", $keywords);
		}else{
			return null;
		}
	}

	public function registerUser(){
		// On créer le nouvel utilisateur dans la DB
		if(Zend_Session::namespaceIsset('visiteur')){
			$visiteur = new Zend_Session_Namespace('visiteur');
			
			if(isset($visiteur->inscription)){
				$tbl = new Aloa_Mdl_Tuser();
				$new_user = $tbl->createRow();
				
				$new_user->dateTime = time();
				$new_user->uname = $visiteur->inscription['uname'];
				$new_user->tagLine = $visiteur->inscription['dvz'];
				$new_user->firstname = $visiteur->inscription['prenom'];
				$new_user->lastname = $visiteur->inscription['nom'];
				$new_user->zip = $visiteur->inscription['cp'];
				$new_user->city = $visiteur->inscription['local'];
				$new_user->email = $visiteur->inscription['email'];
				$new_user->pwd = $visiteur->inscription['enc_mdp'];
				$new_user->lastVisit = time();
				// Déterminer si l'inscrit a définit un avatar personnalisé
				if($visiteur->inscription['avatar_file'] == "Error"){
					$avtr = explode("_", $visiteur->inscription['avatar_selected']);
					$new_user->avatar_id = $avtr[1];
				}else{
					$new_user->avatar_id = $this->registerAvatar($visiteur->inscription['avatar_file'], $visiteur->inscription['uname']);
				}
				
				$new_user->role = $visiteur->inscription['role'];
				
				// Trés problèmatique si à ce moment la connecion à Argos était indisponible -- Création si n'existe pas pour chaque ajout de favoris !!!
				$new_user->viewpoint = $this->addUserViewpoint($visiteur->inscription['uname']);
				
				$thm = isset($visiteur->inscription['theme'])?$visiteur->inscription['theme']:null;
				$other =  isset($visiteur->inscription['other_theme'])?$visiteur->inscription['other_theme']:null;
				$new_user->keywords = $this->addUserEntity($visiteur->inscription['uname'], $thm, $other);

				$activationKey = sha1($visiteur->inscription['uname'].time());
				$new_user->activationKey = $activationKey;
				
				$new_user->save();
				
				$tbl_contrib = new Aloa_Mdl_Tcontribution();
				
				// Créer une contribution pour marquer l'inscription - pas forcément trés pertinent...
				$inscr = $tbl_contrib->createRow();
				$inscr->dateTime = time();
				$inscr->type = Aloa_Mdl_Tcontribution::NEWMEMBER;
				$inscr->author_id = $new_user->id;
				
				// Temporaire car dès lors que l'on pourra éditer les thèmes cela n'aura pas de sens
				$inscr->keywords = $new_user->keywords;
				
				$inscr->save();
				
				// Créer un premier billet avec ma situation 
				
				$new_news = $tbl_contrib->createRow();
				$new_news->dateTime = time();
				$new_news->type = Aloa_Mdl_Tcontribution::NEWS;
				$new_news->author_id = $new_user->id;
				$new_news->content = $visiteur->inscription['situation'];
				$new_news->save();
				
				// On ajoute la nouvelle (billet de description de sa situation)  à l'application Model
				$url = $this->view->url(array('action' => 'pageperso', 'controller' => 'communaute', 'uname' => $new_user->uname),'default',true)."#".$new_news->id;
				$front = Zend_Controller_Front::getInstance();
				$resurl = $front->getParam('aloa_server').$url;
				Aloa_Hypertopic_Wrapper::addEntityToApplicationModel($new_news->type, $resurl, $new_news->type.'_'.$new_news->id);
				
				// On envoi le mail d'activation.
				$visiteur->inscription['activationKey'] = $activationKey;
				$tbl->welcomeMail();
				//$this->_helper->actionStack('welcome', 'mail', 'default');
				
			}else{
				throw new Zend_Exception('Données d\'inscription indisponibles.');
				// ou noRender et redirection auto sur page d'erreur... ?
			}
		}
		
		
	}

	public function changeavatarAction(){
	
		// On cache la sidebar
		$this->_helper->actionStack('hide', 'sidebar', 'default');
	
		if(Zend_Session::namespaceIsset('registred')){
			$user_session = new Zend_Session_Namespace('registred');
			
			$def = array('avatar_selected' => 'avtr_'.$user_session->avatar_id);
			$form = $this->getChangeAvatarForm($def);
			
			$tavtr = new Aloa_Mdl_Tavatar();
			$this->view->avatars = $tavtr->getAvatars($user_session->uname);
			
			if($this->getRequest()->isPost()){
				$params = $this->getRequest()->getParams();
			
				if($form->isValid($params)){
				
					// Si l'utilisateur upload un nouveau fichier
						$trsfr = $form->avatar_file->getTransferAdapter();
						if($trsfr->receive() && $form->avatar_file->isUploaded()){
							// ajouter à la table avatar
							$avtr_id = $this->registerAvatar($form->avatar_file->getFileName(), $user_session->uname);
						}else{
						
							list($needle, $avtr_id) = explode('_', $params['avatar_selected']);
						}
					
					// Mettre à jour le champs correspondant pour l'utilisateur
					$tuser = new Aloa_Mdl_Tuser();
					$user = $tuser->find($user_session->id)->current();
					$user->avatar_id = $avtr_id;
					$user->save();
					
					// On met au passage la valeur à jour en session
					$user_session->avatar_id = $user->avatar_id;
					
					/* Redirection */
					$this->_redirector = $this->_helper->getHelper('Redirector');
					//$front = Zend_Controller_Front::getInstance();
					$this->_redirector->setGotoUrl($this->view->url(array('action' => 'pageperso', 'controller' => 'communaute', 'uname' => $user_session->uname))); 
					
				}
				
			}
			
			$this->view->form = $form;
		}else{
			throw new Aloa_Exception_Authentification('Vous devez être inscrit et connecté comme utilisateur pour pouvoir réaliser cette action.');
		}
	}
	
	public function getChangeAvatarForm($def=array()){
		$form = new Zend_Form();
		$form->setMethod('post');
		$form->setAttrib('enctype', 'multipart/form-data');
		$form->setAction($this->view->url(array('controller'=>'inscription', 'action'=>'changeavatar'),'default',true));
		
		// Informations pour la page personelle
		// Ce champs est nécessaire pour la selection de l'avatar à l'aide d'un composant javascript créer à l'aide du partial(p_avatar.phtml)
		$slctd_avtr = new Zend_Form_Element_Hidden('avatar_selected');
		//$slctd_avtr->setValue('avtr_2');
		$form->addElement($slctd_avtr);
		
		$f_avtr = new Zend_Form_Element_File('avatar_file');
		$f_avtr->setLabel("Ou, choisir une image personnalisée sur mon disque dur (cet avatar vous sera personnel et ne pourra être choisi par d'autres utilisateurs) : ");
		$f_avtr->setDestination(realpath(APPLICATION_PATH . "/../public/upload"));
		$f_avtr->addValidator('Count', false, 1);
		// Limit 1000 Ko
		$f_avtr->addValidator('Size', false, 1024000);
		$f_avtr->setRequired(false);
		$f_avtr->addValidator('Extension', false, 'jpg,png,gif');
		$form->addElement($f_avtr, 'avatar_file');
		
		// On termine par les décorateurs, sinon les changements ne s'appliquent pas
		$form->setDecorators(array('FormElements', 'Form'));
		$form->setElementDecorators(array(
			'ViewHelper',  
			'Errors',
			array('HtmlTag', array('tag' => 'span', 'class' => 'inpt')),
			array('Description', array('tag' => 'font', 'class' => 'redstar', 'placement' => 'prepend')),
			array('Label'),
			array(array('elementP' => 'HtmlTag'), array('tag'=> 'p'))
			));
		
		// Les éléments File ont besoin d'un décorateur spécifique : 'File'
		$f_avtr = $form->getElement('avatar_file');
		$f_avtr->addDecorator('File');
		$f_avtr->addDecorator('Errors');
		$f_avtr->removeDecorator('ViewHelper');
				
		// On applique les valeurs par defaut, bien sur les éléments doivent être déclarer avant !
		$form->setDefaults($def);

		$cmplt = "";
		$front = Zend_Controller_Front::getInstance();
		if(Zend_Session::namespaceIsset('visiteur')){
			$visitor = new Zend_Session_Namespace('visiteur');
			$cmplt = ($visitor->precedingUrl == null)?"":$visitor->precedingUrl;
		}
		$url = $front->getParam('aloa_server').$cmplt;
		
		// Les boutons
		$undo = new Zend_Form_Element_Button('undo', array('label' => "  Annuler  ", 'class' => 'btn'));
		$undo->setAttrib("onClick", "javascript:document.location.href = '".$url."';");
		$undo->setDecorators(array('ViewHelper'));
		$form->addElement($undo);
		
		$submit = new Zend_Form_Element_Submit('submit', array('label' => "  Changer mon avatar ", 'class' => 'btn'));
		$submit->setDecorators(array('ViewHelper'));
		$form->addElement($submit);	
		
		$form->addDisplayGroup(array('undo', 'submit'), 'btn');
		$form->setDisplayGroupDecorators(array(array('HtmlTag', array('tag' => 'p', 'style' => 'padding:10px')), 'FormElements'));
		
		return $form;
	}
	
	public function getAvatar(){
		$tbl = new Aloa_Mdl_Tavatar();
		$select = $tbl->select()->where("belongsTo = 'all'");
		$avatars = $tbl->fetchAll($select);
		return $avatars;
	}
	
	// Ajoute une ligne à la table Avatar après avoir créer les fichiers images associé
	public function registerAvatar($fsrc, $belongsTo='all'){
		$tbl = new Aloa_Mdl_Tavatar();
		$new_avtr = $tbl->createRow();
		$new_avtr->belongsTo = $belongsTo;
		$new_avtr->file = 'tmp';
		$new_avtr->save();
		
		$id = $new_avtr->id;
		$images = $this->makeAvatar($fsrc, $id, realpath(APPLICATION_PATH . "/../public/images/avatar"));
		$new_avtr->file = $images[0];
		$new_avtr->save();
		return $id;
	}
	
	// Créer des thumbnails  avtr_id : 120x120 ; mid_avtr_id : 60x60 ; sml_avtr_id : 30x30
	public function makeAvatar($fsrc, $id, $outdir, $out = array('avtr_' => '120x120', 'mid_avtr_' => '60x60', 'sml_avtr_' => '30x30')){
		
		// On isole le type de fichier
		$lastpt = strrpos($fsrc, ".")+1;
		$type = strtolower(substr($fsrc,$lastpt, strlen($fsrc)-$lastpt));
		
		//preg_match('/jpg|jpeg/',$type)
		if ($type == "jpg" || $type == "jpeg"){
			$src_img=imagecreatefromjpeg($fsrc);
		}
		if ($type == "png"){
			$src_img=imagecreatefrompng($fsrc);
		}
		if ($type == "gif"){
			$src_img=imagecreatefromgif($fsrc);
		}
		
		$old_x=imageSX($src_img);
		$old_y=imageSY($src_img);
		
		$res = array();
		
		foreach($out as $nm => $size){
			$size = explode("x",$size);
			// créer une image de w x h
			$out_img = ImageCreateTrueColor($size[0],$size[1]);
			imagecopyresampled($out_img,$src_img,0,0,0,0,$size[0],$size[1],$old_x,$old_y); 
			
			$fnm = $nm.$id.".".$type;
			$res[] = $fnm;
			$fout = $outdir."/".$fnm;
			
			if ($type == "jpg" || $type == "jpeg"){
				imagejpeg($out_img, $fout);
			}
			if ($type == "png"){
				imagepng($out_img, $fout);
			}
			if ($type == "gif"){
				imagegif($out_img, $fout);
			}
			imagedestroy($out_img); 
		}
		imagedestroy($src_img);
		
		return $res;
	}
	
	public function upldavatarAction(){
		// On cache la sidebar
		$this->_helper->actionStack('hide', 'sidebar', 'default');
		if(Zend_Session::namespaceIsset('registred')){
				
			$user_session = new Zend_Session_Namespace('registred');
			
			if($user_session->rights == 'admin'){
				
				$form = new Zend_Form();
				$form->setMethod('post');
				$form->setAttrib('enctype', 'multipart/form-data');
				$form->setAction($this->view->url(array('controller'=>'inscription', 'action'=>'upldavatar'),'default',true));
				
				$f_avtr = new Zend_Form_Element_File('avatar_file');
				$f_avtr->setLabel("Ajouter un avatar : ");
				$f_avtr->setDestination(realpath(APPLICATION_PATH . "/../public/upload"));
				$f_avtr->addValidator('Count', false, 1);
				// Limit 1000 Ko
				$f_avtr->addValidator('Size', false, 1024000);
				$f_avtr->setRequired(false);
				$f_avtr->addValidator('Extension', false, 'jpg,png,gif');
				$form->addElement($f_avtr, 'avatar_file');
				
				// Les éléments File ont besoin d'un décorateur spécifique : 'File'
				$f_avtr = $form->getElement('avatar_file');
				$f_avtr->addDecorator('File');
				$f_avtr->addDecorator('Errors');
				$f_avtr->removeDecorator('ViewHelper');
				
				$submit = new Zend_Form_Element_Submit('submit', array('label' => "  Envoyer  ", 'class' => 'btn'));
				$submit->setDecorators(array('ViewHelper'));
				$form->addElement($submit);	
				
					
				
				if($this->getRequest()->isPost()){
					
					$params = $this->getRequest()->getParams();
					
					// Si les données sont valides, on les stockent en session et on passe à l'étape suivante.
					if($form->isValid($params)){
								
						$trsfr = $form->avatar_file->getTransferAdapter();
								
						if($trsfr->receive()){
							if($form->avatar_file->isUploaded()){
								
								$this->registerAvatar($form->avatar_file->getFileName());
							}
						}
					}
				}
		
			$this->view->form = $form;
		}else{
			$this->view->form = "Vous n'avez pas les droits suffisants pour effectuer cette action.";
		}
		}else{
			throw new Aloa_Exception_Authentification('Vous devez être inscrit et connecté comme utilisateur pour pouvoir effectuer cette action.');
		}
	}
	
/* Forms */

	public function getStep1Form($def=array()){
		$form = new Zend_Form();
		$form->setMethod('post');
		$form->setAction($this->view->url(array('controller'=>'inscription', 'action'=>'index'),'default',true));
		
		
		// Informations personnelles
		$prenom = new Zend_Form_Element_Text('prenom');
		$prenom->setLabel("Prénom :");
		$form->addElement($prenom);
		
		$nom = new Zend_Form_Element_Text('nom');
		$nom->setLabel("Nom :");
		$form->addElement($nom);
		
		$cp = new Zend_Form_Element_Text('cp');
		$cp->setLabel("Code Postal :");
		$cp->addValidator('digits');
		$cp->addValidator('StringLength', false, array(2, 8));
		$cp->setRequired(true);
		$cp->setDescription('*');
		$form->addElement($cp);
		
		$local = new Zend_Form_Element_Text('local');
		$local->setLabel("Ville/Localité :");
		//$local->addValidator('alpha');
		$local->setRequired(true);
		$local->setDescription('*');
		$form->addElement($local);
		
		$form->addDisplayGroup(array('prenom', 'nom', 'cp', 'local'), 'infoperso', array('legend' => "Informations personnelles", "description" => ""));
		
		// Paramètres du compte
		/*
		$uname = new Zend_Form_Element_Text('uname');
		$uname->setLabel("Nom d'utilisateur :");
		// Validation check DB if exist !!!
		$uname->addValidator('alnum');
		$uname->setRequired(true);
		$uname->setDescription('*');
		$form->addElement($uname);
		*/
		$email = new Zend_Form_Element_Text('email');
		$email->setLabel("Email :");
		$email->addValidator('EmailAddress');
		$email->addValidator('Db_NoRecordExists', false, array('table' => 'user', 'field' => 'email'));
		$email->setRequired(true);
		$email->setDescription('*');
		$form->addElement($email);

		$c_email = new Zend_Form_Element_Text('c_email');
		$c_email->setLabel("Confirmer l'email :");
		$c_email->addValidator('EmailAddress');
		// Attention penser au setToken au moment de la validation !!!  $passTwice->getValidator('Identical')->setToken($data['password']); cf http://www.emanaton.com/code/php/validateidenticalfield
		$c_email->addValidator('Identical');
		$c_email->setRequired(true);
		$c_email->setDescription('*');
		$form->addElement($c_email);

		$mdp = new Zend_Form_Element_Password('mdp');
		$mdp->setLabel("Mot de passe :");
		$mdp->addValidator('StringLength', false, array(6, 20));
		$mdp->setRequired(true);
		$mdp->setDescription('*');
		$form->addElement($mdp);

		$c_mdp = new Zend_Form_Element_Password('c_mdp');
		$c_mdp->setLabel("Confirmer le mot de passe :");
		$c_mdp->addValidator('StringLength', false, array(6, 20));
		// Attention penser au setToken au moment de la validation !!!  $passTwice->getValidator('Identical')->setToken($data['password']); cf http://www.emanaton.com/code/php/validateidenticalfield
		$c_mdp->addValidator('Identical');
		$c_mdp->setRequired(true);
		$c_mdp->setDescription('*');
		$form->addElement($c_mdp);
		
		$form->addDisplayGroup(array('email', 'c_email', 'mdp', 'c_mdp'), 'paramcmpt', array('legend' => "Configuration de votre compte", "description" => "Afin de créer votre compte utilisateur sur Aloa, nous vous demandons votre adresse email et de définir un mot de passe pour vous connecter sur le site. Conseil : Créez un mot de passe que vous n'utilisez nulle part ailleurs."));
		
		// On applique les valeurs par defaut, bien sur les éléments doivent être déclarer avant !
		$form->setDefaults($def);
		
		// On termine par les décorateurs, sinon les changements ne s'appliquent pas
		$form->setDecorators(array('FormElements', 'Form'));
		$form->setDisplayGroupDecorators(array(array('Description', array('tag' => 'p')), 'FormElements', 'Fieldset'));
		$form->setElementDecorators(array(
			'ViewHelper', 
			'Errors',
			array('HtmlTag', array('tag' => 'span', 'class' => 'inpt')),
			array('Description', array('tag' => 'font', 'class' => 'redstar', 'placement' => 'prepend')),
			array('Label'),
			array(array('elementP' => 'HtmlTag'), array('tag'=> 'p', 'class' => 'f_left'))
			));

		$submit = new Zend_Form_Element_Submit('submit', array('label' => "  Passer à l'étape suivante  ", 'class' => 'btn'));
		$submit->setDecorators(array('ViewHelper'));
		$form->addElement($submit);
		
		return $form;
	}

	public function getStep2Form($def=array()){
		$form = new Zend_Form();
		$form->setMethod('post');
		$form->setAttrib('enctype', 'multipart/form-data');
		$form->setAction($this->view->url(array('controller'=>'inscription', 'action'=>'index'),'default',true));
		
		// Informations pour la page personelle
		// Ce champs est nécessaire pour la selection de l'avatar à l'aide d'un composant javascript créer à l'aide du partial(p_avatar.phtml)
		$slctd_avtr = new Zend_Form_Element_Hidden('avatar_selected');
		$slctd_avtr->setValue('avtr_2');
		#$slctd_avtr->setRequired(true);
		$form->addElement($slctd_avtr);
		
		$f_avtr = new Zend_Form_Element_File('avatar_file');
		$f_avtr->setLabel("Ou, choisir une image personnalisée sur mon disque dur (cet avatar vous sera personnel et ne pourra être choisi par d'autres utilisateurs) : ");
		$f_avtr->setDestination(realpath(APPLICATION_PATH . "/../public/upload"));
		$f_avtr->addValidator('Count', false, 1);
		// Limit 1000 Ko
		$f_avtr->addValidator('Size', false, 1024000);
		$f_avtr->setRequired(false);
		$f_avtr->addValidator('Extension', false, 'jpg,png,gif');
		$form->addElement($f_avtr, 'avatar_file');
		
		$uname = new Zend_Form_Element_Text('uname');
		$uname->setLabel("Nom d'utilisateur (sans espaces, ni caractères accentués ou ponctuation) :");
		
		$uname_vld = new Zend_Validate_Regex('/^[a-z0-9]+$/i');
		$uname_vld->setMessage("Le nom d'utilisateur ne doit contenir que des lettres ou des chiffres, sans accents, ni ponctuations ou espaces.", Zend_Validate_Regex::NOT_MATCH);
		$uname->addValidator($uname_vld, false);
		
		$uname->addValidator('StringLength', false, array(4, 20));
		$uname->addValidator('Db_NoRecordExists', false, array('table' => 'user', 'field' => 'uname'));
		$uname->setRequired(true);
		$uname->setDescription('*');
		$form->addElement($uname);
		
		$dvz = new Zend_Form_Element_Text('dvz');
		$dvz->setLabel("Devise :");
		$dvz->addValidator('StringLength', false, array(null, 140));
		//$dvz->setDescription('*');
		$form->addElement($dvz);
		
		// On termine par les décorateurs, sinon les changements ne s'appliquent pas
		$form->setDecorators(array('FormElements', 'Form'));
		$form->setDisplayGroupDecorators(array(array('Description', array('tag' => 'p', 'escape' => false)), 'FormElements', 'Fieldset'));
		$form->setElementDecorators(array(
			'ViewHelper',  
			'Errors',
			array('HtmlTag', array('tag' => 'span', 'class' => 'inpt')),
			array('Description', array('tag' => 'font', 'class' => 'redstar', 'placement' => 'prepend')),
			array('Label'),
			array(array('elementP' => 'HtmlTag'), array('tag'=> 'p'))
			));
		
		// Les éléments File ont besoin d'un décorateur spécifique : 'File'
		$f_avtr = $form->getElement('avatar_file');
		$f_avtr->addDecorator('File');
		$f_avtr->addDecorator('Errors');
		$f_avtr->removeDecorator('ViewHelper');
				
		// On applique les valeurs par defaut, bien sur les éléments doivent être déclarer avant !
		$form->setDefaults($def);

		// Les boutons
		$prcdt = new Zend_Form_Element_Button('prcdt', array('label' => "  Revenir à l'étape précédente  ", 'class' => 'btn'));
		$prcdt->setAttrib("onClick", "javascript:document.location.href = '".$this->view->url(array('controller'=>'inscription', 'action'=>'index', 'back' => '1' ),'default',true)."';");
		$prcdt->setDecorators(array('ViewHelper'));
		$form->addElement($prcdt);
		
		$submit = new Zend_Form_Element_Submit('submit', array('label' => "  Passer à l'étape suivante  ", 'class' => 'btn'));
		$submit->setDecorators(array('ViewHelper'));
		$form->addElement($submit);	
		
		return $form;
	}

	public function getStep3Form($def=array()){
		$form = new Zend_Form();
		$form->setMethod('post');
		$form->setAttrib('enctype', 'multipart/form-data');
		$form->setAction($this->view->url(array('controller'=>'inscription', 'action'=>'index'),'default',true));
		
		
		// Informations pour la page personnelle
		
		// role
		$role = new Zend_Form_Element_Radio('role', array('escape' => false, 'separator' => '<br/><br/>',
		'multiOptions' => array(
        'aidant' => "Je suis &quot;Aidant&quot; <img src='/images/aidant.png'>, j'accompagne un proche qui souffre de trouble de la mémoire.<br/>",
        'supporter' => "Je suis &quot;Supporter&quot; <img src='/images/supporter.png'>, je ne suis pas directement confronté à cette situation mais je me sens concerné.")
		));
		$role->setRequired(true);
		$form->addElement($role);
		
		
		
		// Les thèmes figurant sur les pages personnelles (en l'occurence)
		if(Aloa_Hypertopic_Wrapper::check()){
			$obj = Aloa_Hypertopic_Wrapper::getUsersRootTheme();
			
			$reltopics = array();
			foreach($obj->relatedTopics as $topic => $p){
				$name = $p['name'];
				$uri = $p['uri'];
				$reltopics[$uri] = $name;
			}
			$reltopics['other'] = "Autre : <label><input type='text' name='other_theme' onBlur=".'"'."javascript:document.getElementById('theme-other').checked = (this.value!='')?true:false;".'"'."/></label>";
			
			$thm = new Zend_Form_Element_MultiCheckbox('theme', array('escape' => false, 'separator' => '', 'multiOptions' => $reltopics));

			$thm->setLabel("De quelles aides bénéficiez-vous ?");
			$form->addElement($thm);
		}
		
		
		$situation = new Zend_Form_Element_Textarea('situation');
		$situation->setLabel("Pourriez vous vous présenter et nous dire quelques mots de votre situation ?");
		$situation->setAttrib("class","std_txtarea");
		$situation->setRequired(true);
		$form->addElement($situation);

		//Quesitons annexes
		/*
		$anx = new Zend_Form_Element_Textarea('anx');
		$anx->setLabel("Comment avez vous entendu parler d'Aloa ? ");
		$anx->setAttrib("class","std_txtarea");
		$form->addElement($anx);
		*/
		
		$form->addDisplayGroup(array('role', 'theme', 'situation'), 'pageperso', array('legend' => "La maladie d'Alzheimer et les troubles de la mémoire pour vous", "description" => "<font class='redstar'>*</font> Présentez ici votre situation aux autres utilisateurs du site. Ces renseignements serviront à créer votre page personnelle."));
		
		//$form->addDisplayGroup(array('anx'), 'q_anx', array('legend' => "Questions annexes"));
		
		// On termine par les décorateurs, sinon les changements ne s'appliquent pas
		$form->setDecorators(array('FormElements', 'Form'));
		$form->setDisplayGroupDecorators(array(array('Description', array('tag' => 'p', 'escape' => false)), 'FormElements', 'Fieldset'));
		$form->setElementDecorators(array(
			'ViewHelper',  
			'Errors',
			array('HtmlTag', array('tag' => 'span', 'class' => 'inpt')),
			array('Description', array('tag' => 'font', 'class' => 'redstar', 'placement' => 'prepend')),
			array('Label'),
			array(array('elementP' => 'HtmlTag'), array('tag'=> 'p'))
			));	
		
		//Correction du décorateur des checkbox
		if(Aloa_Hypertopic_Wrapper::check()){
			$thm = $form->getElement('theme');
			$thm->removeDecorator('Label');
			$thm->addDecorator('Label', array('style' => 'float:none'));
			$thm->addDecorator(array('elementDiv' => 'HtmlTag'), array('tag'=> 'div', 'style' => 'float:left;padding:20px 0px 0px 0px;'));
		}
		
		// Correction des décorateurs des textarea
		$situation = $form->getElement('situation');
		$situation->removeDecorator('Label');
		$situation->addDecorator('Label', array('tag' => 'div', 'style' => 'float:none;'));
		$situation->addDecorator(array('elementDiv' => 'HtmlTag'), array('tag'=> 'div', 'style' => 'float:left;padding:10px 0px 10px 0px;'));
		
		/*
		$anx = $form->getElement('anx');
		$anx->removeDecorator('Label');
		$anx->addDecorator('Label', array('tag' => 'p', 'style' => 'float:none'));
		*/
		
		// On applique les valeurs par defaut, bien sur les éléments doivent être déclarer avant !
		$form->setDefaults($def);

		// Les boutons
		$prcdt = new Zend_Form_Element_Button('prcdt', array('label' => "  Revenir à l'étape précédente  ", 'class' => 'btn'));
		$prcdt->setAttrib("onClick", "javascript:document.location.href = '".$this->view->url(array('controller'=>'inscription', 'action'=>'index', 'back' => '2' ),'default',true)."';");
		$prcdt->setDecorators(array('ViewHelper'));
		$form->addElement($prcdt);
		
		$submit = new Zend_Form_Element_Submit('submit', array('label' => "  Valider mon inscription  ", 'class' => 'btn'));
		$submit->setDecorators(array('ViewHelper'));
		$form->addElement($submit);	
		
		return $form;
	}
	
	public function getStep4Form($def=array()){
		$form = new Zend_Form();
		$form->setMethod('post');
		$form->setAction($this->view->url(array('controller'=>'inscription', 'action'=>'index'),'default',true));	
		
		$dummy = new Zend_Form_Element_Hidden('dummy');
		$form->addElement($dummy);
		
		$form->addDisplayGroup(array('dummy'), 'fin', array('legend' => "Félicitations", "description" => "Vous êtes maintenant inscrit sur Aloa. Il ne reste qu'une dernière étape ; un mail de confirmation vient de vous être envoyé. Il vous suffit de cliquer sur le lien dans le message pour activer votre compte et vous connecter sur le site."));

		// On termine par les décorateurs, sinon les changements ne s'appliquent pas
		$form->setDecorators(array('FormElements', 'Form'));
		$form->setDisplayGroupDecorators(array(array('Description', array('tag' => 'p', 'escape' => false)), 'FormElements', 'Fieldset'));
		
		$prcdt = new Zend_Form_Element_Button('prcdt', array('label' => "  Revenir à l'accueil  ", 'class' => 'btn'));
		$prcdt->setAttrib("onClick", "javascript:document.location.href = '".$this->view->url(array('controller'=>'index', 'action'=>'index'),'default',true)."';");
		$prcdt->setDecorators(array('ViewHelper'));
		$form->addElement($prcdt);
		
		return $form;
	}
	
	
}
?>