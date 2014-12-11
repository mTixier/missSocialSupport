<?php

class DocumentController extends Zend_Controller_Action
{

	public function homepageAction(){
		$tcontrib = new Aloa_Mdl_Tcontribution();
		$contrib = $tcontrib->getLastContributions(Aloa_Mdl_Tcontribution::DOCUMENT, 1);
		$this->view->lastcontrib = $contrib;
	}

    public function indexAction()
    {
	
		$front = Zend_Controller_Front::getInstance();
		// Pour redéfinir le titre et description depuis un controleur
		$this->view->headTitle()->offsetSet(0, ":: ".$front->getParam('aloa_site_name')." - S'informer");
		$this->view->headMeta()->offsetSetName(1, 'description', "La bibliothèque ".$front->getParam('aloa_site_name')." vous permet de partager les sites webs et documents qui vous ont été utiles. En ouvrant la barre latérale vous pouvez discuter avec les membres de la communauté et laisser vos commentaires et impressions sur les documents.");
	
		// Ne renvoyer que les thèmes où des document existent ??
		$thm = array();
		if(Aloa_Hypertopic_Wrapper::check()){
			$vpt = Aloa_Hypertopic_Wrapper::getCommunityViewpoint();
			$thm = $vpt->topics;
		}
		$this->view->thm = $thm;
		
		$tcontrib = new Aloa_Mdl_Tcontribution();
		$this->view->lastdoc = $tcontrib->getLastContributions(Aloa_Mdl_Tcontribution::DOCUMENT, 3);
	
		$this->view->popdoc = $tcontrib->getPopularContributions(Aloa_Mdl_Tcontribution::DOCUMENT, 3);

		$this->_helper->actionStack('index', 'sidebar', 'default', array('chatid' => Aloa_Mdl_Tchat::CHAT_DOCUMENT));
    }
	
	public function themeAction(){
		$tid = $this->_getParam('theme_id', null);
		
		$this->view->tid = $tid;
		
		$docs = array();
		
		if($tid != null){
		
			if(Aloa_Hypertopic_Wrapper::check()){
				// Récupérer la liste des entité classée comme document dans ApplicaitonModel
				$app_t = Aloa_Hypertopic_Wrapper::getThemeFromApplicationModel('name', 'Document');
				
				// Récupérer la liste des entité classée comme theme_id dans CommunityViewpoint
				$ct_t = Aloa_Hypertopic_Wrapper::getThemeFromCommunityViewpoint('topicid', $tid);
				$this->view->theme = $ct_t['name'];
			
				$docs = array();
				$tcontrib = new Aloa_Mdl_Tcontribution();
				
				$docs_ent = Aloa_Hypertopic_Wrapper::intersectTheme($app_t['uri'],$ct_t['uri']);
				
				foreach($docs_ent as $e){
					$select = $tcontrib->select();
					list($doc_type, $doc_id) = explode('_', $e['entitypath']);
					$select->where('type = ?', Aloa_Mdl_Tcontribution::DOCUMENT)->where('id = ?', $doc_id);
					//$select->where('type = ?', Aloa_Mdl_Tcontribution::DOCUMENT)->where('title LIKE ?', $this->view->sqlifyParam($e['entitypath']));
					$docs[] = $tcontrib->fetchRow($select);
				}
				
			}

		}else{
			throw new Zend_Exception('Aucun thème n\'a été fourni comme paramètre.');
		}
		
		$this->view->docs = $docs;
		//$this->_helper->actionStack('index', 'sidebar', 'default', array('ttl' => 'Documents'));
		$this->_helper->actionStack('index', 'sidebar', 'default', array('chatid' => Aloa_Mdl_Tchat::CHAT_DOCUMENT));
	}

	public function searchAction(){
	
		$rqt = $this->_getParam('rqt', null);
		$hits = null;
		
		$this->view->rqt = $rqt;
		
		//if($rqt != null){
		$front = Zend_Controller_Front::getInstance();
		$index = $front->getParam('aloa_search_index');
		
		$query = Zend_Search_Lucene_Search_QueryParser::parse('+'. Aloa_Mdl_Tcontribution::SEARCH_ENGINE_PREFIX .'type:'. Aloa_Mdl_Tcontribution::DOCUMENT ." +".$rqt);
					
		$hits = $index->find($query);
		
		//}
		//$this->view->rqt = $query;
		$this->view->results = $hits;
		$this->_helper->actionStack('index', 'sidebar', 'default');
		
	}
	
