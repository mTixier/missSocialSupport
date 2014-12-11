<?php

class QuestionController extends Zend_Controller_Action
{

	public function homepageAction(){
		$tcontrib = new Aloa_Mdl_Tcontribution();
		$contrib = $tcontrib->getLastContributions(Aloa_Mdl_Tcontribution::QUESTION, 1);
		$this->view->lastcontrib = $contrib;
	}
	
    public function indexAction()
    {

		$front = Zend_Controller_Front::getInstance();
		// Pour redéfinir le titre et description depuis un controleur
		$this->view->headTitle()->offsetSet(0, ":: ".$front->getParam('aloa_site_name')." - Questions & Réponses");
		$this->view->headMeta()->offsetSetName(1, 'description', "Posez ici vos questions à la communauté et partagez vos connaissances, conseils pratiques et astuces pour améliorer le quotidien. Retrouvez également ici toutes les questions posées par les membres d'".$front->getParam('aloa_site_name').". Votre expérience peut apporter beaucoup, n'hésitez pas à proposer vos conseils.");
	
		$tcontrib = new Aloa_Mdl_Tcontribution();
				
		$select = $tcontrib->select()->from(array('q' => 'contribution'))
									->where('q.type = ?', Aloa_Mdl_Tcontribution::QUESTION)
									->joinLeft(array('r' => 'contribution'), 'q.id = r.parent_id', array('nbRps' => 'COUNT(r.parent_id)'))
									->group('q.id')
									->order('nbRps')
									->limit(3);
	
		$this->view->noanswer = $tcontrib->fetchAll($select);
		
		$this->view->lastanswers = $tcontrib->getLastContributions(Aloa_Mdl_Tcontribution::ANSWER, 3);
		
		$this->view->popanswers = $tcontrib->getPopularContributions(Aloa_Mdl_Tcontribution::QUESTION, 3);
	
		$this->_helper->actionStack('index', 'sidebar', 'default', array('chatid' => Aloa_Mdl_Tchat::CHAT_QA));
    }
	
	public function searchAction(){
	
		$rqt = $this->_getParam('rqt', null);
		$hits = null;
		
		$this->view->rqt = $rqt;
		
		//if($rqt != null){
		$front = Zend_Controller_Front::getInstance();
		$index = $front->getParam('aloa_search_index');
		
		
		//$query = new Zend_Search_Lucene_Search_Query_MultiTerm();
		//$query->addTerm(new Zend_Search_Lucene_Index_Term($rqt), null);
		//$query->addTerm(new Zend_Search_Lucene_Index_Term(Aloa_Mdl_Tcontribution::DOCUMENT, Aloa_Mdl_Tcontribution::SEARCH_ENGINE_PREFIX . 'type'), true);
		
		//$term  = new Zend_Search_Lucene_Index_Term();
		$query = Zend_Search_Lucene_Search_QueryParser::parse('+'. Aloa_Mdl_Tcontribution::SEARCH_ENGINE_PREFIX .'type:('. Aloa_Mdl_Tcontribution::QUESTION .' '. Aloa_Mdl_Tcontribution::ANSWER .') +'.$rqt);
					
		$hits = $index->find($query);
		
		//}
		//$this->view->rqt = $query;
		$this->view->results = $hits;
		$this->_helper->actionStack('index', 'sidebar', 'default');
	}
	
	public function allAction(){
		
		$tcontrib = new Aloa_Mdl_Tcontribution();
		$this->view->qr = $tcontrib->getLastContributions(Aloa_Mdl_Tcontribution::QUESTION);
		
		$this->_helper->actionStack('index', 'sidebar', 'default', array('chatid' => Aloa_Mdl_Tchat::CHAT_QA));
		
	}
	
	public function viewAction(){
		$title = $this->_getParam('title', null);
		if($title != null){
			$tcontrib = new Aloa_Mdl_Tcontribution();
			$select = $tcontrib->select();
			$select->where('type = ?', Aloa_Mdl_Tcontribution::QUESTION)->where('title LIKE ?', $title);
			$question = $tcontrib->fetchRow($select);
			
			$qid = $question->id;
			
			$bvrdgttl = $question->title;
			
			// Pour redéfinir le titre et description depuis un controleur
			$front = Zend_Controller_Front::getInstance();
			$this->view->headTitle()->offsetSet(0, ":: ".$front->getParam('aloa_site_name')." - ".$question->title);
			$this->view->headMeta()->offsetSetName(1, 'description', substr(strip_tags($question->content), 0, 140));
			
			
			$answer = $question->findAloa_Mdl_Tcontribution();
			
		}else{
			throw new Exception('Paramètres incorrects.');
		}
		
		$this->view->question = $question;
		$this->view->answer = $answer;
		$this->view->rps_form = $this->getAnswerForm($qid);
		
		// Chaque question à un bavardage associé (attention les réponses n'ont pas de bavardage associés)
		$this->_helper->actionStack('index', 'sidebar', 'default', array('contribid' => $qid));
	}
	
