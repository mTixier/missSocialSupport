<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Fri Feb 27 10:19:13 CET 2009
 * @copyright 2009, Tech-CICO, University of technology of Troyes
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link http://www.hypertopic.org
 * @package Fr_Zhou_Objects
 **/

/**
 * @see Zend_Exception
 */
require_once 'Zend/Exception.php';

/**
 * Exception Class
 * 
 *
 *
 * @package default
 * @author Chao ZHOU <chao.zhou@utt.fr>
 **/
class Fr_Zhou_Exception extends Zend_Exception
{
  
  public $info;
  public function __construct($message, $code = 0, $info = null) {
    parent::__construct($message, $code);
    $this->info = $info;
  }
  
  public function getMoreInfo(){
    return $this->info;
  }
}