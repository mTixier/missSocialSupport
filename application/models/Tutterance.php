<?php
/*

  	id  	tinyint(11) 
	dateTime 	tinyint(11)
	author_id	tinyint(11) 
	chat_id	tinyint(11) 
	content	text
	type		varchar(25) 

*/

class Aloa_Mdl_Tutterance extends Zend_Db_Table_Abstract{

	protected $_name="utterance";
	protected $_primary='id';

	protected $_referenceMap = array(
							'RelatedAuthor' => array(
												'columns' => 'author_id', 
												'refTableClass' => 'Aloa_Mdl_Tuser'
												),
							'ParentChat' => array(
												'columns' => 'chat_id', 
												'refTableClass' => 'Aloa_Mdl_Tchat'
												)
									);
								
	
}

?>