	public function viewAction(){
		$title = $this->_getParam('title', null);
		if($title != null){
			$tcontrib = new Aloa_Mdl_Tcontribution();
			$select = $tcontrib->select();
			$select->where('type = ?', Aloa_Mdl_Tcontribution::DOCUMENT)->where('title LIKE ?', $title);
			$doc = $tcontrib->fetchRow($select);
			//$bvrdgttl = $doc->title;
			$contrib_id = $doc->id;
			
			// Pour redéfinir le titre et description depuis un controleur
			$front = Zend_Controller_Front::getInstance();
			$this->view->headTitle()->offsetSet(0, ":: ".$front->getParam('aloa_site_name')." - ".$doc->title);
			$this->view->headMeta()->offsetSetName(1, 'description', substr(strip_tags($doc->content), 0, 140));
			
			
			
		}else{
			$doc = null;
			$contrib_id = null;
			//$bvrdgttl = null;
		}
		$this->view->doc = $doc;
		$this->view->contrib_id = $contrib_id;
		
		// Chaque document a un bavardage associé
		$this->_helper->actionStack('index', 'sidebar', 'default', array('contribid' => $contrib_id));
	}

	public function proposeAction(){
		// Récupération de l'indice du thème pour filtrage si consultation des documents par thèmes.
		$tfilter = $this->_getParam('theme_id', null);
	
		$form = $this->getForm($tfilter);

		$this->view->form = $form;
		
	}
	
