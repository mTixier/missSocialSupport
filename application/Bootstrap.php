<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

	protected function _initAutoload(){
        $autoloader = new Zend_Application_Module_Autoloader(array(
            'namespace' => 'Default_',
            'basePath'  => dirname(__FILE__),
        ));

		//Plugin loader
		$lib = realpath(APPLICATION_PATH . '/../library');
		$plgloader = Zend_Loader_Autoloader::getInstance();
		$plgloader->registerNamespace('Aloa_');
		$plgloader->registerNamespace('Fr_Zhou_');
				
		// Pour permettre l'autoload des models (on peut ajouter d'autres aspect aussi)
		$resourceLoader = new Zend_Loader_Autoloader_Resource(array(
		'basePath'  => APPLICATION_PATH,
		'namespace' => 'Aloa',
		));
		$resourceLoader->addResourceType('model', 'models/', 'Mdl');
		
        return $autoloader;	
	}

	protected function _initDatabase(){
		$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/db.ini', APPLICATION_ENV);
		$db= Zend_Db::factory($config);
		Zend_Db_Table_Abstract::setDefaultAdapter($db);	
	}
		
	protected function _initSession(){
		
		$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/session.ini', APPLICATION_ENV);
		Zend_Session::setOptions($config->toArray());
		
		Zend_Session::start();
		$visitor = new Zend_Session_Namespace('visiteur');
		// Un espace nom de session sans valeurs affectées est détruit automatiquement
		$visitor->intialized = 1;
		
		// à revoir... - Attention toutes les actions fonctionnant par requêtes asynchrones doivent être ajoutée pour empêcher que l'on revienne dessus suite à une opération d'authentification.
		// Il semblerait qu'également les accès à des ressources comme les images soient à disqualifier...
		$visitor->precedingUrl = (isset($visitor->lastUrl)&& preg_match('/css|js|images|upload|hypertopic|error|login|listuser|notification|gift|update|chat|sidebar/', $visitor->lastUrl) == 0)?$visitor->lastUrl:$visitor->precedingUrl;
		
		$visitor->lastUrl = $_SERVER['REQUEST_URI'];
		
		if(Zend_Session::namespaceIsset('registred')){
			$user_session = new Zend_Session_Namespace('registred');
			$tuser = new Aloa_Mdl_Tuser();
			// Mise à jour de la date de la dernière visite
			$user = $tuser->find($user_session->id)->current();
			$user->lastVisit = time();
			$user->save();
		}
		
	}

	protected function _initAloaConfig(){
	
		$front = Zend_Controller_Front::getInstance();
		
		// Active le plugin de gestion (mémorisation de l'état) de la sidebar
		$front->registerPlugin(new Aloa_Controller_Plugin_Sidebar());
		
		$front->setParam('aloa_server', 'http://'.$_SERVER['HTTP_HOST']);
		$front->setParam('aloa_mail_pro', 'mail@mail.fr');
		$front->setParam('aloa_mail_admin', 'mail@mail.fr');
		$front->setParam('aloa_send_mail', 'mail@mail.fr');
		$front->setParam('aloa_site_name', 'Miss');
		
	}
	
	protected function _initMetaData(){
	    
		$front = Zend_Controller_Front::getInstance();
		
		$this->bootstrap('view');
        $view = $this->getResource('view');
        
		$view->doctype('XHTML1_STRICT');

		$view->headTitle(":: ".$front->getParam('aloa_site_name'));
		
		$view->headMeta()->appendName('keywords', "keyword1, keyword2");
		$view->headMeta()->appendName('description', $front->getParam('aloa_site_name')." est un espace d'échange de conseils, d'information et de témoignages.");
		// Pour redéfinir la description depuis un controleur
		//$this->view->headMeta()->offsetSetName(1, 'description', "Site est un espace");
		

		
	}
	
	protected function _initLog(){	
	
		$log = new Zend_Log();
		$log->addPriority('TRACE', 8);
		
		$fileWriter = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/log/trace_'.date('my').'.log');
		
		$frmtr = new Zend_Log_Formatter_Xml('logEntry',
                                        array('timestamp' => 'timestamp',
												'author' => 'author',
												'msg' => 'message',
												'request' => 'request',
                                              'priority' => 'priority',
                                              'priorityName' => 'priorityName'));
		$fileWriter->setFormatter($frmtr);
		
		
		$author = "Visiteur_".(isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:"Unknown");
		if(Zend_Session::namespaceIsset('reg')){
			$user_session = new Zend_Session_Namespace('reg');
			$author = $user_session->uname;
		}
		
		$log->setEventItem('author', $author);
		$log->setEventItem('request', $_SERVER['REQUEST_URI']);
		
		$log->addWriter($fileWriter);
		
		if(!preg_match('/css|js|images|chat|sidebar/', $_SERVER['REQUEST_URI'])){
			$log->log('Log des actions', 8);
		}
	
	}
	
	protected function _initLocale(){
		// Choix de la locale - ici, par défaut en provenance du navigateur ou du host
		
		setlocale(LC_ALL, 'fr_FR.UTF-8');
		$locale = new Zend_Locale();
		
		$trslt = new Zend_Translate('csv', APPLICATION_PATH . '/languages/fr.csv', 'fr');
		$trslt->addTranslation(APPLICATION_PATH . '/languages/en.csv', 'en');
		
		$trslt->setLocale($locale);
		
		Zend_Validate_Abstract::setDefaultTranslator($trslt);
		Zend_Form::setDefaultTranslator($trslt);
		
	}
	

	
	protected function _initHypertopic(){
	
		Aloa_Hypertopic_Wrapper::setDefaultValues('http://argosserver.org', 'login', 'pwd');

		try{
			// définir l'url du point de vue "modèle de l'application"
			
			Aloa_Hypertopic_Wrapper::setApplicationModel('http://argosserver.org/viewpoint/id');
			Aloa_Hypertopic_Wrapper::setCommunityViewpoint('http://argosserver.org/viewpoint/96/');
			Aloa_Hypertopic_Wrapper::setUsersRootTheme('http://argosserver.org/viewpoint/id/topic/id/');
	
		}catch(Zend_Http_Client_Adapter_Exception $e){
			Aloa_Hypertopic_Wrapper::setOk(false);
		}
		

		
	}
	
	protected function _initMail(){
	
		//$config = array('auth' => 'login', 'username' => 'unm', 'password' => 'pwd', 'port' => 421);
		//'ssl' => 'tls', 
		//$tr = new Zend_Mail_Transport_Smtp('smtp.server.com', $config);
		//Zend_Mail::setDefaultTransport($tr);
	}
	
	protected function _initSearchEngine(){
	
		$front = Zend_Controller_Front::getInstance();
		// Chemin vers l'index du moteur de recherche
		$findex = APPLICATION_PATH . '/lucene/aloa_index';
		if(file_exists($findex)){
			$index = Zend_Search_Lucene::open($findex);
		}else{
			$index = Zend_Search_Lucene::create($findex);
		}
		
		$analyzer = new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8_CaseInsensitive();
		
		$stpwrdsFilter = new Zend_Search_Lucene_Analysis_TokenFilter_StopWords();
		$stpwrdsFilter->loadFromFile(APPLICATION_PATH . '/languages/stopwrd_fr.txt');
		
		$analyzer->addFilter($stpwrdsFilter);
		Zend_Search_Lucene_Analysis_Analyzer::setDefault($analyzer);
		
		$front->setParam('aloa_search_index', $index);
	}

}

