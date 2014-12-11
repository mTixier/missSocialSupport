<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 22:28:23 CET 2009
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
 * Entity object class
 * 
 * The Fr_Zhou_Objects_Entity class is a concrete subclass of the general
 * Fr_Zhou_Objects_Hypertopic class, tailored for representing an Entity
 * Object.
 *
 * @package Fr_Zhou_Objects
 * @author Chao ZHOU <chao.zhou@utt.fr>
 **/
class Fr_Zhou_Objects_Entity extends Fr_Zhou_Objects_Hypertopic {
  /**
   * Item path, For example:
   * URI: http://www.example.com/entity/example/A/B/
   * entityPath: example/A/B/
   *
   * @var string
   */
  public $entityPath;

  /**
   * Related topics
   * Format: <pre>
   *    array(array(viewpointid => 1, 
   *                name => topicname, 
   *                topicid => 2,
   *                uri=> http://foo.bar/viewpoint/1/topic/2/))</pre>
   *
   * @var array
   */
  public $topics = array();

  /**
   * Attributes
   * Format: <pre>
   *  array(array(attributename => name, attributevalue => value))</pre>
   *
   * @var array
   */
  public $attributes = array();

  /**
   * Resources
   * Format: <pre>
   *  array(array(resourcename => html, 
   *              resourceurl => http://w3.org/rfcxxx.htm))</pre>
   *
   * @var array
   */
  public $resources = array();

  /**
   * Related Items
   * Format: <pre>
   *  array(array(uri => http://foo.bar/entity/item/name, 
   *              relationtype => partOf))</pre>
   *
   * @var array
   */
  public $relatedEntities = array();
  
  /**
   * parser URI
   *
   * @return bool Returns TRUE if the URL is correct.
   * @exception throw exception if the URL is incorrect.
   */
  public function parseUri() {
    $pos = stripos ( $this->uri, "/entity/" );
    if ($pos === false)
      return false;
    $this->_serviceUrl = substr ( $this->uri, 0, $pos);
    $this->entityPath = substr ( $this->uri, $pos + 8 );
    return true;
  }
  
  /**
   * To XML document
   *
   * @return void
   */
  public function toXml() {
    $xmlstr = "<?xml version='1.0' encoding='UTF-8'?>";
    $xmlstr .= "<entity></entity>";
    $xml = new SimpleXMLElement ( $xmlstr );
    foreach ( $this->attributes as $attribute ) {
      $node = $xml->addChild ( "attribute" );
      $node->addAttribute ( "name", $attribute ["attributename"] );
      $node->addAttribute ( "value", $attribute ["attributevalue"] );
    }
    foreach ( $this->resources as $resource ) {
      $node = $xml->addChild ( "resource" );
      $node->addAttribute ( "name", $resource ["resourcename"] );
      $node->addAttribute ( "href", $resource ["resourceurl"] );
    }
    foreach ( $this->topics as $topic ) {
      if(isset($topic ["topicname"]))
        $topicname = $this->escapeXML ( $topic ["topicname"] );
      else
        $topicname = "";
      $node = $xml->addChild ( "topic", $topicname );
      if(isset($topic ["uri"]))
        $href = $topic["uri"];
      else
        $href = $this->_serviceUrl . "/viewpoint/" . $topic ["viewpointid"] 
                                  . "/topic/" . $topic ["topicid"] . "/";
      $node->addAttribute ( "href", $href );
    }
    foreach ( $this->relatedEntities as $e ) {
      $node = $xml->addChild ( "relatedEntity" );
      if(isset($e["uri"]))
        $href = $e["uri"];
      else
        $href = $this->_serviceUrl . "/entity/" . $this->entityPath . $e["uri"];
      $node->addAttribute ( "href", $href );
      $node->addAttribute ( "relationType", $e["relationtype"]);
    }
    return $xml->asXML ();
  }
  
  /**
   * Parser the server response xml document
   *
   * @return void
   * @exception throw exception if the XML Document is incorrect.
   */
  public function parseXml() {
    try {
      $parser = new SimpleXMLElement ( $this->_responseBody );
    } catch ( Exception $ex ) {
      throw new Fr_Zhou_Exception (
                "Unable to parser the hypertopic server's response",
                400,
                "Response Body:\n" . $this->_responseBody . "\n"
                . "Error Message:\n".$ex->getMessage());
    }

    if (isset ( $parser->topic ))
      foreach ( $parser->topic as $topic ) {
        $href = ( string ) $topic ["href"];
        $name = (isset ( $topic ["name"] ) 
                  ? ( string ) $topic ["name"] : ( string ) $topic);
        $reg = "/viewpoint\/(.+)\/topic\/(.+)\//";
        $result = null;
        if (! preg_match_all ( $reg, $href, $result )) {
          throw new Fr_Zhou_Exception(
            "Invalid URL format error", 400, "Topic URL:".$href);
        }
        $viewpointid = $result [1] [0];
        $topicid = $result [2] [0];
        array_push ( $this->topics, array (
                                           "uri" => $href,
                                           "viewpointid" => $viewpointid,
                                           "topicid" => $topicid, 
                                           "name" => $name ) );
      }
    if (isset ( $parser->attribute ))
      foreach ( $parser->attribute as $attribute ) {
        $attributevalue = (isset ( $attribute ["value"] ) 
              ? ( string ) $attribute ["value"] : ( string ) $attribute);
        $attributename = ( string ) $attribute ["name"];
        array_push ( $this->attributes, 
                      array ("attributename" => $attributename,
                             "attributevalue" => $attributevalue ) );
      }
    if (isset ( $parser->resource ))
      foreach ( $parser->resource as $resource ) {
        $resourcehref = (isset ( $resource ["href"] ) 
                        ? ( string ) $resource ["href"] : '');
        $resourcename = ( string ) $resource ["name"];
        array_push ( $this->resources, 
                            array ("resourcename" => $resourcename, 
                                   "resourceurl" => $resourcehref ) );
      }
    if (isset ( $parser->relatedEntity ))
      foreach ( $parser->relatedEntity as $relatedEntity ) {
        $name = isset($e["name"])?(string) $relatedEntity["name"]:
                                  (string) $relatedEntity;
        $itemhref = (isset ( $relatedEntity ["href"] ) 
                            ? ( string ) $relatedEntity ["href"] : '');
        $relateionType = ( string ) $relatedEntity ["relationType"];
        array_push ( $this->relatedEntities, array(
                                      "name" => $name,
                                      "uri"=> $itemhref,
                                      "relationtype"=>$relateionType));
      }
  }

  /**
   * Get item's name
   * If item has the attribute "name", use it.
   * Otherwise use the path name as item name
   *
   * @return string
   */
  public function getName(){
    foreach($this->attributes as $attribute){
      if($attribute["attributename"] == "name")
        return $attribute["attributevalue"];
    }
    if(substr($this->entityPath, -1) == "/")
          $url = substr($this->entityPath, 0, -1);
      else
          $url = $this->entityPath;
      $entity = split("/", $url);
      $name = array_pop($entity);
      return rawurldecode($name);
  }

  /*
   * Get related topics
   *
   * @return array
   */
  public function getTopics(){
    return $this->topics;
  }
}
?>