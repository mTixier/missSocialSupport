<?php

class ErrorController extends Zend_Controller_Action
{

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');

			switch ($errors->type) { 
			    case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
			    case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:

			        // 404 error -- controller or action not found -- IE fait des redirections automatique vers un moteur de recherche...
			        //$this->getResponse()->setHttpResponseCode(404);
			        $this->view->message = 'Page not found';
			        break;
					
				case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER :
					if($errors->exception instanceof Aloa_Exception_Authentification){
						$this->view->exception = $errors->exception;
						$form = new Aloa_Form_Login();
						$form->setMethod('post');
						$form->setAction($this->view->url(array('action' => 'index', 'controller' => 'login'), false, 'default'));
						$this->view->form = $form;
						
						$this->_helper->actionStack('hide', 'sidebar', 'default');
						$this->render('authentification', 'default', false);
						
						break;
					}
				
			    default:
			        // application error 
			        //$this->getResponse()->setHttpResponseCode(500);
			        $this->view->message = 'Application error '.$errors->type;
			        break;
			}
		//print_r($errors);
		$this->view->exception = $errors->exception;
		$this->view->request   = $errors->request;
    }

	public function todoAction(){
		$this->_helper->actionStack('hide', 'sidebar', 'default');
	}
	
}