	public function answerAction(){
	
		$qid = $this->_getParam('to', null);
		
		if($qid != null){
		
			$form = $this->getAnswerForm($qid);
			
			
			if(Zend_Session::namespaceIsset('registred')){
				
				$params = $this->getRequest()->getParams();
				
				if($this->getRequest()->isPost()){
				
					if($form->isValid($params)){
						$user_session = new Zend_Session_Namespace('registred');
						$tcontrib = new Aloa_Mdl_Tcontribution();
						
						// Ajouter l'enregistrement dans la base de données
						$contrib = $tcontrib->createRow();
						$contrib->dateTime = time();
						$contrib->type = Aloa_Mdl_Tcontribution::ANSWER;
						$contrib->author_id = $user_session->id;
						$contrib->content = $params['msg'];
						$contrib->parent_id = $qid;
						$contrib->save();
						
						// On récupère l'intitulé de la question (à partir de parent_id)
						$question = $contrib->findParentAloa_Mdl_Tcontribution()->title;
						
						$url = $this->view->url(array('action' => 'view', 'controller' => 'question', 'title' => $this->view->sqlifyParam($question)),'default',true)."#".$contrib->id;
						$front = Zend_Controller_Front::getInstance();
						
						// Ajouter la réponse comme entité classée dans l'application model
						$resurl = $front->getParam('aloa_server').$url;
						Aloa_Hypertopic_Wrapper::addEntityToApplicationModel($contrib->type, $resurl, $contrib->type.'_'.$contrib->id);
						
						// Alerter l'auteur de la question
						$tcontrib->alertAnswerMail($contrib, $qid);
						
						// Redirection
						$this->_redirector = $this->_helper->getHelper('Redirector');
						$this->_redirector->setGotoUrl($resurl);
					}else{
					
						$this->view->form = $form;
					
					}
				
				}
				
			}else{
				//throw new Exception('Vous devez être inscrit et connecté comme utilisateur pour pouvoir proposer une réponse.');
				throw new Aloa_Exception_Authentification('Vous devez être inscrit et connecté comme utilisateur pour pouvoir proposer une réponse.');
			}
		}else{
			throw new Exception('Paramètres incorrects.');
		}
	}
	
