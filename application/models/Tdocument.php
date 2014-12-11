<?php
/*

  	id  	tinyint(11) 
	url 	varchar(255) 

*/

class Aloa_Mdl_Tdocument extends Zend_Db_Table_Abstract{

	protected $_name="document";
	protected $_primary='id';

	protected $_referenceMap = array(
							'RelatedContrib' => array(
												'columns' => 'contrib_id', 
												'refTableClass' => 'Aloa_Mdl_Tcontribution'
												)
									);
									
	public function getByContribId($id){
		$select = $this->select();
		$select->where('contrib_id = ?', $id);
		return $this->fetchRow($select);
	}
	
}

?>