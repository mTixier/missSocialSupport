<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 22:42:21 CET 2009
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
 * Viewpoints object class
 *
 * The Fr_Zhou_Objects_Viewpoints class is a concrete subclass of the general
 * Fr_Zhou_Objects_Hypertopic class, tailored for representing the viewpoint
 * list Object.
 *
 * URI Example: http://foo.bar/viewpoint/
 *
 * @package Fr_Zhou_Objects
 * @author Chao ZHOU <chao.zhou@utt.fr>
 **/
class Fr_Zhou_Objects_Viewpoints extends Fr_Zhou_Objects_Hypertopic
{

  /**
   * Viewpoints
   * Format:<pre> 
   *   array("name" => viewpoint name,
   *         "uri" => http://foo.bar/viewpoint/1/)</pre>
   *
   * @var array
   **/
  public $viewpoints = array();

  /**
   * parser URI
   *
   * @return bool Returns TRUE if the URL is correct.
   * @exception throw exception if the URL is incorrect.
   */
  public function parseUri() {
    $pos = stripos ( $this->uri, "/viewpoint/" );
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
    if(!isset($parser->viewpoint))
          return;

    foreach ($parser->viewpoint as $e){
        $href = (isset($e["href"])) ? (string) $e["href"]: "";
        $name = (isset($e["name"])?(string) $e["name"]:(string) $e);
        $this->viewpoints[] = array("uri" => $href, "name" => $name);
    }
    //$firephp->log($this,"Attribute Objects");
  }

  /**
   * Get all viewpoints
   *
   * @return array see {@link $viewpoints}
   */
  public function getViewpoints(){
    return $this->viewpoints;
  }
}
?>