<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 22:11:11 CET 2009
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
 * Actors object class
 *
 * The Fr_Zhou_Objects_Actors class is a concrete subclass of the general
 * Fr_Zhou_Objects_Hypertopic class, tailored for representing the viewpoint
 * list Object.
 *
 * URI Example: 
 *      http://ex.com/actor/
 *
 * @package Fr_Zhou_Objects
 */
class Fr_Zhou_Objects_Actors extends Fr_Zhou_Objects_Hypertopic
{

  /**
   * Actors
   * Format:<pre>
   *    array(array(name => actor name, 
   *          uri => http://example.com/actor/actorid/,
   *          id => actor id))</pre>
   *
   * @var array
   */
  public $actors = array();

  /**
   * parser URI
   *
   * @return bool Returns TRUE if the URL is correct.
   * @exception throw exception if the URL is incorrect.
   */
  public function parseUri() {
    $pos = stripos ( $this->uri, "/actor/" );
    if ($pos === false)
      throw new Fr_Zhou_Exception("Invalid URL format error", 400, "URL:".$this->uri);
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
    try {
      $parser = new SimpleXMLElement ( $this->_responseBody );
    } catch ( Exception $ex ) {
      throw new Fr_Zhou_Exception (
                "Unable to parser the hypertopic server's response",
                400,
                "Response Body:\n" . $this->_responseBody . "\n"
                . "Error Message:\n".$ex->getMessage());
    }
    if(!isset($parser->actor))
          return;

    foreach ($parser->actor as $e){
        $href = (isset($e["href"])) ? (string) $e["href"]: "";
        $reg = "/actor\/(.+)\//";
        if(!preg_match_all( $reg, $href, $result ))
          continue;
        $id = rawurldecode($result[1][0]);
        $name = (isset($e["name"])?(string) $e["name"]:(string) $e);
        $this->actors[] = array(
                "uri" => $href, 
                "name" => $name, 
                "id" => $id);
    }
  }
}
?>