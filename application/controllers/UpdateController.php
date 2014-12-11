<?php

class UpdateController extends Zend_Controller_Action
{

    public function indexAction()
    {
		$this->_helper->layout->disableLayout();
		
		$id = $this->_getParam('id', null);
		$field = $this->_getParam('field', null);
		$content = $this->_getParam('content', null);
		
		// Contrainte sur les formats - On pourrait faire mieux...
		$format = array('zip' => 9, 'city' => 49, 'tagLine' => 140, 'title' => 200);
		
		if(Zend_Session::namespaceIsset('registred')){
			
			$user_session = new Zend_Session_Namespace('registred');
			
			switch($field){
			// Cas pour les paramètres utilisateurs
			case 'tagLine':
			case 'city':
			case 'zip':
				if($user_session->id == $id || $user_session->rights == 'admin'){
					
					$tuser = new Aloa_Mdl_Tuser();
					$row = $tuser->find($user_session->id)->current();
					
					// Formatage/Contrôle sur la valeur du champs - zip (10), city (50), tagline (140)
					$value = (array_key_exists($field, $format))?substr($content, 0, $format[$field]):$content;
					
					$row->$field = $value;
					$row->save();
					
					// Mise à jour de la session
					$user_session->$field = $value;
					
					$msg = "ok:".$value;
			
				}else{
				
					$msg = "error:Action non autorisée.";
				
				}
				break;
				
			// Cas pour les contributions
			case 'title':
			case 'content':
				$tcontrib = new Aloa_Mdl_Tcontribution();
				$contrib = $tcontrib->find($id)->current();
			
				if($user_session->id == $contrib->author_id || $user_session->rights == 'admin'){
				
					// Formatage/Contrôle sur la valeur du champs -  title (200)
					$value = (array_key_exists($field, $format))?substr($content, 0, $format[$field]):$content;			
					
					$contrib->$field = $value;
					$row->lastEdit = time();
					$contrib->save();
					
					$msg = "ok:".$value;
			
				}else{
				
					$msg = "error:Action non autorisée.";
				
				}
				break;
				
			default:
				$msg = "error:Paramètres incorrects.";
				break;
			}
		
			$this->view->msg = $msg;
		}else{
			$this->view->msg = "error:Action non autorisée.";
		}
	}	
	
	// Marquer une contribution comme "Conseils pratiques"  -- Useless, le controller a été migrer sous le HypertopicController par souci de cohérence.
	/*
	public function addtoadvicesAction(){
	
		$this->_helper->layout->disableLayout();
		
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
	*/
	
}