<?php

class IndexController extends Zend_Controller_Action
{

    public function indexAction()
    {
		// Module affichés en page d'accueil
		$tcontrib = new Aloa_Mdl_Tcontribution();
		$lastcontribs = $tcontrib->getLastContributions(array(Aloa_Mdl_Tcontribution::PUBLICNEWS, Aloa_Mdl_Tcontribution::NEWMEMBER) , 3);
		$this->view->actu = $lastcontribs;
		
		$tuser = new Aloa_Mdl_Tuser();
		$this->view->lastusers = $tuser->getLastUsers(8);
		
		// Contrôle le nb de conseils pratiques pour qu'il y en ait un minimum avant de les mettre en avant
		if(Aloa_Hypertopic_Wrapper::check()){
			$adv_t = Aloa_Hypertopic_Wrapper::getThemeFromCommunityViewpoint('name', 'Conseils pratiques');
			$adv_t_obj = Aloa_Hypertopic_Wrapper::__callStatic("getTopic", array($adv_t['uri']));
			$this->view->print_advices = (count($adv_t_obj->entities) > 5)?true:null;
		}else{
			$this->view->print_advices = null;
		}
		
		$this->_helper->actionStack('homepage', 'story', 'default');
		$this->_helper->actionStack('homepage', 'question', 'default');
		$this->_helper->actionStack('homepage', 'document', 'default');
		
		//$this->_helper->actionStack('index', 'sidebar', 'default', array('ttl' => 'Accueil'));
		$this->_helper->actionStack('index', 'sidebar', 'default', array('chatid' => Aloa_Mdl_Tchat::CHAT_HOME));
		
    }

}

