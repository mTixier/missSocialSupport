<?php
/*

  	id 	 int(11)	primary
	dateTime	 int(11)
	type		varchar(20)	-- Pour le moment : newmember, news, publicnews, document, question, answer, storytheme, userstory, reaction, chat, utterance
	author_id 	(int11) 
	title		varchar(140) 
	content 	text	
	keywords 	text
	parent_id 	(int11) 


*/


class Aloa_Mdl_Tcontribution extends Zend_Db_Table_Abstract{

	// Types de contributions prédéfinis par l'application et en relation avec ceux manipulés dans la DB
	const NEWMEMBER = "newmember";
	const NEWS = "news";
	const PUBLICNEWS = "publicnews";
	const DOCUMENT = "document";
	const QUESTION = "question";
	const ANSWER = "answer";
	const STORYTHEME = "storytheme";
	const USERSTORY = "userstory";
	const REACTION = "reaction";
	const CHAT = "chat";
	const UTTERANCE = "utterance";

	const SEARCH_ENGINE_PREFIX = 'contrib_';
	
	protected $_col2fields = array(
									'id' => 'keyword',
									'dateTime' => 'unIndexed',
									'type' => 'keyword',
									'author_id' => 'unIndexed',
									'title' => 'text',
									'content' => 'text',
									'keywords' => 'text',
									'parent_id' => 'unIndexed',
									'lastEdit' => 'unIndexed',
									'nbUp' => 'unIndexed'
										); 
	
	protected $_name="contribution";
	protected $_primary='id';

	protected $_referenceMap = array(
								'Author' => array(
													'columns' => 'author_id', 
													'refTableClass' => 'Aloa_Mdl_Tuser'
													),
								'RelatedContribution' => array(
													'columns' => 'parent_id', 
													'refTableClass' => 'Aloa_Mdl_Tcontribution'
													),
								'RelatedDocument' => array(
													'columns' => 'parent_id', 
													'refTableClass' => 'Aloa_Mdl_Tdocument'
													),								
								'RelatedChat' => array(
													'columns' => 'related_id', 
													'refTableClass' => 'Aloa_Mdl_Tchat'
													),
								);

/* Fonctions de maintien de l'index du moteur de recherche */
// TODO : le mieux aurait été de créer une classe de Row spécifique qui implémente les méthodes _postinsert, postupdate et postdelete

    public function insert(array $data)
    {
		$res = parent::insert($data);
		
        // ajouter à l'index
		$front = Zend_Controller_Front::getInstance();
		$index = $front->getParam('aloa_search_index');
		
		$last_inserted_id = $res;
		$doc = $this->createIndexedDocument($data, $last_inserted_id);
		
		$index->addDocument($doc);
		
		$index->commit();  
		$index->optimize(); 
		
		// ajouter au sitemap - un generateSitemap ne serait pas de trop
		if(isset($data['type']) && in_array($data['type'], array(self::DOCUMENT, self::QUESTION, self::STORYTHEME, self::USERSTORY))){
			$url = $this->urlResolver($res);
			
			$f = fopen('./sitemap.txt', 'ab');
			fwrite($f, $url. PHP_EOL);
			fclose($f);
		}
		
        return $res;
    }

    public function update(array $data, $where)
    {
		$res = parent::update($data, $where);
	
        // mettre à jour l'index
		$front = Zend_Controller_Front::getInstance();
		$index = $front->getParam('aloa_search_index');

		$select = $this->select()->where($where);
		$rows = $this->fetchAll();
		foreach($rows as $row){
			$hit = $this->retrieveIndexedDocument($row->id, $index);
			$index->delete($hit->id);
			
			// problème data ne contient potentiellement que la mise à jour des données... --> semble résolu :) Comme pour insert, il est logique de mettre à jour les données de la DB avant l'index :D
			//$doc = $this->createIndexedDocument(array_merge($row->toArray(), $data));
			$doc = $this->createIndexedDocument($row->toArray());
			
			$index->addDocument($doc);
		}

		$index->commit();  
		$index->optimize(); 		

        return $res;
    }
	
