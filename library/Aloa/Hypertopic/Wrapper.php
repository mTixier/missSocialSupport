<?php
abstract class Aloa_Hypertopic_Wrapper{

	protected static $_login = null;
	protected static $_mdp = null;
	protected static $_argosUrl = null;

	protected static $_isOk = true;
	
	// Le modèle de l(application - Tyopologie des types de contenus proposés
	protected static $_applicationModel = null;
	// Point de vue consensuel établi par les administrateur et partagé par la communauté
	protected static $_communityViewpoint = null;
	// Le thèmes racine des thèmes utilisé pour tagger les utilisateurs - en l'occurrence ici, les aides et services à la personne
	protected static $_usersRootTheme = null;
	
	protected static $dict = array('Fr_Zhou_Managers_Viewpoint',
							'Fr_Zhou_Managers_Viewpoints',
							'Fr_Zhou_Managers_Topic',
							'Fr_Zhou_Managers_Entity',
							'Fr_Zhou_Managers_Resource',
							'Fr_Zhou_Managers_Attribute',
							'Fr_Zhou_Managers_Attributes',
							'Fr_Zhou_Managers_Value',
							'Fr_Zhou_Managers_Actor',
							'Fr_Zhou_Managers_Actors'
						);
						
	protected static $_appMdl_type_translation = array(
						"newmember" => 'Page personnelle',
						"news" => 'Nouvelle',
						"publicnews" => 'Nouvelle publique',
						"document" => 'Document',
						"question" => 'Question',
						"answer" => 'Réponse',
						"storytheme" => 'Sujet de témoignage',
						"userstory" => 'Témoignage',
						"reaction" => 'Réaction',
						"chat" => 'Bavardage'
						);


	public static function setDefaultValues($p_server, $p_login, $p_mdp){
		self::$_login = $p_login;
		self::$_mdp = $p_mdp;
		self::$_argosUrl = $p_server;
	}

	public static function getArgosUrl(){
		return self::$_argosUrl;
	}
	
	public static function setApplicationModel($uri){
		self::$_applicationModel = self::__callStatic('getViewpoint', array($uri));
		return self::$_applicationModel;
	}

	public static function setCommunityViewpoint($uri){
		self::$_communityViewpoint = self::__callStatic('getViewpoint', array($uri));
		return self::$_communityViewpoint;
	}

	public static function setUsersRootTheme($uri){
		self::$_usersRootTheme = self::__callStatic('getTopic', array($uri));
		return self::$_usersRootTheme;
	}
	
	public static function getThemeFromApplicationModel($key, $val){
		foreach(self::$_applicationModel->topics as $t){
			if($t[$key] == $val){
				return $t;
			}
		}
		return null;
	}
	
	// Key : uri, name, topicid, viewpointid
	public static function getThemeFromCommunityViewpoint($key, $val){
		foreach(self::$_communityViewpoint->topics as $t){
			if($t[$key] == $val){
				return $t;
			}
		}
		return null;
	}
	
	public static function getThemeFromUsersRootTheme($key, $val){
		foreach(self::$_usersRootTheme->relatedTopics as $t){
			if($t[$key] == $val){
				// {"viewpointid":"55","topicid":"95","name":"Page personnelle","uri":"http:\/\/argos.orkidees.com\/viewpoint\/55\/topic\/95\/"}
				$tmp = preg_match("/viewpoint\/(\d+)\/topic\/(\d+)/", $t['uri']);
				return array("viewpointid" => $tmp[1], "topicid" => $tmp[2], "name" => $t['name'], "uri" => $t['uri']);
			}
		}
		return null;
	}
	
	// Attention il ne renvoi que les thèmes de "premiers" degré
	public static function getThemeFromViewpoint($uri_vpt ,$key, $val){
		$vpt = self::__callStatic('getViewpoint', array($uri_vpt));
		foreach($vpt->topics as $t){
			if($t[$key] == $val){
				return $t;
			}
		}
		return null;		
	}

	// Pour bloquer les appels à argos s'il est indisponible
	public static function check(){
		return self::$_isOk;
	}
	
	public static function setOk($v){
		self::$_isOk = $v;
		return self::$_isOk;
	}
	
	public static function getApplicationModel(){
		return self::$_applicationModel;
	}

	public static function getCommunityViewpoint(){
		return self::$_communityViewpoint;
	}

	public static function getUsersRootTheme(){
		return self::$_usersRootTheme;
	}
	
	public static function intersectTheme($t1_uri, $t2_uri){
	
		$t1 = self::__callStatic("getTopic", array($t1_uri));
		$t2 = self::__callStatic("getTopic", array($t2_uri));
		
		$listuri = array();
		
		if(count($t1->entities) >= count($t2->entities)){
			foreach($t1->entities as $e){
				$listuri[] = $e['uri'];
			}
			$cmp = $t2->entities;
		}else{
			foreach($t2->entities as $e){
				$listuri[] = $e['uri'];
			}
			$cmp = $t1->entities;
		}
		
		$res = array();
		foreach($cmp as $ent){
			if(in_array($ent['uri'], $listuri)){
				$res[] = $ent;
			}
		}
		
		return $res;
	}
	
	// Attention les documents bénéficient d'un traitement différent car ils sont thématisé dans le communtyViewpoint - la règle de formation des entity_id est "typeOfContribution_idContribution"
	public function addEntityToApplicationModel($type, $resurl, $entity_id){
		// entpath correspond au type de ressource_id de la ressource
		if(self::$_isOk){	
			$topics[] = self::getThemeFromApplicationModel('name', self::$_appMdl_type_translation[$type]);
			
			$resources = array(array('resourcename' => 'html', 'resourceurl' => $resurl));
			
			$argosUrl = self::getArgosUrl();
			
			return self::__callStatic('addEntity', array($argosUrl, '/'.$entity_id, $topics, null, $resources));
		}
		return null;
	}
	
	// Pour rattacher une entité à un thème ou une liste de thèmes - tous les param sont des urls
	public function attachEntity($ent, $thm){
		if(!is_array($thm)){
			$thm = array($thm);
		}
		
		return self::__callStatic("addTopics", array($ent, $thm));
		
	}
	
	// Pour détacher une entité d'un thème ou d'une liste de thèmes
	public function detachEntity($ent, $thm){
		if(!is_array($thm)){
			$thm = array($thm);
		}
		
		return $res = self::__callStatic("removeTopics", array($ent, $thm));
	}
	
	
	
	// Ajoute login et mdp à la fin de la liste d'argument pour permettre l'authorisation http
	/* Besoin de PHP 5.3 pour pouvoir faire à la façon méthode magique avec __callStatic, mais cela ne change que l'appel in fine donc comme le ttt est le même je me permet de la créer. --*/	
	public static function __callStatic($method,$args){
		if(self::$_isOk){
			foreach(self::$dict as $class){
				if(in_array($method, get_class_methods($class))){
				
					$args[] = self::$_login;
					$args[] = self::$_mdp;
					return call_user_func_array(array($class, $method), $args);
					
				
				}
			}
			
			throw new Zend_Exception($method.' n\'a pas de classe correspondante.');
		
		}else{
			return null;
		}
	}
}

?>