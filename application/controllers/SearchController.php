<?php

class SearchController extends Zend_Controller_Action
{

	public function indexAction(){
		$rqt = $this->_getParam('rqt', null);
		
		if($rqt != null && $rqt != ''){
			$front = Zend_Controller_Front::getInstance();
			$index = $front->getParam('aloa_search_index');
			
			// A voir si on le garde...
			Zend_Search_Lucene_Search_QueryParser::dontSuppressQueryParsingExceptions();
			
			try{
				$query = Zend_Search_Lucene_Search_QueryParser::parse($rqt, "UTF-8");
			
				$hits = $index->find($query);
				// Franchement c'est bien la peine d'utiliser un moteur de recherche...
				$ids = array();
				$prefix = Aloa_Mdl_Tcontribution::SEARCH_ENGINE_PREFIX;
				foreach($hits as $hit){
					$fld = $prefix.'id';
					$ids[] = $hit->$fld;
				}
				
				$tcontrib = new Aloa_Mdl_Tcontribution();
				$contribs = $tcontrib->find($ids);
				
			}catch(Zend_Search_Lucene_Search_QueryParserException $e){
				$contribs = array();
				$this->view->errmsg = $e->getMessage();
			}
			//$this->view->hits = $hits;
			
			$this->view->request = $rqt;
			$this->view->results = $contribs;
			$this->_helper->actionStack('index', 'sidebar', 'default');
		}
	
	}
	
	public function indexfrombaseAction(){
		// Sécurisé pour réserver à admin 
		if(Zend_Session::namespaceIsset('registred')){
			
			$user_session = new Zend_Session_Namespace('registred');
			if($user_session->rights == 'admin'){
				// Détruit le fichier d'index précédent
				$front = Zend_Controller_Front::getInstance();
				$index = $front->getParam('aloa_search_index');
				$findex = $index->getDirectory();	
				$index = Zend_Search_Lucene::create($findex);
				
				// Le rempli avec tout ce qui figure dans la table contribution
				$tcontrib = new Aloa_Mdl_Tcontribution();
				$select = $tcontrib->select();
				$res = $tcontrib->fetchAll($select);
				
				$this->view->nb = count($res);
				
				foreach($res as $contrib){
				
					$doc = $tcontrib->createIndexedDocument($contrib->toArray());
					$index->addDocument($doc);
				}
				
				$index->commit();  
				$index->optimize(); 
			
			}else{
				$this->view->errmsg = "Vous n'avez pas les droits suffisants pour effectuer cette action.";
			}
		}
	}

}