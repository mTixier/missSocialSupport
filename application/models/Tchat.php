<?php
/*

  	id  	tinyint(11) 
	dateTime 	tinyint(11)
	type	varchar(25)
	title		varchar(200)
	author_id	tinyint(11)  NULL
	related_id	tinyint(11)  NULL
	related_type	tinyint(11)   (page pour les caht affecté par defaut à certaine pages, user ou contribution sinon)
	// l'author id est plus là dans la mesure où il était envisagé la possibilité d'avoir plusieurs bavardages sur un même page lancé par différents utilisateurs

*/

class Aloa_Mdl_Tchat extends Zend_Db_Table_Abstract{

	// Références aux ids des bavardages définit par défaut et qui ne sont pas générés suite à une contribution
	const CHAT_HOME = 1;
	const CHAT_DOCUMENT = 2;
	const CHAT_QA = 3;
	const CHAT_STORIES = 4;
	const CHAT_COMMUNITY = 5;
	const CHAT_ABOUT = 6;

	// les types de chat - influence pour le related_id
	const TYPE_PAGE = 'page';
	const TYPE_CONTRIBUTION = 'contribution';
	const TYPE_USER = 'user';
	const TYPE_THEME = 'theme';

	protected $_name="chat";
	protected $_primary='id';

	
	protected $_referenceMap = array(
							'RelatedAuthor' => array(
												'columns' => 'author_id', 
												'refTableClass' => 'Aloa_Mdl_Tuser'
												),
							'RelatedContribution' => array(
												'columns' => 'related_id', 
												'refTableClass' => 'Aloa_Mdl_Tcontribution'
												)
									);
									
	
}

?>