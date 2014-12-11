<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author  Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 12:47:34 CET 2009
 * @copyright 2009, Tech-CICO, University of technology of Troyes
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link http://www.hypertopic.org
 * @package Fr_Zhou_Managers
 **/
 
/**
 * Topic Library 
 *
 * This library allows you to:
 * <ul>
 * <li>create a new topic </li>
 * <li>Add/remove related topics to/from the topic </li>
 * <li>Add/remove entities to/from the topic </li>
 * </ul>
 *
 * @package Fr_Zhou_Managers
 * @author  Chao ZHOU <chao.zhou@utt.fr>
 **/
class Fr_Zhou_Managers_Topic
{

  /**
   * Get a topic
   *
   * @param string $topicUrl topic's url
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   *
   * @return mixed Returns the {@link Fr_Zhou_Objects_Topic} object
   *                if the topic is available, FALSE otherwise.
   * @exception throw exception when the topic's url is unavailable.
   * @author Chao ZHOU <chao.zhou@utt.fr>
   **/
  public static function getTopic($topicUrl, $username = null, 
                                                $password = null)
  {
    $object = new Fr_Zhou_Objects_Topic($topicUrl);
    $object->parseUri();
    $object->get($username,$password);
    if(!$object->success())
    throw new Fr_Zhou_Exception(
                    "Failed to fetch topic from server",
                    $object->_statusCode,
                    "Topic URL:".$topicUrl);
    $object->parseXml();
    return $object;
  }

  /**
   * Create a new topic just with name
   *
   * @param string $viewpointUrl viewpoint's URL
   * @param string $name the new topic's name
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   *
   * @return string new topic's uri
   * @exception throw exception when the update failed.
   * @author Chao ZHOU <chao.zhou@utt.fr>
   **/
  public static function addTopic($viewpointUrl,$name, 
                                  $username = null, $password = null)
  {
    $url = $viewpointUrl . "topic/";
    $object = new Fr_Zhou_Objects_Topic($url);
    $object->parseUri();
    $object->topicName = $name;
    $object->post($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
                "Failed to create topic",
                $object->_statusCode,
                "Viewpoint URL:".$viewpointUrl."\n"
                ."Topic Object:".json_encode($object));
    return $object->uri;
  }
  
  /**
   * Create a new topic with name and parent topic URL
   *
   * @param string $topicUrl parent topic's URL
   * @param string $name the new topic's name
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   *
   * @return string new topic's uri
   * @exception throw exception when the update failed.
   * @author Chao ZHOU <chao.zhou@utt.fr>
   **/
  public static function addTopicWithParentTopic($topicUrl,$name, 
                                  $username = null, $password = null)
  {
    //$logger = Zend_Registry::get('logger');
    $object = new Fr_Zhou_Objects_Topic($topicUrl);
    $object->parseUri();
    $object->topicId = NULL;
    $object->uri = $object->_serviceUrl . "/viewpoint/" 
                      . $object->viewpointId . "/topic/";
    $object->topicName = $name;
    $object->relatedTopics[] = array("name" => "don't care", 
                                     "uri" => $topicUrl,
                                     "relationtype" => "includedIn");
    //$logger->log($object,Zend_Log::DEBUG);
    $object->post($username,$password);
    if(!$object->success())
    throw new Fr_Zhou_Exception(
              "Failed to create topic",
              $object->_statusCode,
              "Topic URL:".$topicUrl."\n"
              ."Topic Object:".json_encode($object));
    return $object->uri;
  }
  
  /**
   * Delete a topic
   *
   * @param string $topicUrl topic's url to delete
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   * @return void
   * @exception throw exception when the deletion failed.
   * @author Chao ZHOU <chao.zhou@utt.fr>
   **/
  public static function deleteTopic($topicUrl, $username = null, 
                                    $password = null)
  {
    $object = new Fr_Zhou_Objects_Topic($topicUrl);
    $object->delete($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
              "Failed to delete the topic",
              $object->_statusCode,
              "Topic URL:".$topicUrl);
  }

  /**
   * Rename a topic
   *
   * @param string $topicUrl topic's url
   * @param string $name the new topic's name
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   *
   * @return bool Returns TRUE if it is updated successfully, 
   *              FALSE otherwise. 
   * @exception throw exception when the topic's url is unavailable.
   * @author Chao ZHOU <chao.zhou@utt.fr>
   **/
  public static function renameTopic($topicUrl, $name, $username = null, 
                                                $password = null)
  {
    // $logger = Zend_Registry::get('logger');
    $object = new Fr_Zhou_Objects_Topic($topicUrl);
    $object->parseUri();
    $object->get($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
                      "Failed to fetch topic from server",
                      $object->_statusCode,
                      "Topic URL:".$topicUrl);
    $object->parseXml();
    $object->topicName = $name;
    // $logger->log($object,Zend_Log::DEBUG);
    $object->put($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
                "Failed to rename topic",
                $object->_statusCode,
                "Topic URL:".$topicUrl);
    return $object;
  }
  
