<?php

class HypertopicController extends Zend_Controller_Action
{

	// Penser à ajouter à la liste des controller qui ne permettent pas la redirection (bootstrap et sidebar Plugin)
	// Doubler les contrôle sécurité sur la session
	
	public function init(){
		$this->_helper->layout->disableLayout();
	}

	// Attache un thème à une entité - si le thème n'existe pas il est créé	
    public function attachtheme2entityAction()
    {
		$ent_id = $this->_getParam('entity_id', null);
		$vpt_id = $this->_getParam('vpt_id', null);
		$topicName = $this->_getParam('addtheme_'.$ent_id, null);
		$topic_id = $this->_getParam('addtheme_'.$ent_id.'_id', null);
		$parent_topic_id = $this->_getParam('parent_id', null);
		
		//$this->view->msg = $ent_id." : (".$vpt_id."/".$topicName.", ".$topic_id.")";
		
		// Gestion des droits 
		if(Zend_Session::namespaceIsset('registred')){
		
			// Si le topic ID est fourni c'est que le thème existe et on fait un simple rattachement -- Sinon, il s'agit d'un nouveau thème à créer
			if($topic_id == null){
				
				if($parent_topic_id == null){
					$vpt_uri = Aloa_Hypertopic_Wrapper::getArgosUrl()."/viewpoint/".$vpt_id."/";
					$t_uri = Aloa_Hypertopic_Wrapper::__callStatic('addTopic', array($vpt_uri, $topicName));
				}else{
					// Pour les thèmes eux-mêmes reliés à un thèmes comme c'est la cas pour les aides reçues
					$parent_uri = Aloa_Hypertopic_Wrapper::getArgosUrl()."/viewpoint/".$vpt_id."/topic/".$parent_topic_id."/";
					$t_uri = Aloa_Hypertopic_Wrapper::__callStatic('addTopicWithParentTopic', array($parent_uri, $topicName));
				}
			}else{

				$t_uri = Aloa_Hypertopic_Wrapper::getArgosUrl()."/viewpoint/".$vpt_id."/topic/".$topic_id."/";

			}

			// On attache le thème à l'entité
			$ent_uri = Aloa_Hypertopic_Wrapper::getArgosUrl()."/entity/".$ent_id;
			Aloa_Hypertopic_Wrapper::attachEntity($ent_uri, $t_uri);
			
			// On détache du topic Non classé s'il y a lieu
			$vpt_uri = Aloa_Hypertopic_Wrapper::getArgosUrl()."/viewpoint/".$vpt_id."/";
			$thm_non_classe = Aloa_Hypertopic_Wrapper::getThemeFromViewpoint($vpt_uri, 'name', "Non classé");
			
			if($thm_non_classe != null){
				$ent = Aloa_Hypertopic_Wrapper::__callStatic('getEntity', array($ent_uri));
				
				foreach($ent->topics as $look_topic){
					// Si l'entité était sous le thème Non classé alors on détache l'entité

					if($look_topic['uri'] == $thm_non_classe['uri']){
						Aloa_Hypertopic_Wrapper::detachEntity($ent_uri, $thm_non_classe);
						break;
					}
				}
			
			}
				
			 //-- Mise à jour des enregistrement DB pour les mots-clés
			$kwrds = $this->updtDBKeywords($ent_id, $topicName);

			//$this->view->msg = $ent_id." : ".$kwrds;

			// Redirection -- gestion des erreurs ??	
			
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$front = Zend_Controller_Front::getInstance();
			$cmplt = "";

			if(Zend_Session::namespaceIsset('visiteur')){
				$visitor = new Zend_Session_Namespace('visiteur');
				$cmplt = ($visitor->precedingUrl == null)?"":$visitor->precedingUrl;
			}
			
			$this->_redirector->setGotoUrl($front->getParam('aloa_server').$cmplt);
			
		}else{
			throw new Aloa_Exception_Authentification('Vous devez être inscrit et connecté pour pouvoir effectué cette opération.');
		}
		
	}	
	
