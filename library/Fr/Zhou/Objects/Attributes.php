<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 22:33:52 CET 2009
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
  * Attributes object class
  *
  * The Fr_Zhou_Objects_Attributes class is a concrete subclass of the general
  * Fr_Zhou_Objects_Hypertopic class, tailored for representing an Attributes
  * Object.
  *
  * URI Example: 
  *      http://ex.com/attribute/
 *
 * @package Fr_Zhou_Objects
 * @author Chao ZHOU <chao.zhou@utt.fr>
 **/
class Fr_Zhou_Objects_Attributes extends Fr_Zhou_Objects_Hypertopic
{

  /**
   * Attributes
   * Format: <pre>
   *    array((uri => http://ex.com/attribute/attributename/,
   *           name => attributename))</pre>
   * 
   * @var array
   */
  public $attributes = array();

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
    if(!isset($parser->attribute))
          return;

    foreach ($parser->attribute as $e){
        $href = (isset($e["href"])) ? (string) $e["href"]: "";
        $reg = "/attribute\/(.+)/";
        if(!preg_match_all( $reg, $href, $result ))
          continue;
        $name = rawurldecode($result[1][0]);
        if(substr($name, -1) == "/")
            $name = substr($name, 0, -1);
          $this->attributes[] = array("uri" => $href, "name" => $name);
        }
        //$firephp->log($this,"Attribute Objects");
  }

  /**
   * Get all attribute names
   *
   * Return values format:<pre>
   *    array("last_modified", "creation_date", ..)</pre>
   *  
   * @return array all attribute names
   */
  public function getNames(){
    $names = array();
    foreach($this->attributes as $attribute)
      $names[] = $attribute["name"];
    return $names;
  }
}
?>