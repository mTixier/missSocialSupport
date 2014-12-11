<?php

class Aloa_Controller_Plugin_Sidebar extends Zend_Controller_Plugin_Abstract{

	const SIDEBAR_DEFAULT = 'default';
	const SIDEBAR_HIDDEN = 'hidden';
	const SIDEBAR_MINIMIZED = 'minimize';
	const SIDEBAR_MAXIMIZED = 'maximize';

	private $_enabled = true;
	
	private $_state;

	public function __construct(){
	
        $front = Zend_Controller_Front::getInstance();
        if (!$front->hasPlugin('Zend_Controller_Plugin_ActionStack')) {
            /**
             * @see Zend_Controller_Plugin_ActionStack
             */
            require_once 'Zend/Controller/Plugin/ActionStack.php';
            $this->_actionStack = new Zend_Controller_Plugin_ActionStack();
            $front->registerPlugin($this->_actionStack, 97);
			
        } else {
            $this->_actionStack = $front->getPlugin('Zend_Controller_Plugin_ActionStack');
        }
	
		if(Zend_Session::namespaceIsset('visiteur')){
			$visiteur = new Zend_Session_Namespace('visiteur');
			
			if(!isset($visiteur->sidebarState)){
				$visiteur->sidebarState = self::SIDEBAR_DEFAULT;
			}
			
			$this->_state = $visiteur->sidebarState;
			
		}
	
	}
	
	public function isEnabled(){
		return $this->_enabled;
	}
	
	public function disable(){
		$this->_enabled = false;
		return $this->isEnabled();
	}

	public function enable(){
		$this->_enabled = true;
		return $this->isEnabled();
	}
	
    public function pushStack(Zend_Controller_Request_Abstract $next)
    {
        $this->_actionStack->pushStack($next);
        return $this;
    }
	
	public function dispatchLoopStartup(Zend_Controller_Request_Abstract $rqt){
	
		// filtre sur les controlleur
		$ctrlr = $rqt->getControllerName();
		$action = $rqt->getActionName();
		// Il faudrait voir la possibilité mais il serait plus propre de désactiver automatiquement dès lors que la vue ou le layout sont eux-même désactivés...
		if(in_array($ctrlr, array('update', 'chat', 'inscription', 'login', 'gift', 'notification','hypertopic', 'cron')) || in_array($action, array('listuser'))){
			$this->disable();
		}
	
		if($this->isEnabled()){
			
			switch($this->_state){
				case self::SIDEBAR_HIDDEN:
					$action = 'hide';
					break;				
				case self::SIDEBAR_MINIMIZED:
					$action = 'minimize';
					break;				
				case self::SIDEBAR_MAXIMIZED:
					$action = 'maximize';
					break;
				default:
					$action = 'default';
					break;
			
			}
			
			if($action != null){
				require_once 'Zend/Controller/Request/Simple.php';
				$newRequest = new Zend_Controller_Request_Simple($action, 'sidebar', 'default', array());
				$this->pushStack($newRequest);
			}
			
			return $this->_state;

		}
	
	}

}

?>