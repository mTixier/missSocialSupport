<?php

class StoryController extends Zend_Controller_Action
{

    public function indexAction()
    {
	
		$front = Zend_Controller_Front::getInstance();
		// Pour redéfinir le titre et description depuis un controleur
		$this->view->headTitle()->offsetSet(0, ":: ".$front->getParam('aloa_site_name')." - Témoigner");
		$this->view->headMeta()->offsetSetName(1, 'description', "Partagez une anecdote, votre expérience et invitez la communauté à s'exprimer sur un sujet qui vous tient à coeur en lançant un tour de table. Vous pouvez aussi simplement rejoindre un tour de table existant pour partager votre expérience, vos opinions et votre histoire.");
	
		$tcontrib = new Aloa_Mdl_Tcontribution();
					
		$this->view->laststories = $tcontrib->getLastContributions(Aloa_Mdl_Tcontribution::USERSTORY, 3);
		
		$select = $tcontrib->select()->from(array('q' => 'contribution'))
							->where('q.type = ?', Aloa_Mdl_Tcontribution::STORYTHEME)
							->joinLeft(array('r' => 'contribution'), 'q.id = r.parent_id', array('nbStory' => 'COUNT(r.parent_id)'))
							->group('q.id')
							->order('nbStory')
							->limit(3);
		
		$this->view->poptheme = $tcontrib->fetchAll($select);
		
		// Sur un critère différent attention ...
		// $this->view->popthm = $tcontrib->getPopularContributions(Aloa_Mdl_Tcontribution::STORYTHEME, 3);
		
		//$this->_helper->actionStack('index', 'sidebar', 'default', array('ttl' => 'Témoignages'));
		$this->_helper->actionStack('index', 'sidebar', 'default', array('chatid' => Aloa_Mdl_Tchat::CHAT_STORIES));
    }
	
	public function homepageAction(){
		$tcontrib = new Aloa_Mdl_Tcontribution();
		$contrib = $tcontrib->getLastContributions(Aloa_Mdl_Tcontribution::STORYTHEME, 1);
		$this->view->lastcontrib = $contrib;
	}
	
	public function searchAction(){
	
		$rqt = $this->_getParam('rqt', null);
		$hits = null;
		
		$this->view->rqt = $rqt;

		$front = Zend_Controller_Front::getInstance();
		$index = $front->getParam('aloa_search_index');
		
		$query = Zend_Search_Lucene_Search_QueryParser::parse('+'. Aloa_Mdl_Tcontribution::SEARCH_ENGINE_PREFIX .'type:('. Aloa_Mdl_Tcontribution::USERSTORY .' '. Aloa_Mdl_Tcontribution::REACTION .') +'.$rqt);
					
		$hits = $index->find($query);
		
		$this->view->results = $hits;
		$this->_helper->actionStack('index', 'sidebar', 'default');
		
	}
	
	public function proposeAction(){
		// Récupération du sujet de témoignage pour adaptation automatique du formulaire à l'intérieur d'un suejt de témoignage
		$filter = $this->_getParam('storytheme', null);
	
		$form = $this->getForm($filter);

		$this->view->form = $form;
		
	}

