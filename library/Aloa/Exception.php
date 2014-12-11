<?php
class Aloa_Exception extends Zend_Exception
{
  
  const STANDARD = 1;
  
  public $info;
  
  public function __construct($message, $code = self::STANDARD, $info = null) {
    parent::__construct($message, $code);
    $this->info = $info;
  }
  
  public function getMoreInfo(){
    return $this->info;
  }
}

?>