  /**
   * Add related topics to the topic
   *
   * @param string $topicUrl topic's url
   * @param array $relatedTopics related topics 
   *                {@link Fr_Zhou_Objects_Topic::$relatedTopics}
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   * @return void
   * @exception throw exception when the related topic's url is null or
   *            update failed.
   * @author Chao ZHOU <chao.zhou@utt.fr>
   **/
  public static function addRelatedTopics($topicUrl, $relatedTopics,
                              $username = null, $password = null)
  {
    self::_doRelatedTopics($topicUrl, $relatedTopics, "insert",
                        $username, $password);
  }
  
  /**
   * Delete related topics from the topic
   *
   * @param string $topicUrl topic's url
   * @param array $relatedTopics related topics 
   *                {@link Fr_Zhou_Objects_Topic::$relatedTopics}
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   * @return void
   * @author Chao ZHOU <chao.zhou@utt.fr>
   **/
  public static function removeRelatedTopics($topicUrl, $relatedTopics,
                              $username = null, $password = null)
  {
    self::_doRelatedTopics($topicUrl, $relatedTopics, "delete",
                        $username, $password);
  }

  /**
   * Attach the entities to the topic
   *
   * @param string $topicUrl topic's url
   * @param array $entities related entities 
   *                {@link Fr_Zhou_Objects_Topic::$entities}
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   * @return void
   * @exception throw exception when the related entities' url is null or
   *            update failed.
   * @author Chao ZHOU <chao.zhou@utt.fr>
   **/
  public static function addRelatedEntities($topicUrl, $entities,
                              $username = null, $password = null)
  {
    self::_doEntities($topicUrl, $entities, "insert",
                        $username, $password);
  }
  
  /**
   * Delete related entities from the topic
   *
   * @param string $topicUrl topic's url
   * @param array $entities related entities 
   *                {@link Fr_Zhou_Objects_Topic::$entities}
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   * @return void
   * @author Chao ZHOU <chao.zhou@utt.fr>
   **/
  public static function removeRelatedEntities($topicUrl, $entities,
                              $username = null, $password = null)
  {
    self::_doEntities($topicUrl, $entities, "delete",
                        $username, $password);
  }

  private static function _doRelatedTopics($topicUrl, $relatedTopics, $type,
                      $username = null, $password = null)
  {
    if(!isset($relatedTopics) || !is_array($relatedTopics) 
          || count($relatedTopics) == 0)
        throw new Fr_Zhou_Exception(
                        "No related topics defined",
                        400,
                        "");
      
    $object = new Fr_Zhou_Objects_Topic($topicUrl);
    $object->parseUri();
    foreach ($relatedTopics as $rTopic) {
      if($rTopic["uri"] == "")
        throw new Fr_Zhou_Exception(
                        "Invaild related topic URL",
                        400,
                        "");

      $result = null;
      $reg = "/viewpoint\/(.+)\/topic\/(.+)\//";
      if(!preg_match_all( $reg, $rTopic["uri"], $result ))
        throw new Fr_Zhou_Exception(
                        "Invaild related topic URL",
                        400,
                        "");
      $topicId = $result[2][0];
      
      $object->relatedTopics[] = array("name" => $rTopic["name"],
                                "relationtype" => $rTopic["relationtype"],
                                "topicid" => $topicId,
                                "action" => $type);
    }
    $object->post($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
                      "Failed to update related topics",
                      400,
                      "Topic Object:".json_encode($object));
  }

  private static function _doEntities($topicUrl, $entities, $type,
                                $username = null, $password = null)
  {
    if(!isset($entities) || !is_array($entities) 
          || count($entities) == 0)
        throw new Fr_Zhou_Exception(
                        "No related entities defined",
                        400,
                        "");
      
    $object = new Fr_Zhou_Objects_Topic($topicUrl);
    $object->parseUri();
    foreach ($entities as $entity) {
      if(!isset($entity["serviceurl"]) || $entity["serviceurl"] == "")
        if(!isset($entity["uri"]) || $entity["uri"] == "")
          throw new Exception("Undefined entity service url", 400, "");
        else
        {
          $entityObject = new Fr_Zhou_Objects_Entity($entity["uri"]);
          $entityObject->parseUri();
          $entity["serviceurl"] = $entityObject->_serviceUrl;
          $entity["entitypath"] = $entityObject->entityPath;
        }
      if($entity["serviceurl"] != $object->_serviceUrl)
      {
        $entity["entitypath"] = rawurlencode($entity["serviceurl"]."/entity/".$entity["entitypath"]);
      }
      
      $object->entities[] = array(
                                "serviceurl" => $object->_serviceUrl,
                                "entitypath" => $entity["entitypath"],
                                "action" => $type);
    }
    $object->post($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
                      "Failed to update related entities",
                      400,
                      "Topic Object:".json_encode($object));
  }
}
?>