	public function getForm($sbj_filter=null, $def=array()){

		$form = new Zend_Form();
		$form->setMethod('post');
		$form->setAttrib('enctype', 'multipart/form-data');
		if($sbj_filter != null){
			$form->setAction($this->view->url(array('controller'=>'story', 'action'=>'add', 'storytheme' => $sbj_filter),'default',true));
		}else{
			$form->setAction($this->view->url(array('controller'=>'story', 'action'=>'add'),'default',true));
		}
		
		$sbj = "";
		if($sbj_filter == null){
			$subject = new Zend_Form_Element_Text('new_subject');
			$subject->setRequired(true);
			$subject->setDescription("<font class='arial_b'>Sujet : </font><font class='redstar'>*</font>");
			$form->addElement($subject);
		}else{
			$tcontrib = new Aloa_Mdl_Tcontribution();
			$subject = $tcontrib->getByTitle($sbj_filter, Aloa_Mdl_Tcontribution::STORYTHEME);	
			$sbj = "<h4><a name='contribuer'><img src='/images/evt_story.png'/></a>&nbsp;
Je veux témoigner aussi sur : <em>".$subject->title."</em></h4>";
		}
		
		$ttl = new Zend_Form_Element_Text('title');
		#$ttl->setLabel("Titre :");
		$ttl->setRequired(true);
		$ttl->setDescription($sbj."<h4>Votre témoignage : <font class='redstar'>*</font></h4><font class='arial'>Titre : </font>");
		$form->addElement($ttl);
		
		$userstory = new Zend_Form_Element_Textarea('userstory');
		//$desc->setLabel("Description : ");
		//$userstory->setDescription("<font class='redstar'>*</font>");
		$userstory->setAttrib("class","std_txtarea");
		$userstory->setRequired(true);
		$form->addElement($userstory);

		$invit = new Zend_Form_Element_Hidden('recepient_list');
		$invit->setDescription("<div style='margin-bottom:10px;'>
		<h4><a class='info' href='javascript:void(0)'>Inviter d'autres membres à témoigner sur le sujet : 
<span>Le champs recherche (<img src='/images/loupe.png' style='padding:1px;'/>) vous permet de trouver les aloïstes par les premières lettres de leur nom et de les ajouter par un simple clic dans la liste des invités.</span></a></h4><div style='float:left;'>
				<div><img src='/images/loupe.png'>&nbsp;:&nbsp;<input id='recepient_selector' onKeyUp='javascript:getRcptList();'/><br/>&nbsp;</div>
				<div class='arial_sml'>Cliquer sur le <img src='/images/add.png'> pour ajouter :</div>
				<ul class='profil_selector' id='slct'>

				</ul>
				</div>
				
				<div class='separation'>
				<br/><br/>
				
				<div class='arial_sml' style='padding-top:5px'>Liste des membres à contacter</div>
				
				<ul class='recepient' id='rcpt'>

				</ul>
				</div>
				</div>
			");
		$form->addElement($invit);
				
		// Décorateurs
		$form->setElementDecorators(array(
			'ViewHelper',  
			'Errors',
			array('HtmlTag', array('tag' => 'span', 'class' => 'inpt')),
			array('Description', array('placement' => 'prepend', 'escape' => false)),
			array('Label', ),
			array(array('elementP' => 'HtmlTag'), array('tag'=> 'p'))
			));
		
		$submit = new Zend_Form_Element_Submit('submit', array('label' => "  Publier ", 'class' => 'btn'));
		$submit->setDecorators(array('ViewHelper'));
		$form->addElement($submit);	

		// On applique les valeurs par defaut, bien sur les éléments doivent être déclarer avant !
		$form->setDefaults($def);
		
		return $form;
	}

	public function addAction(){

		if(Zend_Session::namespaceIsset('registred')){
			
			$p_subject = $this->_getParam('storytheme', null);
			
			$params = $this->getRequest()->getParams();
			
			$def = array();
			if(isset($params['title'])){
				$def = $params;
			}
			
			$form = $this->getForm($p_subject, $def);
			
			if($this->getRequest()->isPost()){
			
				if($form->isValid($params)){
				
					$user_session = new Zend_Session_Namespace('registred');
					$tcontrib = new Aloa_Mdl_Tcontribution();
					$front = Zend_Controller_Front::getInstance();
					
					// Créer le sujet de témoignage s'il y a lieu - collision entre les param subject en GET et POST... malencontreux même si l'effet de bord est intéressant
					if($p_subject == null){
						$checkUnicity = $tcontrib->getByTitle($this->view->sqlifyParam($params['new_subject']), Aloa_Mdl_Tcontribution::STORYTHEME);
						if($checkUnicity == null){
							$subject = $tcontrib->createRow();
							$subject->author_id = $user_session->id;
							$subject->dateTime = time();
							$subject->type = Aloa_Mdl_Tcontribution::STORYTHEME;
							$subject->title = $params['new_subject'];
							$subject->save();
							
							// Ajouter le sujet de témoignage comme entité classée dans l'application model
							$url = $this->view->url(array('action' => 'view', 'controller' => 'story', 'storytheme' => $this->view->sqlifyParam($subject->title)),'default',true);
							$resurl = $front->getParam('aloa_server').$url;
							Aloa_Hypertopic_Wrapper::addEntityToApplicationModel($subject->type, $resurl, $subject->type.'_'.$subject->id);
							
						}else{
							// Le titre proposer existe déjà en tant que tel donc on fait suivre la requête de façon transparente
							$subject = $checkUnicity;
							// Du coup on redéfinit form pour y ajouter le bon sujet sqlifier
							$form = $this->getForm($this->view->sqlifyParam($subject->title), $def);
						}
					}else{
						//  Pour avoir le titre dé-sqlifier
						$subject = $tcontrib->getByTitle($p_subject, Aloa_Mdl_Tcontribution::STORYTHEME);	
					}
					
					// Créer le témoignage - Attention l'uinicité du titre devrait se vérifier à l'échelle d'un sujet de témoignage !!!
					
					$checkUnicity = $tcontrib->getByTitle($this->view->sqlifyParam($params['title']), Aloa_Mdl_Tcontribution::USERSTORY, $subject->id);
					if($checkUnicity == null){
						$story = $tcontrib->createRow();
						$story->author_id = $user_session->id;
						$story->dateTime = time();	
						$story->type = Aloa_Mdl_Tcontribution::USERSTORY;
						$story->title = $params['title'];
						$story->content = $params['userstory'];
						$story->parent_id = $subject->id;
						$story->save();
						
						
						$url = $this->view->url(array('action' => 'view', 'controller' => 'story', 'storytheme' => $this->view->sqlifyParam($subject->title), 'userstory' => $this->view->sqlifyParam($story->title)),'default',true);
						
						// Ajouter le témoignage comme entité classée dans l'application model
						$resurl = $front->getParam('aloa_server').$url;
						Aloa_Hypertopic_Wrapper::addEntityToApplicationModel($story->type, $resurl, $story->type.'_'.$story->id);
						
						//Rediriger vers la page de témoignage frâichement créée
						$this->_redirector = $this->_helper->getHelper('Redirector');
						$this->_redirector->setGotoUrl($resurl);	
							
					}else{
						// Définir un message lorsque le titre n'est pas unique (en toute logique, il est obligatoire que le sujet existe et que l'on ne l'ait pas crée).
						$this->view->errmsg = "Une question porte déjà ce titre. Nous vous proposons d'en choisir un autre. Merci.";
					}

				}else{
					$this->view->form = $form;
					$this->_helper->actionStack('index', 'sidebar', 'default');
				}
			}

		}else{
			//throw new Exception('Vous devez être inscrit et connecté comme utilisateur pour pouvoir déposer un document');
			throw new Aloa_Exception_Authentification('Vous devez être inscrit et connecté comme utilisateur pour pouvoir proposer un témoignage.');
		}
	}
	

	public function viewAction(){
	
		// Préparation du formulaire pour témoigner sur le  sujet courant
		$p_subject = $this->_getParam('storytheme', null);
		$p_title =  $this->_getParam('userstory', null);
		$form = $this->getForm($p_subject);
		$this->view->tform = $form;
		
		
		$tcontrib = new Aloa_Mdl_Tcontribution();
		
		// Titre du sujet
		$subject = $tcontrib->getByTitle($p_subject, Aloa_Mdl_Tcontribution::STORYTHEME);
		$this->view->subject = $subject;
		
		// Liste des contributions /témoignage sur le sujet
		$select = $tcontrib->select();
		$select->where('type = ?', Aloa_Mdl_Tcontribution::USERSTORY)->where('parent_id = ?', $subject->id)->order('dateTime ASC');
		$usersStories = $tcontrib->fetchAll($select);
		$this->view->usersStories = $usersStories;
		
		// L'histoire courament selectionnée
		if($p_title != null){
			$currentStory = $tcontrib->getByTitle($p_title, Aloa_Mdl_Tcontribution::USERSTORY, $subject->id);
		}else{
			$currentStory = $usersStories->current();
		}
		$this->view->currentStory = $currentStory;
		
		// Pour redéfinir le titre et description depuis un controleur
		$front = Zend_Controller_Front::getInstance();
		$this->view->headTitle()->offsetSet(0, ":: ".$front->getParam('aloa_site_name')." - ".$subject->title);
		$this->view->headMeta()->offsetSetName(1, 'description', substr(strip_tags($currentStory->title." : ".$currentStory->content), 0, 140));
		
		
		// Les reactions associées au témoignage courant
		$select = $tcontrib->select();
		$select->where('type = ?', Aloa_Mdl_Tcontribution::REACTION)->where('parent_id = ?', $currentStory->id)->order('dateTime ASC');
		$messages = $tcontrib->fetchAll($select);
		$this->view->messages = $messages;
		 
		// Le formulaire pour laisser un message une récation sur le témoignage courant
		$this->view->msgform = $this->getMessageForm($currentStory->id);
		
		// Chaque sujet de témoignage à un bavardage associé (pas chaque témoignage)
		$this->_helper->actionStack('index', 'sidebar', 'default', array('contribid' => $subject->id));
	}
	
	public function messageAction(){
		if(Zend_Session::namespaceIsset('registred')){
			
			$p_story = $this->_getParam('parent', null);
			
			$params = $this->getRequest()->getParams();
			
			$def = array();
			if(isset($params['parent'])){
				$def = $params;
			
			
				$form = $this->getMessageForm($p_story, $def);
				
				if($this->getRequest()->isPost()){
				
					if($form->isValid($params)){
					
						$user_session = new Zend_Session_Namespace('registred');
						$tcontrib = new Aloa_Mdl_Tcontribution();
						
						$msg = $tcontrib->createRow();
						$msg->author_id = $user_session->id;
						$msg->dateTime = time();
						$msg->type = Aloa_Mdl_Tcontribution::REACTION;
						$msg->content = $params['msg'];
						$msg->parent_id = $params['parent'];
						$msg->save();
						
						$story = $msg->findParentAloa_Mdl_Tcontribution();
						$subject = $story->findParentAloa_Mdl_Tcontribution();


						
						$url = $this->view->url(array('action' => 'view', 'controller' => 'story', 'storytheme' => $this->view->sqlifyParam($subject->title), 'userstory' => $this->view->sqlifyParam($story->title)),'default',true);
						$front = Zend_Controller_Front::getInstance();
						$resurl = $front->getParam('aloa_server').$url."#".$msg->id;
						
						// Ajouter la reaction comme entité classée dans l'application model
						Aloa_Hypertopic_Wrapper::addEntityToApplicationModel($msg->type, $resurl, $msg->type.'_'.$msg->id);
						
						// Alerter l'auteur du témoignage
						$tcontrib->alertReactionMail($story, $msg);
						
						// Redirection
						$this->_redirector = $this->_helper->getHelper('Redirector');
						$this->_redirector->setGotoUrl($resurl);	
						
					}else{
						$this->view->form = $form;
						$this->_helper->actionStack('index', 'sidebar', 'default');
					}
				}
			}else{
				throw new Exception('Paramètres incorrects.');
			}

		}else{
			//throw new Exception('Vous devez être inscrit et connecté comme utilisateur pour pouvoir déposer un document');
			throw new Aloa_Exception_Authentification('Vous devez être inscrit et connecté comme utilisateur pour pouvoir publier un message.');
		}
	
	}

	public function getMessageForm($id, $def=array()){
		$form = new Zend_Form();
		$form->setMethod('post');
		$form->setAttrib('enctype', 'multipart/form-data');
		$form->setAction($this->view->url(array('controller'=>'story', 'action'=>'message', 'parent' => $id),'default',true));
	
		$msg = new Zend_Form_Element_Textarea('msg');
		$msg->setDescription("<font class='arial_b'>Votre message : </font><font class='redstar'>*</font>");
		$msg->setAttrib("class","std_txtarea");
		$msg->setRequired(true);
		$form->addElement($msg);
		
		$submit = new Zend_Form_Element_Submit('submit', array('label' => "  Répondre  ", 'class' => 'btn'));
		$submit->setDecorators(array('ViewHelper'));
		$form->addElement($submit);	
		
		// Décorateurs
		$form->setElementDecorators(array(
			'ViewHelper', 
			'Errors',
			array('HtmlTag', array('tag' => 'span', 'class' => 'inpt')),
			array('Description', array('placement' => 'prepend', 'escape' => false)),
			array(array('elementP' => 'HtmlTag'), array('tag'=> 'p'))
			));
		

		
		return $form;
	}
	
}

