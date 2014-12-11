<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 22:35:57 CET 2009
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
 * Topic object class
 *
 * The Fr_Zhou_Objects_Topic class is a concrete subclass of the general
 * Fr_Zhou_Objects_Hypertopic class, tailored for representing the topic
 * Object.
 *
 * URI Example: http://ex.com/viewpoint/1/topic/2/
 *
 * @package Fr_Zhou_Objects
 * @author Chao ZHOU <chao.zhou@utt.fr>
 **/
class Fr_Zhou_Objects_Topic extends Fr_Zhou_Objects_Hypertopic
{
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
   * Topic id
   * @var int $topicId
   */
  public $topicId = null;

  /**
   * Topic name
   * @var string $topicName
   */
  public $topicName = null;
  
  /**
   * Related Topics List
   * Format:
   * <pre>
   *      array(array(name => topic name,
   *            uri => http://foo.bar/viewpoint/1/topic/1/,
   *            relationtype => includes/includedIn,
   *            action => insert/delete
   *      ))</pre>
   *
   * @var array
   */
  public $relatedTopics = array();

  /**
   * Eentities which are related to current topic
   * Format:
   * <pre>
   *      array(array(uri => http://foo.bar/entity/item,
   *            serviceurl=> http://foo.bar,
   *            entitypath=> item,
   *            action=> insert/delete,
   *            name => entity name (if available)
   *      ))</pre>
   *
   * @var array
   */
  public $entities = array();
  
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
    $reg = "/viewpoint\/(.+)\/topic/";
    if(preg_match_all( $reg, $this->uri, $result ))
      $this->viewpointId = $result[1][0];
      
    $reg = "/viewpoint\/(.+)\/topic\/(.+)\//";
    if(!preg_match_all( $reg, $this->uri, $result ))
      return false;
    $this->topicId = $result[2][0];
    
    return true;
  }

  /**
   * To XML document
   *
   * @return void
   */
  public function toXml(){
    $xmlstr  = "<?xml version='1.0' encoding='UTF-8'?>";
    $xmlstr .= "<topic></topic>";
    $xml = new SimpleXMLElement($xmlstr);
    if($this->topicId != null)
      $xml->addAttribute("href", $this->_serviceUrl."/viewpoint/"
                  .$this->viewpointId."/topic/"
                  .$this->topicId."/");
                  
    if($this->topicName != ""){
      $this->topicName = $this->escapeXML($this->topicName);  
      $xml->addAttribute("name", $this->topicName);
    }
      
    $viewpointname = $this->escapeXML($this->viewpointName);  
    $node = $xml->addChild("viewpoint",$viewpointname);
    $node->addAttribute("href", $this->_serviceUrl."/viewpoint/"
                  .$this->viewpointId."/");

    foreach($this->relatedTopics as $e){
      $topicname = $this->escapeXML($e["name"]);
      $node = $xml->addChild("relatedTopic",$topicname);
      $node->addAttribute("relationType", $e["relationtype"]);
      
      if(isset($e["uri"]))
        $node->addAttribute("href", $e["uri"]);
      else
        $node->addAttribute("href", $this->_serviceUrl."/viewpoint/"
                    .$this->viewpointId."/topic/"
                    .$e["topicid"]."/");
      if(isset($e["action"]))
        $node->addAttribute("action", $e["action"]);
      
    }
    
    foreach($this->entities as $e){
      $node = $xml->addChild("entity");
      if(isset($e["uri"]))
        $node->addAttribute("href", $e["uri"]);
      else
        $node->addAttribute("href", 
          $e["serviceurl"]."/entity/".$e["entitypath"]);
      if(isset($e["action"]))
       $node->addAttribute("action", $e["action"]);
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
                "Response Body:\n" . $this->_responseBody . "\n"
                . "Error Message:\n".$ex->getMessage());
    }
    $this->topicName = isset($parser["name"]) 
                ? (string) $parser["name"] : "";
    
    if(isset($parser->viewpoint))
      $this->viewpointName = isset($parser->viewpoint["name"]) 
        ? (string) $parser->viewpoint["name"] : (string) $parser->viewpoint;
    
    if(isset($parser->relatedTopic))
      foreach ($parser->relatedTopic as $relatedTopic) 
      {
        $relatedTopicHref =  (string) $relatedTopic["href"];
        $relationtype = isset($relatedTopic["relationType"]) ?
                (string) $relatedTopic["relationType"] : '';
        $name = (isset($relatedTopic["name"]) 
              ? (string) $relatedTopic["name"] 
                : (string) $relatedTopic);
        $action = (isset($relatedTopic["action"]) 
              ? (string) $relatedTopic["action"] : "");
        $reg = "/viewpoint\/(.+)\/topic\/(.+)\//";
        $result = null;
        if(!preg_match_all( $reg, $relatedTopicHref, $result ))
          throw new Fr_Zhou_Exception(
            "Invalid URL format error", 400, "Related topic URL:".$relatedTopicHref);
        
        $viewpointId = $result[1][0];
        if($viewpointId != $this->viewpointId)
          throw new Fr_Zhou_Exception(
            "You cannot add related topic from a different viewpoint", 400, 
              "URL:".$relatedTopicHref);

        $topicId = $result[2][0];
        array_push($this->relatedTopics, array(
                        "uri" => $relatedTopicHref,
                        "topicid" => $topicId,
                        "relationtype"=> $relationtype, 
                        "name" => $name,
                        "action" => $action));
      }
      
      // Get related entities
      if (isset($parser->entity))
        foreach($parser->entity as $entity) 
        {
          $name = isset($e["name"])?(string) $entity["name"]:
                                    (string) $entity;
          $entityHref = (string) $entity["href"];
          $pos = stripos($entityHref,"/entity/");
          if($pos === false)
            throw new Fr_Zhou_Exception(
              "Invalid URL format error", 400, "Entity URL:".$this->uri);

          $serviceUrl = substr($entityHref, 0, $pos);
          $entityPath = substr($entityHref, $pos+8);
          $action = (isset($entity["action"]) 
                ? (string) $entity["action"] : "");
          array_push ( $this->entities, array(
                        "name" => $name,
                        "uri" => $entityHref,
                        "serviceurl" => $serviceUrl,
                        "entitypath" => $entityPath,
                        "action" => $action));
        }
  }
}
?>