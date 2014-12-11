<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 22:39:28 CET 2009
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
 * Viewpoint object class
 *
 * The Fr_Zhou_Objects_Viewpoint class is a concrete subclass of the general
 * Fr_Zhou_Objects_Hypertopic class, tailored for representing the viewpoint
 * Object.
 *
 * URI Example: http://ex.com/viewpoint/1/
 *
 * @package Fr_Zhou_Objects
 * @author Chao ZHOU <chao.zhou@utt.fr>
 **/
class Fr_Zhou_Objects_Viewpoint extends Fr_Zhou_Objects_Hypertopic
{

  /**
   * Related Topics List
   * Format: <pre>
   *    array(array(uri => http://ex.com/viewpoint/1/topic/2/,
   *                name => topic name, 
   *                topicid => 2, 
   *                viewpointid => 1))</pre>
   *
   * @var array
   */
  public $topics = array();
  
  /**
   * Related Actors List
   * Format: <pre>
   *    array(array(uri => http://ex.com/actor/chao.zhou/,
   *                name => Chao ZHOU, 
   *                role => creator, 
   *                actorid => chao.zhou))</pre>
   *
   * @var array
   */
  public $actors = array();
  
  /**
   * Viewpoint Name
   * Example: My viewpoint
   *
   * @var string
   */
  public $viewpointName = NULL;

  /**
   * Viewpoint Id
   * Example: 1
   *
   * @var string
   */
  public $viewpointId = NULL;
  
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
    
    $result = null;
    $reg = "/viewpoint\/(.+)\//";
    if(!preg_match_all( $reg, $this->uri, $result ))
      throw new Fr_Zhou_Exception(
        "Invalid URL format error", 400, "URL:".$this->uri);

    $this->viewpointId = $result[1][0];
    return true;
  }

  /**
   * To XML document
   *
   * @return void
   */
  public function toXml(){
    $xmlstr  = "<?xml version='1.0' encoding='UTF-8'?>";
    $xmlstr .= "<viewpoint></viewpoint>";
    $xml = new SimpleXMLElement($xmlstr);
    $xml->addAttribute("name", $this->viewpointName);
    foreach($this->actors as $e){
      $node = $xml->addChild("actor");
      $href = $this->_serviceUrl."/actor/".$e["actorid"]."/";
      $node->addAttribute("href", $href);
      $node->addAttribute("name", $e["name"]);
      if($e["role"] != "")
        $node->addAttribute("role", $e["role"]);
    }
    foreach($this->topics as $e){
      $topicname = $this->escapeXML($e["name"]);
      $node = $xml->addChild("topic", $topicname);
      $href = $this->_serviceUrl."/viewpoint/".$this->viewpointId
          ."/topic/".$e["topicid"]."/";
      $node->addAttribute("href", $href);
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
    $this->viewpointName = isset($parser["name"]) 
                  ? (string) $parser["name"] : "";
    
    if(isset($parser->actor))
      foreach ($parser->actor as $e){
        $href = (string) $e["href"];
        $role = (string) $e["role"];
        $name = (isset($e["name"])?(string) $e["name"]:(string) $e);
        $reg = "/actor\/(.+)\//";
        if(!preg_match_all( $reg, $href, $result ))
          throw new Fr_Zhou_Exception(
            "Invalid URL format error", 400, "Actor URL:".$href);

        $actorId = $result[1][0];
        array_push ($this->actors, array(
                "actorid" => $actorId,
                "name" => $name, 
                "role" => $role,
                "uri"=> $href));
      }
      
    if(isset($parser->topic))
      foreach ($parser->topic as $e)
      {
        $href = (string) $e["href"];
        $name = (isset($e["name"])?(string) $e["name"]:(string) $e);
        $reg = "/viewpoint\/(.+)\/topic\/(.+)\//";
        if(!preg_match_all( $reg, $href, $result ))
          throw new Fr_Zhou_Exception(
            "Invalid URL format error", 400, "Topic URL:".$href);

        $viewpointId = $result[1][0];
        $topicId = $result[2][0];

        array_push ($this->topics, array(
                "viewpointid" => $viewpointId,
                "topicid" => $topicId, 
                "name" => $name,
                "uri" => $href));
      }
    //$firephp->log($this,"Attribute Objects");
  }
}
?>