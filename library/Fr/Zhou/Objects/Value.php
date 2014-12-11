<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 22:36:55 CET 2009
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
 * Value object class
 *
 * The Fr_Zhou_Objects_Values class is a concrete subclass of the general
 * Fr_Zhou_Objects_Hypertopic class, tailored for representing the Value
 * Object.
 *
 * URI Example: http://ex.com/attribute/modifed_date/2008-01-01/
 *
 * @package Fr_Zhou_Objects
 * @author Chao ZHOU <chao.zhou@utt.fr>
 **/
class Fr_Zhou_Objects_Value extends Fr_Zhou_Objects_Hypertopic
{

  /**
   * Entities
   * Format: <pre>
   *    array(array(uri => http://ex.com/entity/item/name, 
   *                name => item name))</pre>
   *
   * @var array
   */
  public $entities = array();

  /**
   * Attribute Name
   * Example: modified_date
   *
   * @var string
   */
  public $attributeName;

  /**
   * Attribute Value
   * Example: 2008-01-01
   *
   * @var string
   */
  public $attributeValue;

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
    $this->_serviceUrl = substr ( $this->uri, 0, $pos+1 );

    $requestUrl = substr ( $this->uri, $pos);

    $result = null;
      $reg = "/attribute\/(.+)\/(.+)\/$/";
      if(preg_match_all( $reg, $requestUrl, $result )){
        $this->attributeName = rawurldecode($result[1][0]);
        $this->attributeValue = rawurldecode($result[2][0]);
        return true;
      }
      else
      return false;
  }

  /**
   * To XML document (no need)
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
    try {
      $parser = new SimpleXMLElement ( $this->_responseBody );
    } catch ( Exception $ex ) {
      throw new Fr_Zhou_Exception (
                "Unable to parser the hypertopic server's response",
                400,
                "Response Body:\n" . $this->_responseBody . "\n"
                . "Error Message:\n".$ex->getMessage());
    }
    if(!isset($parser->entity))
          return;

    foreach ($parser->entity as $e){
          $href = (isset($e["href"])) ? (string) $e["href"]: "";
          if($href == "") continue;
          $name = (isset($e["name"])?(string) $e["name"]:(string) $e);
          if($name == ""){
            $url = $href;
            if(substr($url,-1) == "/")
              $url = substr($url, 0, -1);
            $pos = strrpos($url, "/");
            $name = substr($url, $pos+1);
          }
          $this->entities[] = array("uri" => $href, "name" => $name);
        }
  }

  /**
   * Get all related entities
   *
   * @return array {@link $entities}
   */
  public function getEntities(){
    return $this->entities;
  }
}
?>