	public function getForm($thm_filter=null, $def=array()){

		$form = new Zend_Form();
		$form->setMethod('post');
		$form->setAttrib('enctype', 'multipart/form-data');
		$form->setAction($this->view->url(array('controller'=>'document', 'action'=>'add', 'theme_id' => $thm_filter),'default',true));
		
		$ttl = new Zend_Form_Element_Text('title');
		#$ttl->setLabel("Titre :");
		$ttl->setRequired(true);
		$ttl->setDescription("<font class='arial_b'>Titre : </font><font class='redstar'>*</font>");
		$form->addElement($ttl);
		
		$desc = new Zend_Form_Element_Textarea('description');
		#$desc->setLabel("Description : ");
		$desc->setDescription("<font class='arial_b'>Description : </font><font class='redstar'>*</font>");
		$desc->setAttrib("class","std_txtarea");
		$desc->setRequired(true);
		$form->addElement($desc);
		
		// Les thèmes du point de vue communautairepour classer les documents
		if(Aloa_Hypertopic_Wrapper::check()){
			$vpt = Aloa_Hypertopic_Wrapper::getCommunityViewpoint();
			
			$thm = array();
			foreach($vpt->topics as $t){
				// Filtre sur les thèmes
				if($t['topicid'] != $thm_filter && $t['name'] != 'Non classé'){
					$thm[$t['uri']] = $t['name']; 
				}
			}

			$thm['other'] = "Autre : <label><input type='text' name='other_theme' onBlur=".'"'."javascript:document.getElementById('theme-other').checked = (this.value!='')?true:false;".'"'."/></label><p>&nbsp;</p><p>&nbsp;</p>";
			
			$theme = new Zend_Form_Element_MultiCheckbox('theme', array('escape' => false, 'separator' => '', 'multiOptions' => $thm));

			$theme->setDescription("<font class='arial_b'>Thème(s) :</font>");
			$form->addElement($theme);
		}
				
		$f_doc = new Zend_Form_Element_File('document_file');
		$f_doc->setDescription("<font class='arial_b'>Déposer un fichier : </font><font class='redstar'>*</font>");
		$f_doc->setDestination(realpath(APPLICATION_PATH . "/../public/upload"));
		$f_doc->addValidator('Count', false, 1);
		// Limit 1000 Ko
		$f_doc->addValidator('Size', false, 1024000);
		$f_doc->setRequired(false);
		//$f_doc->addValidator('Extension', false, 'pdf,doc,htm,html');
		$form->addElement($f_doc, 'document_file');
		
		$url = new Zend_Form_Element_Text('document_url');
		// array(Zend_Validate_Hostname::ALLOW_DNS, false, false)
		$url->addValidator('Regex', false, array('/(f|ht)tps?:\/\/([-\w\.]+)+(:\d+)?(\/([\w\/_\.]*(\?\S+)?)?)?/'));
		$url->setLabel("ou l'adresse d'un site internet :");
		$form->addElement($url);

		$invit = new Zend_Form_Element_Hidden('recepient_list');
		$invit->setDescription("<div style='margin-bottom:10px;'>
		<h4><a class='info' href='javascript:void(0)'>Signaler ce document à d'autres membres : 
<span>Le champs recherche (<img src='/images/loupe.png' style='padding:1px;'/>) vous permet de trouver les utilisateurs par les premières lettres de leur nom et de les ajouter par un simple clic dans la liste des invités.</span></a></h4><div style='float:left;'>
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
		
		// Les éléments File ont besoin d'un décorateur spécifique : 'File'
		$f_doc = $form->getElement('document_file');
		$f_doc->addDecorator('File');
		$f_doc->addDecorator('Description', array('placement' => 'prepend', 'escape' => false));
		$f_doc->addDecorator('Errors');
		$f_doc->removeDecorator('ViewHelper');
		
		$submit = new Zend_Form_Element_Submit('submit', array('label' => "  Déposer le document ", 'class' => 'btn'));
		$submit->setDecorators(array('ViewHelper'));
		$form->addElement($submit);	

		// On applique les valeurs par defaut, bien sur les éléments doivent être déclarer avant !
		$form->setDefaults($def);
		
		return $form;
	}
	
	public function addAction(){
	
		// Revoir le décorateur des thèmes
		if(Zend_Session::namespaceIsset('registred')){
			
			$tfilter = $this->_getParam('theme_id', null);
			
			$params = $this->getRequest()->getParams();
			
			$def = array();
			if(isset($params['title'])){
				$def = $params;
			}
			
			$form = $this->getForm($tfilter, $def);
			
			if($this->getRequest()->isPost()){
			
				// Validation du titre comme unique sur la base de sqlify(title)
				$tcontrib = new Aloa_Mdl_Tcontribution();
				$select = $tcontrib->select();
				$select->where('type = ?', Aloa_Mdl_Tcontribution::DOCUMENT)->where('title LIKE ?', $this->view->sqlifyParam($params['title']));
				$checkUnicity = $tcontrib->fetchRow($select);
				if($checkUnicity == null){
					if($form->isValid($params)){
					
					//
					$user_session = new Zend_Session_Namespace('registred');
					
					// Ajouter l'enregistrement dans la base de données
					
					$contrib = $tcontrib->createRow();
					$contrib->dateTime = time();
					$contrib->type = Aloa_Mdl_Tcontribution::DOCUMENT;
					$contrib->author_id = $user_session->id;
					$contrib->title = $params['title'];
					$contrib->content = $params['description'];
					$contrib->save();
									
					// Uploader et déplacer le fichier
						$trsfr = $form->document_file->getTransferAdapter();
						
						if(isset($params['document_url']) && $params['document_url'] != ""){
						
							$url = $params['document_url'];
											
						}else if($trsfr->receive()){
								if($form->document_file->isUploaded()){
								
									// Nommer le fichier
									$fsrc = $form->document_file->getFileName();
										// On isole le type de fichier
									$lastpt = strrpos($fsrc, ".")+1;
									$type = strtolower(substr($fsrc,$lastpt, strlen($fsrc)-$lastpt));
									$fnm = $contrib->id.".".$type;

									$fdest = realpath(APPLICATION_PATH . "/../public/documents")."/".$fnm;
									rename($fsrc, $fdest);
								}
							
							$url = Zend_Controller_Front::getInstance()->getParam('aloa_server')."/documents/".$fnm;

						
						}else{
							$this->view->errmsg = "Vous n'avez indiqué ni fichier, ni adresse de site internet à ajouter : ";
							$this->view->form = $form;
							//$this->_helper->actionStack('index', 'sidebar', 'default', array('ttl' => 'Documents'));
							$this->_helper->actionStack('index', 'sidebar', 'default', array('chatid' => Aloa_Mdl_Tchat::CHAT_DOCUMENT));
							exit();
						}
						
					$tdoc = new Aloa_Mdl_Tdocument();
					$doc = $tdoc->createRow();
					$doc->url = $url;
					$doc->contrib_id = $contrib->id;
					$doc->save();
									
					// Ajouter les thèmes choisis par l'utilisateur pour le document				
					$thm = isset($params['theme'])?$params['theme']:null;
					// pour les formulaire de dépot à l'intérieur des thèmes - On ajoute automatiquement le thème correspondant
					if($tfilter != null){
						$thm = array();
						$curthm = Aloa_Hypertopic_Wrapper::getThemeFromCommunityViewpoint('topicid', $tfilter);
						array_unshift($thm, $curthm['uri']);
					}
					
					$other =  isset($params['other_theme'])?$params['other_theme']:null;
					
					//$contrib->keywords = $this->addDocumentEntity($this->view->sqlifyParam($params['title']), $url, $thm, $other);
					//On normalise le traitement par rapport aux autres contribution : entity_id = $contrib->type.'_'.$contrib->id 
					$keywords = $this->addDocumentEntity($contrib->type.'_'.$contrib->id, $url, $thm, $other);
					
					// On ajoute l'auteur de la contribution parmi les mots-clé
					//$contrib->keywords = addKeywords($keywords, $user_session->uname);
					$contrib->keywords = $tcontrib->addKeywords($contrib->keywords, $keywords);
					
					// Update du parent_id (lien au document) et du champs keywords de la contribution courante
					//$contrib->parent_id = $doc->id;
					$contrib->save();

					
					// Demander l'envoi des mails d'invitations aux autres utilisateurs le cas échéant
					$invit = isset($params['recepient_list'])?$params['recepient_list']:null;
					if($invit != "no" && $invit != null){
						$invit = explode(";", $invit);
						foreach($invit as $uname){
							$tcontrib->invitationMail($uname, $contrib);
						}
					}
					
					// Rediriger vers la page du document fraichement créée
						$url = $this->view->url(array('action' => 'view', 'controller' => 'document', 'title' => $this->view->sqlifyParam($params['title'])),'default',true);
						$this->_redirector = $this->_helper->getHelper('Redirector');
						$front = Zend_Controller_Front::getInstance();
						$this->_redirector->setGotoUrl($front->getParam('aloa_server').$url);
					}
				}else{
					// Définir un message lorsque le titre n'est pas unique.
					$this->view->errmsg = "Un document porte déjà ce titre. Nous vous proposons d'en choisir un autre. Merci.";
				}

			}
			
			$this->view->form = $form;
			//$this->_helper->actionStack('index', 'sidebar', 'default', array('ttl' => 'Documents'));
			$this->_helper->actionStack('index', 'sidebar', 'default', array('chatid' => Aloa_Mdl_Tchat::CHAT_DOCUMENT));
			
		}else{
			//throw new Exception('Vous devez être inscrit et connecté comme utilisateur pour pouvoir déposer un document');
			throw new Aloa_Exception_Authentification('Vous devez être inscrit et connecté comme utilisateur pour pouvoir déposer un document.');
		}
	}	
	
	protected function addDocumentEntity($title, $resurl, $thm = null, $other = null){
		if(Aloa_Hypertopic_Wrapper::check()){		
				$keywords = array();
				
				// Enregistrer le document comme entité sur Argos en lui affectant les thèmes selectionné avec ajout si besoin
				//addEntity($serverUrl, $entityPath, $topics = null, $attributes = null, $resources = null, $username = null, $password = null);
				$topics = array();

				// si thm est null alors on ajoute le document au thème "Non classé"
				if($thm != null){
					
					if(in_array('other', $thm) && $other != null){
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
						$t = Aloa_Hypertopic_Wrapper::getThemeFromCommunityViewpoint('uri', $uri);
						$topics[] = $t;
						$keywords[] = $t['name'];
					}
					
				}else{
					// si thm est null alors on ajoute le document au thème "Non classé"
					$topics[] = Aloa_Hypertopic_Wrapper::getThemeFromCommunityViewpoint('name', 'Non classé');
				}
				
				
				// Il faut penser à rajouter la page perso dans le modèle de l'application par la suite si la connexion à Agorae n'a pu se faire.
				$topics[] = Aloa_Hypertopic_Wrapper::getThemeFromApplicationModel('name', 'Document');
				
				// On ajoute la page personnelle comme ressource - bof, ce serait mieux un vrai objet ressource (mais si c'est comme pour les thèmes...)
				$front = Zend_Controller_Front::getInstance();
				$resources = array(array('resourcename' => 'html', 'resourceurl' => $resurl));
				
				$argosUrl = Aloa_Hypertopic_Wrapper::getArgosUrl();
				$entityPath = '/'.$title;
				
				Aloa_Hypertopic_Wrapper::__callStatic('addEntity', array($argosUrl, $entityPath, $topics, null, $resources));
				
				// Renvoi la liste des nom de thèmes pour les keywords destinés au moteur de recherche
				return implode(", ", $keywords);
		}else{
			return null;
		}	
	}
	
}