	public function getAnswerForm($id){
		$form = new Zend_Form();
		$form->setMethod('post');
		$form->setAttrib('enctype', 'multipart/form-data');
		$form->setAction($this->view->url(array('controller'=>'question', 'action'=>'answer', 'to' => $id),'default',true));
	
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
	
	public function addAction(){
		if(Zend_Session::namespaceIsset('registred')){
			
			$params = $this->getRequest()->getParams();
			
			$def = array();
			if(isset($params['question'])){
				$def = $params;
			}
			
			$form = $this->getForm($def);
			
			if($this->getRequest()->isPost()){
			
				// Validation du titre comme unique sur la base de sqlify(question)
				$tcontrib = new Aloa_Mdl_Tcontribution();
				/*
				$select = $tcontrib->select();
				$select->where('type = ?', Aloa_Mdl_Tcontribution::QUESTION)->where('title LIKE ?', $this->view->sqlifyParam($params['question']));
				$checkUnicity = $tcontrib->fetchRow($select);*/
				
				$checkUnicity = $tcontrib->getByTitle($this->view->sqlifyParam($params['question']), Aloa_Mdl_Tcontribution::QUESTION);
				
				if($checkUnicity == null){
					if($form->isValid($params)){
					
					//
					$user_session = new Zend_Session_Namespace('registred');
					
					// Ajouter l'enregistrement dans la base de données
					$contrib = $tcontrib->createRow();
					$contrib->dateTime = time();
					$contrib->type = Aloa_Mdl_Tcontribution::QUESTION;
					$contrib->author_id = $user_session->id;
					$contrib->title = $params['question'];
					$contrib->content = $params['detail'];
					$contrib->save();
					
					// Envoyer un email d'alerte au professionnel si la quesiton leur est destinée
					if(isset($params['toward']) && $params['toward'] == 'pro'){
						$tcontrib->alertProMail($contrib);
					}
					
					
					$url = $this->view->url(array('action' => 'view', 'controller' => 'question', 'title' => $this->view->sqlifyParam($contrib->title)),'default',true);
					$front = Zend_Controller_Front::getInstance();
					
					// Ajouter la question comme entité classée dans l'application model
					$resurl = $front->getParam('aloa_server').$url;
					Aloa_Hypertopic_Wrapper::addEntityToApplicationModel($contrib->type, $resurl, $contrib->type.'_'.$contrib->id);
					
					// Redirection
					$this->_redirector = $this->_helper->getHelper('Redirector');
					$this->_redirector->setGotoUrl($resurl);
					}
					
				}else{
					// Définir un message lorsque le titre n'est pas unique.
					$this->view->errmsg = "Une question porte déjà ce titre. Nous vous proposons d'en choisir un autre. Merci.";
				}

			}
			
			$this->view->form = $form;
			//$this->_helper->actionStack('index', 'sidebar', 'default', array('ttl' => 'Question & Réponses'));
			$this->_helper->actionStack('index', 'sidebar', 'default', array('chatid' => Aloa_Mdl_Tchat::CHAT_QA));
			
		}else{
			//throw new Exception('Vous devez être inscrit et connecté comme utilisateur pour pouvoir poser une question');
			throw new Aloa_Exception_Authentification('Vous devez être inscrit et connecté comme utilisateur pour pouvoir poser une question.');
		}
	}
	
	public function askAction(){
	
		//$this->_helper->actionStack('index', 'sidebar', 'default', array('ttl' => 'Question & Réponses'));
		$this->_helper->actionStack('index', 'sidebar', 'default', array('chatid' => Aloa_Mdl_Tchat::CHAT_QA));
	}
	
	public function proposeAction(){
	
		$form = $this->getForm();

		$this->view->form = $form;
		
	}
	
	public function getForm($def=array()){
	
		$form = new Zend_Form();
		$form->setMethod('post');
		$form->setAttrib('enctype', 'multipart/form-data');
		$form->setAction($this->view->url(array('controller'=>'question', 'action'=>'add'),'default',true));
		
		$question = new Zend_Form_Element_Text('question');
		$question->setRequired(true);
		$question->setDescription("<font class='arial_b'>Votre question : </font><font class='redstar'>*</font>");
		$form->addElement($question);
		
		$detail = new Zend_Form_Element_Textarea('detail');
		$detail->setDescription("<a class='info' href='javascript:void(0)'><font class='arial_b'>Message :</font>
					<span>Ce champs texte vous permet de donner plus de détails sur le contexte de votre question afin d'aider les autres membres à se faire une idée de votre situation.</span></a><font class='redstar'>*</font>");
		$detail->setAttrib("class","std_txtarea");
		$detail->setRequired(true);
		$form->addElement($detail);

		$invit = new Zend_Form_Element_Hidden('recepient_list');
		$invit->setDescription("<div style='margin-bottom:10px;'>
		<h4><a class='info' href='javascript:void(0)'>Signaler ce document à d'autres membres : 
<span>Le champs recherche (<img src='/images/loupe.png' style='padding:1px;'/>) vous permet de trouver les aloïstes par les premières lettres de leur nom et de les ajouter par un simple clic dans la liste des invités.</span></a></h4><div style='float:left;'>
				<div><img src='/images/loupe.png'>&nbsp;:&nbsp;<input id='recepient_selector' onKeyUp='javascript:getRcptList();'/><br/>&nbsp;</div>
				<div class='arial_sml'>Cliquer sur le <img src='/images/add.png'> pour ajouter :</div>
				<ul class='profil_selector' id='slct'>

				</ul>
				</div>
				
				<div class='separation'>
				<br/><br/>
				
				<div class='arial_sml' style='padding-top:5px'>Liste des membres à informer</div>
				
				<ul class='recepient' id='rcpt'>

				</ul>
				</div>
				</div>
			");
		//$form->addElement($invit);
		

		$toward = new Zend_Form_Element_Hidden('toward');
		$toward->setDescription("
			<div class='mnu_block'><img src='/images/mnu_qr_ct.png' class='mnu_picto' title=\"Cliquez sur l'image pour poser votre question\"><div class='mnu_pres'><h4>Poser votre question à la communauté</h4><p>Sollicitez la communauté afin d'obtenir des réponses et conseils pratiques pour gérer le quotidien. De nombreux aloïstes ont acquis une grande expérience qui peut servir à tous et chacun peut apporter des réponses utiles aux autres.</p><input type='button' value='Poser ma question à la communauté' onClick=\"javascript:ed = nicEditors.findEditor('detail');ed.saveContent();document.getElementById('toward').value = 'ct';this.form.submit();\"/></div></div>

	<div class='mnu_block'><img src='/images/mnu_qr_pro.png' class='mnu_picto' title=\"Cliquez sur l'image pour poser votre question\"><div class='mnu_pres'><h4>Poser votre question à la communauté et aux professionnels</h4><p>Vous souhaitez bénéficier d'une réponse certifiée ? En cliquant sur ce bouton, Aloa relaira votre question à un professionnel de santé du réseau qui essaiera de vous répondre dans les meilleurs délais (comptez au moins 2 jours). Sa réponse sera signalée sur fond bleu.</p><input type='button' value='Poser ma question à la communauté et aux professionnels' onClick=\"javascript:ed = nicEditors.findEditor('detail');ed.saveContent();document.getElementById('toward').value = 'pro';this.form.submit();\"/></div></div>
			");
		$toward->setRequired(true);
		$form->addElement($toward);
		
		// Décorateurs
		$form->setElementDecorators(array(
			'ViewHelper', 
			'Errors',
			array('HtmlTag', array('tag' => 'span', 'class' => 'inpt')),
			array('Description', array('placement' => 'prepend', 'escape' => false)),
			array(array('elementP' => 'HtmlTag'), array('tag'=> 'p'))
			));

			
		// On applique les valeurs par defaut, bien sur les éléments doivent être déclarer avant !
		$form->setDefaults($def);
		
		return $form;
	
	}
	
}

