<?php
class Aloa_Exception_Authentification extends Aloa_Exception
{
	const AUTH_REQUIRED = 11;

	public function __construct($message = null, $code = self::AUTH_REQUIRED, $info = null){
		if($message == null){
			$message = "Vous devez être inscrit et connecté comme utilisateur pour pouvoir effectuer cette action.";
		}
		parent::__construct($message, $code, $info);
	
	}

}
?>