    public function delete($where)
    {
        // mettre à jour l'index
		$front = Zend_Controller_Front::getInstance();
		$index = $front->getParam('aloa_search_index');
		
		$select = $this->select()->where($where);
		$rows = $this->fetchAll();
		foreach($rows as $row){
			$hit = $this->retrieveIndexedDocument($row->id, $index);
			$index->delete($hit->id);
		}
		
		$index->commit();  
		$index->optimize(); 
		
        return parent::delete($where);
    }

	
	public function createIndexedDocument($data, $id = null){
		
		if($id != null){
			$data['id'] = $id;
		}
	
		$doc = new Zend_Search_Lucene_Document();
		foreach($data as $col => $val){
			$doc->addField(call_user_func_array(array("Zend_Search_Lucene_Field", $this->_col2fields[$col]), array(self::SEARCH_ENGINE_PREFIX.$col, $val, 'utf-8')));
		}
		// Ajout d'un champs complémentaire avec le nom d'utilisateur en clair
		$tuser = new Aloa_Mdl_Tuser();
		$user = $tuser->find($data['author_id'])->current();
		
		// Keyword est trop fort car le nom d'utilisateur doit être exactement le même au caractère près
		$doc->addField(Zend_Search_Lucene_Field::text(self::SEARCH_ENGINE_PREFIX."author", $user->uname, 'utf-8'));
		
		
		return $doc;
	}

	
	public function retrieveIndexedDocument($id, $index){
		
		$term = new Zend_Search_Lucene_Index_Term($id, self::SEARCH_ENGINE_PREFIX.'id');
		$query = new Zend_Search_Lucene_Search_Query_Term($term);
		$hits  = $index->find($query);
		foreach ($hits as $hit) {
			return $hit;
			//$hit->id
		}

		
	}
	

	
/* Fonctions raccourcis de reccherche dans la table */
	// Ajouter un paramètre optionnel - tout sauf $type
	public function getByAuthor($auth, $type='*', $limit=0){
		$select = $this->select();
		
		if($type != '*'){
			if(is_array($type)){
				$select->where('type IN (?)', $type);
			}else{
				$select->where('type = ?', $type);
			}
		}
		
		$select->where('author_id = ?', $auth);
		
		if($limit > 0){
			$select->limit($limit);
		}
		
		$select->order('dateTime DESC');
		
		return $this->fetchAll($select);
		
	}
	
	public function getByTitle($ttl, $type='*', $parent_id = null){
		$select = $this->select();
		
		if($type != '*'){
			if(is_array($type)){
				$select->where('type IN (?)', $type);
			}else{
				$select->where('type = ?', $type);
			}
		}
		
		$select->where('title LIKE ?', $ttl);
		
		if($parent_id != null){
			$select->where('parent_id = ?', $parent_id);
		}
		
		return $this->fetchRow($select);
		
	}
		
	
	public function getPopularContributions($type='*', $limit=0, $except = false){
	
	/*  SELECT `c`. * , `b`.`id` , COUNT( `b`.`id` ) + `c`.`nbUp` AS `score` FROM `contribution` AS `c` LEFT JOIN `chat` AS `b` ON c.id = b.related_id AND `b`.`related_type` = 'contribution' LEFT JOIN `utterance` AS `u` ON u.chat_id = b.id WHERE ( c.type = 'document' ) GROUP BY `c`.`id` ORDER BY `score` DESC LIMIT 3  */ 	
	
	/*
	
	Le score de popularité est fonciton de la quantité de bavardage (chat) (nb d'utterance) et du nb de signalement comme conseil pratique.
	La requête est fait deux jointure mais on joue sur l'effet de bord que par la jointure à gauche on a une ou au moins autant de fois de ligne jointe via la table 'chat' que d'utterance.
	
	*/
	
	/* Zend_Table_Select ne peut prendre des valeurs que dans la table */
	
		$db = $this->getAdapter();
		$select = new Zend_Db_Select($db);
		$select->from(array('c' => 'contribution'), array('id', 'dateTime'));
		
		
		//$select->order('nbUp DESC');
		
		$op = '=';
		if($except){
			$op = '!=';
		}
		
		if($type != '*'){
			if(is_array($type)){
				$select->where('`c`.`type` IN (?)', $type);
			}else{
				$select->where('`c`.`type` '.$op.' ?', $type);
			}
		}
		
		$select->joinLeft(array('b' => 'chat'), "`c`.`id` =  `b`.`related_id` AND `b`.`related_type` = 'contribution'", array('score' => '(COUNT(`b`.`id`) + `c`.`nbUp`)'));
		$select->joinLeft(array('u' => 'utterance'), '`u`.`chat_id` =  `b`.`id`', array());
		$select->group('c.id');
		$select->order('score DESC');
		
		if($limit > 0){
			$select->limit($limit);
		}
		
		$res = $db->fetchAll($select);
		
		$ids = array();
		foreach($res as $row){
			$ids[] = $row['id'];
		}
		
		
		return $this->find($ids);	
	}
	
	
	
