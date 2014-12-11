<?php

class GiftController extends Zend_Controller_Action
{

    public function indexAction()
    {

		$contrib = $this->_getParam('contrib', null);
		$content = $this->_getParam('content', null);
	
		$this->_helper->layout->disableLayout();
		
		if(Zend_Session::namespaceIsset('registred')){
			
			$user_session = new Zend_Session_Namespace('registred');
			
			if($contrib != null){
				
				$tcontrib = new Aloa_Mdl_Tcontribution();
				$rel_contrib = $tcontrib->find($contrib)->current();
				$dest = $rel_contrib->findParentAloa_Mdl_Tuser();
			
				$tgift = new Aloa_Mdl_Tgift();
				$gift = $tgift->createRow();
				
				$gift->dateTime = time();
				$gift->from_id = $user_session->id;
				$gift->to_id = $dest->id;
				$gift->message = $content;
				$gift->contrib_id = $rel_contrib->id;
				$gift->save();
				
				// URL resolver
				switch($rel_contrib->type){
					case Aloa_Mdl_Tcontribution::NEWS :
					case Aloa_Mdl_Tcontribution::PUBLICNEWS :
						$relatedUrl = $this->view->url(array('controller'=> 'communaute', 'action' => 'pageperso', 'uname' => $dest->uname),'default',true)."#".$rel_contrib->id;
						break;
					case Aloa_Mdl_Tcontribution::DOCUMENT :
						$relatedUrl = $this->view->url(array('controller'=> 'document', 'action' => 'view', 'title' => $this->view->sqlifyParam($rel_contrib->title)),'default',true);
						break;
					case Aloa_Mdl_Tcontribution::QUESTION :
						$relatedUrl = $this->view->url(array('controller'=> 'question', 'action' => 'view', 'title' => $this->view->sqlifyParam($rel_contrib->title)),'default',true);
						break;
					case Aloa_Mdl_Tcontribution::ANSWER :
						$question = $rel_contrib->findParentAloa_Mdl_Tcontribution();
						$relatedUrl = $this->view->url(array('controller'=> 'question', 'action' => 'view', 'title' => $this->view->sqlifyParam($question->title)),'default',true)."#".$rel_contrib->id;
						break;
					case Aloa_Mdl_Tcontribution::USERSTORY :
						$storytheme = $rel_contrib->findParentAloa_Mdl_Tcontribution();
						$relatedUrl = $this->view->url(array('controller'=> 'question', 'action' => 'view', 'storytheme' => $this->view->sqlifyParam($storytheme->title), 'userstory' => $this->view->sqlifyParam($rel_contrib->title)),'default',true);
						break;
					case Aloa_Mdl_Tcontribution::REACTION :
						$storytheme = $rel_contrib->findParentAloa_Mdl_Tcontribution();
						$userstory = $storytheme->findParentAloa_Mdl_Tcontribution();
						$relatedUrl = $this->view->url(array('controller'=> 'question', 'action' => 'view', 'storytheme' => $this->view->sqlifyParam($storytheme->title), 'userstory' => $this->view->sqlifyParam($userstory->title)),'default',true)."#".$rel_contrib->id;
						break;
						
					default:
						$relatedUrl = $this->view->url(array('controller'=> 'index'),'default',true);
				}
				
				$relatedUrl = Zend_Controller_Front::getInstance()->getParam('aloa_server').$relatedUrl;
				
				// Envoi du mail
				$tgift->giftMail($dest, $content, $relatedUrl);
				
				$this->view->msg = "ok:success";

			}else{
				$this->view->msg = "error:Paramètres incorrects.";
			}
			
		}else{
			$this->view->msg = "error:Action non autorisée.";
		}
		
		
		
	
	}
	
	
	
	
}

?>