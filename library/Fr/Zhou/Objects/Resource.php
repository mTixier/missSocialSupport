<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 22:34:29 CET 2009
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
  * Resource object class
  *
  * The Fr_Zhou_Objects_Resource class is a concrete subclass of the general
  * Fr_Zhou_Objects_Hypertopic class, tailored for representing the Resources
  * Object.
  *
  * URI Example: 
  * http://ex.com/resource/http%3A%2F%2Ftech-ada.utt.fr%2Fresource.htm/
  *
 * @package Fr_Zhou_Objects
 * @author Chao ZHOU <chao.zhou@utt.fr>
 **/
class Fr_Zhou_Objects_Resource extends Fr_Zhou_Objects_Hypertopic
{

  /**
   * Items which include this resource
   * Format: <pre>
   *    array(array(name => item name, 
   *                uri => http://ex.com/entity/item/name))</pre>
   *
   * @var array
   */
  public $entities = array();

  /**
   * Resource URL
   * Example: http://tech-ada.utt.fr/resource.htm
   * @var string
   */
  public $resourceUrl = NULL;

  /**
   * parser URI
   *
   * e.g. uri: http://www.example.com/resource/
   * http%3A%2F%2Ftech-ada.utt.fr%2Fresource.htm/
   *
   * Result: $this->resourceUrl = http://tech-ada.utt.fr/resource.htm
   *
   * @return bool Returns TRUE if the URL is correct.
   * @exception throw exception if the URL is incorrect.
   */
  public function parseUri() {
    $pos = stripos($this->uri,"/resource/");
    if($pos === false)
      throw new Fr_Zhou_Exception(
        "Invalid URL format error", 400, "URL:".$this->uri);
    $this->_serviceUrl = substr ( $this->uri, 0, $pos);
    $this->resourceUrl = substr($this->uri, $pos + 10);
    
    if(substr($this->resourceUrl, -1) == "/")
      $this->resourceUrl = substr($this->resourceUrl, 0, -1);
    $this->resourceUrl = rawurldecode($this->resourceUrl);
    return true;
  }

  /**
   * To XML document
   * No need to generate XML document
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
        $name = (isset($e["name"])?(string) $e["name"]:(string) $e);
        $this->entities[] = array("uri" => $href, "name" => $name);
        }
  }
}
?>