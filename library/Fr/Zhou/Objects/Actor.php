<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 22:10:44 CET 2009
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
 * Actor object class
 * 
 * The Fr_Zhou_Objects_Actor class is a concrete subclass of the general
 * Fr_Zhou_Objects_Hypertopic class, tailored for representing the actor
 * Object.
 *
 * URI Example: 
 *      http://ex.com/actor/chao.zhou/
 *
 * @package Fr_Zhou_Objects
 * @author Chao ZHOU <chao.zhou@utt.fr>
 **/
class Fr_Zhou_Objects_Actor extends Fr_Zhou_Objects_Hypertopic
{

  /**
   * Viewpoints
   * format:<pre>
   *  array(array(name => viewpoint name,
   *        uri => http://foo.bar/viewpoint/1/,
   *        viewpointid => 1,
   *        role => reader))</pre>
   *
   * @var array
   */
  public $viewpoints = array();

  /**
   * Actor Full Name
   * Example: Chao ZHOU
   *
   * @var string
   */
  public $actorName = NULL;

  /**
   * Actor Id
   * Example: chao.zhou
   *
   * @var string
   */
  public $actorId = NULL;

  /**
   * parser URI
   *
   * @return bool Returns TRUE if the URL is correct.
   * @exception throw exception if the URL is incorrect.
   */
  public function parseUri() {
    $pos = stripos ( $this->uri, "/actor/" );
    if ($pos === false)
      return false;
    $this->_serviceUrl = substr ( $this->uri, 0, $pos);
    
    $result = null;
    $reg = "/actor\/(.+)\//";
    if(!preg_match_all( $reg, $this->uri, $result )){
      throw new Fr_Zhou_Exception(
        "Invalid URL format error", 400, "URL:".$this->uri);
      return false;
    }
    $this->actorId = $result[1][0];
    return true;
  }

  /**
   * To XML document
   *
   * @return void
   */
  public function toXml(){
    $xmlstr  = "<?xml version='1.0' encoding='UTF-8'?>";
    $xmlstr .= "<actor></actor>";
    $xml = new SimpleXMLElement($xmlstr);
    $xml->addAttribute("name", $this->actorName);
    foreach($this->viewpoints as $e){
      $viewpointname = $this->escapeXML($e["name"]);
      $node = $xml->addChild("viewpoint",$viewpointname);
      $href = $this->_serviceUrl."/viewpoint/".$e["viewpointid"]."/";
      $node->addAttribute("href", $href);
      if($e["role"] != "")
        $node->addAttribute("role", $e["role"]);
    }
    return $xml -> asXML();
  }

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
                "Request Body : \n"
                . $this->_responseBody
                . "\nError Message:\n".$ex->getMessage());
    }
    $this->actorName = isset($parser["name"])?(string) $parser["name"]:"";
    
    if(isset($parser->viewpoint))
      foreach ($parser->viewpoint as $e){
        $href = (string) $e["href"];
        $role = (string) $e["role"];
        $name = (isset($e["name"])?(string) $e["name"]:(string) $e);
        $reg = "/viewpoint\/(.+)\//";
        if(!preg_match_all( $reg, $href, $result ))
          throw new Fr_Zhou_Exception(
            "Invalid URL format error",
            400,
            "URL:"
            .$href);
            
        $viewpointid = $result[1][0];
        array_push ($this->viewpoints, array(
                "uri" => $this->_serviceUrl."/viewpoint/".$viewpointid."/",
                "viewpointid" => $viewpointid,
                "name" => $name, 
                "role" => $role) );
      }
  }
}
?>