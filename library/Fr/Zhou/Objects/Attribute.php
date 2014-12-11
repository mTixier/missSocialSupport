<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 22:11:23 CET 2009
 * @copyright 2009, Tech-CICO, University of technology of Troyes
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link http://www.hypertopic.org
 * @package Fr_Zhou_Objects
 **/
 
/**
 * @see Fr_Zhou_Objects_Hypertopic
 */
require_once 'Fr/Zhou/Objects/Hypertopic.php';

/**
 * Attribute object class
 *
 * The Fr_Zhou_Objects_Attribute class is a concrete subclass of the general
 * Fr_Zhou_Objects_Hypertopic class, tailored for representing an Attribute
 * Object.
 *
 * URI Example: 
 *      http://ex.com/attribute/modified_date/
 *
 * @package Fr_Zhou_Objects
 */
class Fr_Zhou_Objects_Attribute extends Fr_Zhou_Objects_Hypertopic
{

  /**
   * Values
   * Format: <pre> 
   *    array(array(uri => http://ex.com/attribute/modified_date/2008-01-01,
   *           value => 2008-01-01))</pre>
   *
   * @var array
   */
  public $values = array();
  
  /**
   * Attribute Name
   * Example: modified_date
   *
   * @var string
   */
  public $attributeName;
  
  /**
   * parser URI
   *
   * @return bool Returns TRUE if the URL is correct.
   * @exception throw exception if the URL is incorrect.
   */
  public function parseUri() {
    $pos = stripos ( $this->uri, "/attribute/" );
    if ($pos === false)
      return false;
    $this->_serviceUrl = substr ( $this->uri, 0, $pos);
    $result = NULL;
    $reg = "/attribute\/(.+)/";
    if(!preg_match_all( $reg, $this->uri, $result ))
      throw new Fr_Zhou_Exception(
        "Invalid URL format error", 400, "URL:".$this->uri);

    $this->attributeName = rawurldecode($result[1][0]);
    if(substr($this->attributeName, -1) == "/")
      $this->attributeName = substr($this->attributeName, 0, -1);
    $this->attributeName = rawurldecode($this->attributeName);
    return true;
  }

  /**
   * To XML document
   *
   * @return void
   */
  public function toXml(){}

  /**
   * Parser the server response xml document
   *
   * @return void
   * @exception throw exception if the XML Document is incorrect.
   */
  public function parseXml()
  {
    //$firephp = FirePHP::getInstance(true);
    //$firephp->log($this->_responseBody);
    try {
      $parser = new SimpleXMLElement ( $this->_responseBody );
    } catch ( Exception $ex ) {
      throw new Fr_Zhou_Exception (
                "Unable to parser the hypertopic server's response",
                400,
                "Response Body:\n" . $this->_responseBody . "\n"
                . "Error Message:\n".$ex->getMessage());
    }
    if(!isset($parser->value))
          return;

    foreach ($parser->value as $e){
          $href = (isset($e["href"])) ? (string) $e["href"]: "";
          $value = (isset($e["value"])?(string) $e["value"]:(string) $e);
          $this->values[] = array("uri" => $href, "value" => $value);
        }
        //$firephp->log($this,"Attribute Objects");
  }
}
?>