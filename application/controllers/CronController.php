<?php

class CronController extends Zend_Controller_Action
{

    public function init(){
		
		if($_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']){
		
			$this->_helper->layout->disableLayout();
		
		}else{
			throw new Zend_Exception('Une tentative d\'accès non conforme a été détectée.');
		}
		
    }
	
	public function indexAction(){
	
		$this->view->msg = fileatime("./sitemap.txt");
	
	}
	
	public function updtsitemapAction(){
	
		$lastmdf = fileatime("./sitemap.txt");
		
		// si la dernière modification date de moins de 24h alors on répond à l'appel du CRON et on lance la requête à Google pour la mise à jour (sinon c'est qu'il n'y a rien de nouveau depuis la dernière fois)
		// On paramètre le CRON pour qu'il ne s'execute qu'une fois toutes les 24h
		// http://www.google.com/support/webmasters/bin/answer.py?answer=156184&hl=fr
		
		if($lastmdf != false){
			$front = Zend_Controller_Front::getInstance();
			$domain = $front->getParam('aloa_server');
			
			
			$url = "http://www.google.com/webmasters/tools/ping?sitemap=".urlencode($domain."/sitemap.txt");
			
			if($lastmdf - time() <= 60*60*24){
				$c = curl_init();
				curl_setopt($c, CURLOPT_URL, $url);
				curl_setopt($c, CURLOPT_HEADER, 1); // get the header
				curl_setopt($c, CURLOPT_NOBODY, 1); // and *only* get the header
				curl_setopt($c, CURLOPT_RETURNTRANSFER, 1); // get the response as a string from curl_exec(), rather than echoing it
				curl_setopt($c, CURLOPT_FRESH_CONNECT, 1); // don't use a cached version of the url
				
				
				if(!curl_exec($c)){ 
					$this->view->msg = "no"; 
				}

				$httpcode = curl_getinfo($c, CURLINFO_HTTP_CODE);
				$this->view->msg = $url." : ".$httpcode;
				
			}else{
				$this->view->msg = "No update needed.";
			}
		}else{
			$this->view->msg = "Not found.";
		}
	}
	
	
	// Digest
	public function digestAction(){
	
	}	

	public function weekdigestAction(){
	
	}
	
	public function biweekdigestAction(){
	
	}
	
	public function viewdigestAction(){
	
	}
	
	protected function makeDigest(){
	
	}
	
}