	// Retire un thème d'une entité - faist le ménage si un thème n'est utilisé par personne à l'exception des thèmes par defaut
    public function detachthemefromentityAction()
    {
		$ent_id = $this->_getParam('entity_id', null);
		$vpt_id = $this->_getParam('vpt_id', null);
		$topic_id = $this->_getParam('theme_id', null);
		
		//$this->view->msg = $ent_id." : ".$vpt_id."/".$topic_id;
		
		// Gestion des droits  
		if(Zend_Session::namespaceIsset('registred')){
		
			// Détache l'entité du thème 
			$t_uri = Aloa_Hypertopic_Wrapper::getArgosUrl()."/viewpoint/".$vpt_id."/topic/".$topic_id."/";

			$ent_uri = Aloa_Hypertopic_Wrapper::getArgosUrl()."/entity/".$ent_id;
			Aloa_Hypertopic_Wrapper::detachEntity($ent_uri, $t_uri);
			
			// Si l'entité n'a plus de thèmes associé dans le community viewpoint ou le userViewpoint alors elle devient non classée -- Non applicable pour l'application Model
			$vpt_uri = Aloa_Hypertopic_Wrapper::getArgosUrl()."/viewpoint/".$vpt_id."/";
			$thm_non_classe = Aloa_Hypertopic_Wrapper::getThemeFromViewpoint($vpt_uri, 'name', "Non classé");
			
			if($thm_non_classe != null){
				
				$ent = Aloa_Hypertopic_Wrapper::__callStatic('getEntity', array($ent_uri));
				
				$is_non_classe = true;
				
				foreach($ent->topics as $look_topic){
					// Si un thème catégorisant l'entité appartient au communityViewpoint alors pas besoin de reclasser sous le thm Non classé					
					if($look_topic["viewpointid"] == $vpt_id){	

						$is_non_classe = false;
						break;
					}
				}

				if($is_non_classe){
					Aloa_Hypertopic_Wrapper::attachEntity($ent_uri, $thm_non_classe['uri']);
				}
			
			}
			
			
			// Si le thème n'a plus d'entité associée on le supprime (sauf s'il s'agit d'un des thèmes par défaut de l'application ?)
			$t = Aloa_Hypertopic_Wrapper::__callStatic('getTopic', array($t_uri));
			
			//-- Mise à jour des enregistrement DB pour les mots-clés
			$kwrds = $this->updtDBKeywords($ent_id, $t->topicName, 'rmv');
			
			if(count($t->entities) <= 0){
				Aloa_Hypertopic_Wrapper::__callStatic('deleteTopic', array($t_uri));
			}
			
			//$this->view->msg = $ent_id." : ".$kwrds;
			
			
			// Redirection -- gestion des erreurs ??
			
				$this->_redirector = $this->_helper->getHelper('Redirector');
				$front = Zend_Controller_Front::getInstance();
				$cmplt = "";

				if(Zend_Session::namespaceIsset('visiteur')){
					$visitor = new Zend_Session_Namespace('visiteur');
					$cmplt = ($visitor->precedingUrl == null)?"":$visitor->precedingUrl;
				}
				$this->_redirector->setGotoUrl($front->getParam('aloa_server').$cmplt);
			
		}else{
			throw new Aloa_Exception_Authentification('Vous devez être inscrit et connecté pour pouvoir effectué cette opération.');
		}
	}	
	
	protected function updtDBKeywords($ent_id, $kw, $mod = 'add'){
		 
		 $is_user = false;
		 
		 // Cela, c'est une mauvaise ruse pour savoir s'il s'agit d'un utilisateur ou d'une contribution... à revoir
		 $thm_pgperso_uri = Aloa_Hypertopic_Wrapper::getThemeFromApplicationModel('name', "Page personnelle");
		 $thm_pgperso = Aloa_Hypertopic_Wrapper::__callStatic('getTopic', array($thm_pgperso_uri['uri']));
		 
		 foreach($thm_pgperso->entities as $ent){
			if($ent['entitypath'] == $ent_id){
				$is_user = true;
				break;
			}
		 }
		 
		 if($is_user){
				
				$tuser = new Aloa_Mdl_Tuser();
				$select = $tuser->select();
				$select->from($tuser, array('id', 'keywords'))->where('uname = ?', $ent_id);
				$user = $tuser->fetchRow($select);
				
				if($mod == 'add'){
					$user->keywords = $tuser->addKeywords($user->keywords, $kw);
				}else{
					$user->keywords = $tuser->rmvKeywords($user->keywords, $kw);
				}
				
				$user->save();
				
				// On s'astraint à mettre à jour la contribution associée à l'inscription de l'utilisateur afin que les mots clés puissent être utilisées par le moteur de recherche...
				if($user != null){
					$tcontrib = new Aloa_Mdl_Tcontribution();
					$select = $tcontrib->select();
					$select->where('type = ?', Aloa_Mdl_Tcontribution::NEWMEMBER)->where('author_id = ?', $user->id);
					$contrib = $tcontrib->fetchRow($select);
					
					if($mod == 'add'){
						$contrib->keywords = $tcontrib->addKeywords($contrib->keywords, $kw);
					}else{
						$contrib->keywords = $tcontrib->rmvKeywords($contrib->keywords, $kw);
					}
					
					$contrib->save();
					
				}
		 
		 }else{
			// C'est une contribution
			list($contrib_type, $contrib_id) = explode('_', $ent_id);
			$tcontrib = new Aloa_Mdl_Tcontribution();
			$contrib = $tcontrib->find($contrib_id)->current();
			
			if($mod == 'add'){
				$contrib->keywords = $tcontrib->addKeywords($contrib->keywords, $kw);
			}else{
				$contrib->keywords = $tcontrib->rmvKeywords($contrib->keywords, $kw);
			}
			
			$contrib->save();
		 }	
		return $contrib->keywords;
	}
	
