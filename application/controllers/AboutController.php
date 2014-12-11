<?php

class AboutController extends Zend_Controller_Action
{

    public function indexAction()
    {
		$front = Zend_Controller_Front::getInstance();
		// Pour redéfinir le titre et description depuis un controleur
		$this->view->headTitle()->offsetSet(0, ":: ".$front->getParam('aloa_site_name')." - Contacts et informations");
		//$this->view->headMeta()->offsetSetName(1, 'description', "Aloa est un espace");
	
		$this->_helper->actionStack('index', 'sidebar', 'default', array('chatid' => Aloa_Mdl_Tchat::CHAT_ABOUT));
		
    }
	
	public function cnilAction(){
	
		$front = Zend_Controller_Front::getInstance();
		// Pour redéfinir le titre et description depuis un controleur
		$this->view->headTitle()->offsetSet(0, ":: ".$front->getParam('aloa_site_name')." - Confidentialité et informations personnelles");
	
		$this->_helper->actionStack('hide', 'sidebar', 'default');
	}

}