	public function getLastContributions($type='*', $limit=0, $except = false){
		$select = $this->select();
		
		$select->order('dateTime DESC');
		
		$op = '=';
		if($except){
			$op = '!=';
		}
		
		if($type != '*'){
			if(is_array($type)){
				$select->where('type IN (?)', $type);
			}else{
				$select->where('type '.$op.' ?', $type);
			}
		}
		
		if($limit > 0){
			$select->limit($limit);
		}
		
		return $this->fetchAll($select);
	}

/* fonctions de gestions de la colonne keywords */
// Gagnerai à gérer les majuscules/minuscules...
// Est utilisé notamment par l'hypertopic controler et le document controller

	public function addKeywords($past_kw, $kw){
		
		if(is_array($kw)){
			$kw = implode(',', $kw);
		}
		
		if($past_kw == null){
			$res = $kw;
		}else{
			$kwlist = array_unique(explode(',', $past_kw.','.$kw));
			$res = implode(',', $kwlist);
		}
		return $res;
	}
	
	public function rmvKeywords($past_kw, $kw){
	
		if($past_kw == null){
			$res = null;
		}else{
			$res = array();
			$kwlist = explode(',', $past_kw);
			
			if(!is_array($kw)){
				$kw = array($kw);
			}
			
			foreach($kwlist as $v){
				if(!in_array($v, $kw)){
					$res[] = $v;
				}
			}
			// Si la liste est vide alors null
			if(count($res) > 0){
				$res = implode(',',$res);
			}else{
				$res = null;
			}
		}
		return $res;
	}
	
/* Mail */
	public function invitationMail($to, $contrib){
	
			$tuser = new Aloa_Mdl_Tuser();
			$user = $tuser->getByUname($to, 1)->current();
			
			$auth = $contrib->findParentAloa_Mdl_Tuser();
	
			$mail = new Zend_Mail('UTF-8');
			
			$mail->setFrom(Zend_Controller_Front::getInstance()->getParam('aloa_send_mail'), 'Aloa');
			$mail->addTo($user->email, $user->uname);
			$mail->setSubject($user->uname.' vous invite à consulter : '.$contrib->title);
			
			$mail->addHeader('MIME-Version', '1.0');
			$mail->addHeader('Content-Type', 'text-plain; charset=utf-8');
			$mail->addHeader('X-Mailer', 'PHP');
			
			$mail->setBodyText("Bonjour ".$user->uname.",\r\n \r\n ".$auth->uname." vous invite à consulter : ".$contrib->title."\r\n \r\n 
			".Zend_Controller_Front::getInstance()->getParam('aloa_server')."/".$contrib->type."/view/title/".$this->sqlifyParam($contrib->title)."\r\n \r\nA trés bientôt sur Aloa.
			 \r\n \r\n \r\n \r\nSi vous pensez que ce mail ne vous était pas destiné, nous nous en excusons et vous remercions de nous le signaler par retour de mail à cette adresse : ".Zend_Controller_Front::getInstance()->getParam('aloa_mail_admin'));
			// version HTML ?
			
			
			try{
				$mail->send();
			}catch(Zend_Mail_Exception $e){
				
			}
	
	}

	public function alertProMail($contrib){
	
		$auth = $contrib->findParentAloa_Mdl_Tuser();

		$mail = new Zend_Mail('UTF-8');
		
		$mail->setFrom(Zend_Controller_Front::getInstance()->getParam('aloa_send_mail'), 'Aloa');
		$mail->addTo(Zend_Controller_Front::getInstance()->getParam('aloa_mail_pro'), 'Professionnels Aloa');
		$mail->setSubject($auth->uname.' a posé une question : '.$contrib->title);
		
		$mail->addHeader('MIME-Version', '1.0');
		$mail->addHeader('Content-Type', 'text-plain; charset=utf-8');
		$mail->addHeader('X-Mailer', 'PHP');
		
		$mail->setBodyText("Bonjour,\r\n \r\n ".$auth->uname." a demandé une réponse de professionnel pour sa question : ".$contrib->title.".Vous pouvez lui répondre en cliquant sur ce lien : \r\n \r\n 
		".Zend_Controller_Front::getInstance()->getParam('aloa_server')."/question/view/title/".$this->sqlifyParam($contrib->title)."\r\n \r\nMerci beaucoup de votre participation.
		 \r\n \r\n \r\n \r\nSi vous pensez que ce mail ne vous était pas destiné, nous nous en excusons et vous remercions de nous le signaler par retour de mail à cette adresse : ".Zend_Controller_Front::getInstance()->getParam('aloa_mail_admin'));
		// version HTML ?
		
		
		try{
			$mail->send();
		}catch(Zend_Mail_Exception $e){
			
		}

	}
	
	public function alertAnswerMail($answer, $question_id){
	
		$tcontrib = new Aloa_Mdl_Tcontribution();
		
		$answer_author = $answer->findParentAloa_Mdl_Tuser();
		
		$question = $tcontrib->find($question_id)->current();
		$user = $question->findParentAloa_Mdl_Tuser();
	
		$mail = new Zend_Mail('UTF-8');
		
		$mail->setFrom(Zend_Controller_Front::getInstance()->getParam('aloa_send_mail'), 'Aloa');
		//$mail->addTo(Zend_Controller_Front::getInstance()->getParam('aloa_mail_pro'), 'Professionnels Aloa');
		$mail->addTo($user->email, $user->uname);
		$mail->setSubject($answer_author->uname.' a proposé une réponse à votre question : '.$question->title);
		
		$mail->addHeader('MIME-Version', '1.0');
		$mail->addHeader('Content-Type', 'text-plain; charset=utf-8');
		$mail->addHeader('X-Mailer', 'PHP');
		
		$mail->setBodyText("Bonjour ".$user->uname.",\r\n \r\n ".$answer_author->uname." a proposé une réponse à votre question : ".$question->title.".Vous pouvez la consulter en cliquant sur ce lien : \r\n \r\n 
		".Zend_Controller_Front::getInstance()->getParam('aloa_server')."/question/view/title/".$this->sqlifyParam($question->title)."#".$answer->id."\r\n \r\nA trs bientôt sur Aloa.
		 \r\n \r\n \r\n \r\nSi vous pensez que ce mail ne vous était pas destiné, nous nous en excusons et vous remercions de nous le signaler par retour de mail à cette adresse : ".Zend_Controller_Front::getInstance()->getParam('aloa_mail_admin'));
		// version HTML ?
		
		
		try{
			$mail->send();
		}catch(Zend_Mail_Exception $e){
			
		}	
	}
	
	public function alertReactionMail($userstory, $reaction){
		
		$reaction_author = $reaction->findParentAloa_Mdl_Tuser();
		
		$user = $userstory->findParentAloa_Mdl_Tuser();
		$storytheme = $userstory->findParentAloa_Mdl_Tcontribution();
	
		$mail = new Zend_Mail('UTF-8');
		
		$mail->setFrom(Zend_Controller_Front::getInstance()->getParam('aloa_send_mail'), 'Aloa');
		//$mail->addTo(Zend_Controller_Front::getInstance()->getParam('aloa_mail_pro'), 'Professionnels Aloa');
		$mail->addTo($user->email, $user->uname);
		$mail->setSubject($reaction_author->uname.' a laissé un message à propos de votre témoignage : '.$userstory->title);
		
		$mail->addHeader('MIME-Version', '1.0');
		$mail->addHeader('Content-Type', 'text-plain; charset=utf-8');
		$mail->addHeader('X-Mailer', 'PHP');
		
		$mail->setBodyText("Bonjour ".$user->uname.",\r\n \r\n ".$reaction_author->uname." a laissé un message à propos de votre témoignage : ".$userstory->title." sur le sujet ".$storytheme->title.".Vous pouvez le consulter en cliquant sur ce lien : \r\n \r\n 
		".Zend_Controller_Front::getInstance()->getParam('aloa_server')."/story/view/storytheme/".$this->sqlifyParam($storytheme->title)."/userstory/".$this->sqlifyParam($userstory->title)."#".$reaction->id."\r\n \r\nA très bientôt sur Aloa.
		 \r\n \r\n \r\n \r\nSi vous pensez que ce mail ne vous était pas destiné, nous nous en excusons et vous remercions de nous le signaler par retour de mail à cette adresse : ".Zend_Controller_Front::getInstance()->getParam('aloa_mail_admin'));
		// version HTML ?
		
		
		try{
			$mail->send();
		}catch(Zend_Mail_Exception $e){
			
		}	
	}	
	
	
/* Useful */


	public function urlResolver($id){

		// URL resolver - permet de contruire l'url d'une contribution à partir de son enregistrement dans la base de données.
		
			$rel_contrib = $this->find($id)->current();
			
			switch($rel_contrib->type){
					
					case self::NEWS :
					case self::PUBLICNEWS :
						$author = $rel_contrib->findParentAloa_Mdl_Tuser();
						$relatedUrl = "/communaute/pageperso/uname/".$author->uname."#".$rel_contrib->id;
						break;
					
					case self::DOCUMENT :
						$relatedUrl = "/document/view/title/".$this->sqlifyParam($rel_contrib->title);
						break;
						
					case self::QUESTION :
						$relatedUrl = "/question/view/title/".$this->sqlifyParam($rel_contrib->title);
						break;
					
					case self::ANSWER :
						$question = $rel_contrib->findParentAloa_Mdl_Tcontribution();
						$relatedUrl = "/question/view/title/".$this->sqlifyParam($question->title)."#".$rel_contrib->id;
						break;

					case self::STORYTHEME :
						$relatedUrl = "/story/view/storytheme/".$this->sqlifyParam($rel_contrib->title);
						break;
						
					case self::USERSTORY :
						$storytheme = $rel_contrib->findParentAloa_Mdl_Tcontribution();
						$relatedUrl = "/story/view/storytheme/".$this->sqlifyParam($storytheme->title)."/userstory/".$this->sqlifyParam($rel_contrib->title);
						break;
						
					case self::REACTION :
						$storytheme = $rel_contrib->findParentAloa_Mdl_Tcontribution();
						$userstory = $storytheme->findParentAloa_Mdl_Tcontribution();
						$relatedUrl = "/story/view/storytheme/".$this->sqlifyParam($storytheme->title)."/userstory/".$this->sqlifyParam($userstory->title)."#".$rel_contrib->id;
						break;
						
					default:
						$relatedUrl = "/index";
				}
				
				$relatedUrl = Zend_Controller_Front::getInstance()->getParam('aloa_server').$relatedUrl;	
				
				return $relatedUrl;
	}
	
	public function sqlifyParam($param){
	
		$text = preg_replace('/[^a-z0-9]/i', '_', $param);
		
		return $text; 
	}
}

?>