	// Marquer une contribution comme "Conseils pratiques" 
	public function addtoadvicesAction(){
	
		//$this->_helper->layout->disableLayout();
		
		$contribid = $this->_getParam('cid', null);
	
		if($contribid != null){
			if(Zend_Session::namespaceIsset('registred')){
				
				$user_session = new Zend_Session_Namespace('registred');

				$tcontrib = new Aloa_Mdl_Tcontribution();
				$contrib = $tcontrib->find($contribid)->current();
				
				if($contrib != null){
				
					$u_vpt = $user_session->viewpoint;
					$u_adv_t = Aloa_Hypertopic_Wrapper::getThemeFromViewpoint($u_vpt, 'name', 'Conseils pratiques');
					
					if($u_adv_t == null){
						// Si l'utilisateur n'a pas le thème conseil pratique dans son point de vue on le lui ajoute
						Aloa_Hypertopic_Wrapper::__callStatic('addTopic', array($u_vpt, 'Conseils pratiques'));
					}
					
					// Le thème 'Conseils pratiques' du community viewpoint 
					$adv_t = Aloa_Hypertopic_Wrapper::getThemeFromCommunityViewpoint('name', 'Conseils pratiques');
					
					// En théorie l'entité existe toujours dans l'application model puisqu'elle est créée au moment de l'insertion de la contribution dans la DB
					$ent_url = Aloa_Hypertopic_Wrapper::getArgosUrl()."/entity/".$contrib->type.'_'.$contrib->id;
					
					// On vérifie que l'utilisateur n'a pas déjà cette entité sous le thème dans son point de vue
					$ent = Aloa_Hypertopic_Wrapper::__callStatic('getEntity', array($ent_url));

					
					if($ent != false){
					
						$thm = array();
						

						// Contrôles sur les thèmes auxquels l'entité est déjà attachée
						$ent_thm = array();
						foreach($ent->topics as $t){
							$ent_thm[] = $t['uri'];
						}
						
						// Si non référencé sous le thm dans le communityViewpoint
						if(!in_array($adv_t['uri'], $ent_thm)){
							$thm[] = $adv_t['uri'];
						}
						
						// Si non référencé sous le thm dans le userViewpoint
						if(!in_array($u_adv_t['uri'], $ent_thm)){
							$thm[] = $u_adv_t['uri'];
							// alors on incrémentera dans db --> En fait il serait beaucoup plus propre de compter le nb de fois que le thème "Conseils Pratiques" apparaît dans l'entité...
							$contrib->nbUp += 1;
							$contrib->save();
							
							$this->view->msg = "ok:La contribution vient d'être ajoutée avec succès aux conseils pratique de la communautés. 
							Merci de votre participation vous pourrez la retrouver dans la rubrique <a href='".$this->view->url(array('controller' => 'communaute', 'action' => 'index'),'default',true)."#advices'>Conseils Pratiques.</a>";
							
						}else{
						
							$this->view->msg = "error:Vous avez déjà marqué cette contribution comme conseil pratique.";
						
						}
						
						if(count($thm) > 0){

							$res = Aloa_Hypertopic_Wrapper::attachEntity($ent_url, $thm);
						}

					}else{
					
						$this->view->msg = 'error:Entité inconnue.';
					
					}
					

				
				}else{
					//throw new Zend_Exception('Contribution inconnue.');
					$this->view->msg = 'error:Contribution inconnue.';
				}
	 
			}else{
				//throw new Aloa_Exception_Authentification('Vous devez être inscrit et connecté comme utilisateur pour pouvoir marquer une contribution comme conseil pratique.');
				$this->view->msg = 'error:Vous devez être inscrit et connecté comme utilisateur pour pouvoir marquer une contribution comme conseil pratique.';
			}
		}else{
			//throw new Zend_Exception('Paramètres incorrects.');
			$this->view->msg = 'error:Paramètres incorrects.';
		}
	
	}
	
}