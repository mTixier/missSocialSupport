<?php
/*

  	id  	tinyint(11) 
	belongsTo varchar(50) -> ref uname
	file 	varchar(255) -> comme ce n'est que le nom on pourrait faire plus court...

*/

class Aloa_Mdl_Tavatar extends Zend_Db_Table_Abstract{

	protected $_name="avatar";
	protected $_primary='id';

	
	public function getAvatars($belongsTo = 'all'){
		
		$select = $this->select();
		
		$select->where("belongsTo = ?", 'all');
		
		if($belongsTo != 'all'){
			if(is_array($belongsTo)){
				$select->orWhere("belongsTo IN (?)", $belongsTo);
			}else{
				$select->orWhere("belongsTo = ?", $belongsTo);
			}
		}

		return $this->fetchAll($select);
	
	}
	
}

?>