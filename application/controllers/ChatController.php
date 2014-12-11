<?php

class ChatController extends Zend_Controller_Action
{

	public function init(){

		$this->_helper->layout->disableLayout();
	
	}

    public function indexAction()
    {
		$id = $this->_getParam('chatid', null);
				
		if(Zend_Session::namespaceIsset('registred')){
			
			if($id != null){
				$tutt = new Aloa_Mdl_Tutterance();
				$select = $tutt->select();
				$select->where('chat_id = ?', $id)->order('dateTime DESC')->limit(10);
				$utts = $tutt->fetchAll($select);
				
				if(count($utts) > 0){
					$msg = "";
					
					$flag = true;
					
					foreach($utts as $ut){
						// contenu de l'ordre des lignes la plus récente est la première
						if($flag){
							$lastdt = $ut->dateTime;
							$flag = false;
						}
						
						$author = $ut->findParentAloa_Mdl_Tuser();
						$avatar = $author->findParentAloa_Mdl_Tavatar();
						//$msg.= $this->view->humanDate($ut->dateTime)."|".$author->uname."|/images/avatar/mid_".$avatar->file."|".$ut->content."#";
						$msg.= "le ".date("d/m/Y à H\hi" ,$ut->dateTime)."|".$author->uname."|/images/avatar/mid_".$avatar->file."|".$ut->content."#";
						
					}
					$this->view->msg = "ok-".$lastdt."&".$msg;
				
				}else{
					$this->view->msg = "message&Personne n'a encore participé à ce bavardage... Ouvrez la dicussion en envoyant un premier message : ";
				}
				
			}else{
				$this->view->msg = 'error&Paramètres incorrects.';
			}
			
		}
	}	
	
	public function updateAction(){
		
		$id = $this->_getParam('chatid', null);
		$lastdt = $this->_getParam('lastdt', time());

	
		if(Zend_Session::namespaceIsset('registred')){
		
			if($id != null){
				$tutt = new Aloa_Mdl_Tutterance();
				$select = $tutt->select();
				$select->where('chat_id = ?', $id)->where('dateTime > ?', $lastdt)->order('dateTime DESC')->limit(10);
				$utts = $tutt->fetchAll($select);
			
				$msg = "";
				if(count($utts) > 0){
					
					$flag = true;
					
					foreach($utts as $ut){
					
						if($flag){
							$lastdt = $ut->dateTime;
							$flag = false;
						}
					
						$author = $ut->findParentAloa_Mdl_Tuser();
						$avatar = $author->findParentAloa_Mdl_Tavatar();
						
						//$msg.= $this->view->humanDate($ut->dateTime)."|".$author->uname."|/images/avatar/mid_".$avatar->file."|".$ut->content."#";
						$msg.= "le ".date("d/m/Y à H\hi" ,$ut->dateTime)."|".$author->uname."|/images/avatar/mid_".$avatar->file."|".$ut->content."#";
						
						
					}
					
					//$msg = rtrim($msg, "#");
			
				}
					$this->view->msg = "ok-".$lastdt."&".$msg;
			}else{
				$this->view->msg = 'error&Paramètres incorrects.';
			}
		}
	}
	
	public function appendAction(){
		$id = $this->_getParam('chatid', null);
		$uname = $this->_getParam('uname', null);
		$content = $this->_getParam('content', null);

		// Pour éviter que des caractères utilisé dans les échanges soit exploités dans les messages
		$content = str_replace(array('#', '|', '&'), "?", $content);
		
		if(Zend_Session::namespaceIsset('registred')){
			
			$user_session = new Zend_Session_Namespace('registred');

			if($uname == $user_session->uname){
				
				$tutt = new Aloa_Mdl_Tutterance();
				$ut = $tutt->createRow();
				$ut->dateTime = time();
				$ut->author_id = $user_session->id;
				$ut->chat_id = $id;
				$ut->content = $content;
				
				$ut->save();
				
				
			}else{
				throw new Zend_Exception('Alerte de sécurité.');
			}

		}
		
	
	}
	
}