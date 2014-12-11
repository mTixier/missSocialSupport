<?php


class Zend_View_Helper_SqlifyParam{

	public function SqlifyParam($param){
	
		$text = preg_replace('/[^a-z0-9]/i', '_', $param);
		
		return $text; 
	}

}

?>