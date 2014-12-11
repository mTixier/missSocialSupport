<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 16:58:01 CET 2009
 * @copyright 2009, Tech-CICO, University of technology of Troyes
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link http://www.hypertopic.org
 * @package Fr_Zhou_Managers
 **/

/**
 * Entity Library
 * 
 * <ul>
 * <li>Get an entity.</li>
 * <li>Create an Entity with attributes/resources/topics.</li>
 * <li>Add/remove the attributes to/from an entity.</li>
 * <li>Add/remove the resources to/from an entity.</li>
 * <li>Add/remove the topics to/from an entity.</li>
 * <li>Delete an entity.</li>
 * </ul>
 *
 * @package Fr_Zhou_Managers
 * @author Chao ZHOU <chao.zhou@utt.fr>
 **/
class Fr_Zhou_Managers_Entity
{
  
  /**
   * Get an entity
   *
   * @param string $entityUrl entity's URL
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   * @return mixed Returns the {@link Fr_Zhou_Objects_Entity} 
   *                object, if the entity is available,
   *                FALSE otherwise.
   **/
  public static function getEntity($entityUrl, $username = null,
                                            $password = null){
    $object = new Fr_Zhou_Objects_Entity($entityUrl);
    $object->parseUri();
    $object->get($username,$password);
    if(!$object->success())
      return false;
    $object->parseXml();
    return $object;
  }
  
  /**
   * Create a new entity
   * 
   * Create a new entity with topics, attributes, resources
   * 
   * @param string $serverUrl the hypertopic server Url
   * @param string $entityPath the entity path 
   * @param array $topics topics related to entity 
   *                        {@link Fr_Zhou_Objects_Entity::$topics}
   * @param array $attributes attributes related to entity 
   *                        {@link Fr_Zhou_Objects_Entity::$attributes}
   * @param array $resources resources related to entity 
   *                        {@link Fr_Zhou_Objects_Entity::$resources}
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   * @return string entity URL
   * @exception throw exception if entity path is undefined, topics/resources
   * /attributes are empty, or update entity failed.
   * @author Chao ZHOU <chao.zhou@utt.fr>
   **/
  public static function addEntity($serverUrl, $entityPath, $topics = null,
                    $attributes = null, $resources = null, $username = null,
                    $password = null)
  {
    if(!isset($entityPath) || $entityPath == "")
      throw new Fr_Zhou_Exception(
                      "Invalid entity path",
                      400,
                      "");
    
    if(($topics == null || count($topics) ==0) 
        && ($attributes == null || count($attributes) ==0) 
        && ($resources == null || count($resources) ==0))
      throw new Fr_Zhou_Exception(
                      "You cannot create an empty entity",
                      400,
                      "");
      
    if(substr($entityPath,0,1) == "/")
      $entityPath = substr($entityPath, 1);
    
    $entityUrl = $serverUrl."/entity/".$entityPath;
    $object = new Fr_Zhou_Objects_Entity($entityUrl);
    
    if($topics != null)
      $object->topics = $topics;
    if($resources != null)
      $object->resources = $resources;
    if($attributes != null)
      $object->attributes = $attributes;
    $object->parseUri();
    
    $object->put($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
                "Failed to create entity",
                $object->_statusCode,
                "Entity Object:".json_encode($object));
    return $object->uri;
  }
  
  /**
   * Add topics to an entity.
   * 
   * Topics array format: <pre>
   *    array(http://foo.bar/viewpoint/1/topic/2/,
   *            http://foo.bar/viewpoint/2/topic/3/
   *    )</pre>
   * 
   * @param string $entityUrl the entity URL 
   * @param array $topics topics' URL to add
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   * @return bool true if success.
   * @exception throw exception if the entity URL is empty, entity is 
   * unavailable, or update entity failed.
   * @author Chao ZHOU <chao.zhou@utt.fr>
   **/
  public static function addTopics($entityUrl, $topics = null,
                    $username = null, $password = null){
    if(!isset($entityUrl) || $entityUrl == "")
      throw new Fr_Zhou_Exception(
                      "Invalid entity URL format",
                      400,
                      "");
    
    if($topics == null || count($topics) == 0)
      return true;
    // Attention les param de connexion ne sont pas fournis dans les appels en interne !!!! -- Cependant il est d'usage de libérer les droits d'accès en lecture
    $object = self::getEntity($entityUrl, $username, $password);
    if(!$object)
      throw new Fr_Zhou_Exception(
                "Failed to fetch entity from server.",
                404,
                "Entity Object:".json_encode($object));
    foreach($topics as $url){
      $topic = new Fr_Zhou_Objects_Topic($url);
      $topic->parseUri();
      if($topic->_serviceUrl != $object->_serviceUrl)
        continue;
      foreach($object->topics as $t)
        if($t["viewpointid"] == $topic->viewpointId 
            && $t["topicid"] == $topic->topicId)
          continue 2;
      
      $object->topics[] = array("viewpointid" => $topic->viewpointId,
                                "topicid" => $topic->topicId,
                                "topicname" => "");
    }
    $object->put($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
                "Failed to add topics to the entity",
                $object->_statusCode,
                "Entity URL:".$entityUrl."\n"
                ."Topics:".json_encode($topics));
    return true;
  }
  
  /**
   * Remove topics from an entity.
   * 
   * Topics array format: <pre>
   *    array(http://foo.bar/viewpoint/1/topic/2/,
   *            http://foo.bar/viewpoint/2/topic/3/
   *    )</pre>
   * 
   * @param string $entityUrl the entity URL 
   * @param array $topics topics to remove
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   * @return bool TRUE if success.
   * @exception throw exception if the entity URL is empty, entity is 
   * unavailable, or update entity failed.
   * @author Chao ZHOU <chao.zhou@utt.fr>
   **/
  public static function removeTopics($entityUrl, $topics = null,
                    $username = null, $password = null){
    if(!isset($entityUrl) || $entityUrl == "")
      throw new Fr_Zhou_Exception(
                      "Invalid entity URL format",
                      400,
                      "");
    
    if($topics == null || count($topics) == 0)
      return true;
    // Attention les param de connexion ne sont pas fournis dans les appels en interne !!!! -- Cependant il est d'usage de libérer les droits d'accès en lecture
    $object = self::getEntity($entityUrl, $username, $password);
    if(!$object)
      throw new Fr_Zhou_Exception(
                      "Failed to fetch entity from server",
                      404,
                      "Entity URL:".$entityUrl);
    foreach($topics as $url){
      $topic = new Fr_Zhou_Objects_Topic($url);
      $topic->parseUri();
      if($topic->_serviceUrl != $object->_serviceUrl)
        continue;
      for($i = 0; $i < count($object->topics); $i++)
        if($object->topics[$i]["viewpointid"] == $topic->viewpointId 
            && $object->topics[$i]["topicid"] == $topic->topicId)
        {
          unset($object->topics[$i]);
          $i--;
          if($i < 0) $i = 0;
          $object->topics = array_values($object->topics);
        }
    }
    $object->put($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
                "Failed to remove topics from the entity",
                $object->_statusCode,
                "Entity URL:".$entityUrl."\n"
                ."Topics:".json_encode($topics));
  }
  

  /**
   * Add attributes to an entity.
   * 
   * @param string $entityUrl the entity URL 
   * @param array $attributes attributes to add, 
   *                  see: {@link Fr_Zhou_Objects_Entity::$attributes}
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   * @return bool true if success.
   * @exception throw exception if the entity URL is empty, the entity
   * is unavailable, or unable to update the entity.
   * @author Chao ZHOU <chao.zhou@utt.fr>
   **/
  public static function addAttributes($entityUrl, $attributes = null,
                    $username = null, $password = null){
    // $logger = Zend_Registry::get('logger');
    if(!isset($entityUrl) || $entityUrl == "")
      throw new Fr_Zhou_Exception(
                      "Invalid entity URL format",
                      400,
                      "");
    
    if($attributes == null || count($attributes) == 0)
      return true;
    
    $object = self::getEntity($entityUrl);
    // $logger->log($object->_statusCode,Zend_Log::DEBUG);
    if(!$object)
      throw new Fr_Zhou_Exception(
                      "Failed to fetch entity from server",
                      404,
                      "Entity URL:".$entityUrl);
    
    foreach($attributes as $attribute){
      foreach($object->attributes as $t){
        if($t["attributename"] == $attribute["attributename"] 
            && $t["attributevalue"] == $attribute["attributevalue"])
        {
          //$logger->log($attribute, Zend_Log::INFO);
          continue 2;
        }
      }  
      $object->attributes[] = $attribute;
    }
    $object->put($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
                "Failed to add attributes to the entity",
                $object->_statusCode,
                "Entity URL:".$entityUrl."\n"
                ."Attributes:".json_encode($attributes));
    return true;
  }
  
  /**
   * Remove attributes from an entity.
   * 
   * @param string $entityUrl the entity URL 
   * @param array $attributes attributes to remove, 
   *                  see: {@link Fr_Zhou_Objects_Entity::$attributes}
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   * @return bool TRUE if success.
   * @exception throw exception if the entity URL is empty, entity is 
   * unavailable, or update entity failed.
   * @author Chao ZHOU <chao.zhou@utt.fr>
   **/
  public static function removeAttributes($entityUrl, $attributes = null,
                    $username = null, $password = null){
    if(!isset($entityUrl) || $entityUrl == "")
      throw new Fr_Zhou_Exception(
                      "Invalid entity URL format",
                      400,
                      "");
    
    if($attributes == null || count($attributes) == 0)
      return true;
    
    $object = self::getEntity($entityUrl);
    if(!$object)
      throw new Fr_Zhou_Exception(
                      "Failed to fetch entity from server",
                      404,
                      "Entity URL:".$entityUrl);
    foreach($attributes as $attribute){
      for($i = 0 ; $i < count($object->attributes); $i++)
        if($object->attributes[$i]["attributename"] 
              == $attribute["attributename"] 
            &&   $object->attributes[$i]["attributevalue"] 
                    == $attribute["attributevalue"])
        {
          unset($object->attributes[$i]);
          $i--;
          if($i < 0) $i = 0;
          $object->attributes = array_values($object->attributes);
        }
    }

    $object->put($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
                "Failed to remove attributes from the entity",
                $object->_statusCode,
                "Entity URL:".$entityUrl."\n"
                ."Attributes:".json_encode($attributes));
      
    return true;
  }
  
  /**
   * Add resources to an entity. Notice: resource name should be unique.
   * 
   * @param string $entityUrl the entity URL 
   * @param array $resources resources to add, 
   *                  see:{@link Fr_Zhou_Objects_Entity::$resources}
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   * @return bool true if success.
   * @exception throw exception if the entity URL is empty, entity is
   * unavailable, or update entity failed.
   * @author Chao ZHOU <chao.zhou@utt.fr>
   **/
  public static function addResources($entityUrl, $resources = null,
                    $username = null, $password = null){
    if(!isset($entityUrl) || $entityUrl == "")
      throw new Fr_Zhou_Exception(
                "Invalid entity URL format",
                400,
                "");
    
    if($resources == null || count($resources) == 0)
      return true;
    
    $object = self::getEntity($entityUrl);
    if(!$object)
      throw new Fr_Zhou_Exception(
                "Failed to fetch entity from server",
                404,
                "Entity URL:".$entityUrl);
    foreach($resources as $resource){
      foreach($object->resources as $t)
        if($t["resourcename"] == $resource["resourcename"])
          continue 2;
      
      $object->resources[] = $resource;
    }
    $object->put($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
                "Failed to add resources to entity",
                $object->_statusCode,
                "Entity URL:".$entityUrl."\n"
               ."Resources Object".json_encode($resources));
    return true;
  }
  
  /**
   * Remove resources from an entity.
   * 
   * @param string $entityUrl the entity URL 
   * @param array $resources resources to remove, 
   *                  see:{@link Fr_Zhou_Objects_Entity::$resources}
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   * @return bool TRUE if success.
   * @exception throw exception if the entity URL is empty, entity is 
   * unavailable, or update entity failed.
   * @author Chao ZHOU <chao.zhou@utt.fr>
   **/
  public static function removeResources($entityUrl, $resources = null,
                    $username = null, $password = null){
    if(!isset($entityUrl) || $entityUrl == "")
      throw new Fr_Zhou_Exception(
                "Invalid entity URL format",
                400,
                "");
    
    if($resources == null || count($resources) == 0)
      return true;
    
    $object = self::getEntity($entityUrl);
    if(!$object)
      throw new Fr_Zhou_Exception(
                "Failed to fetch entity from server",
                404,
                "Entity URL:".$entityUrl);
    foreach($resources as $resource){
      for($i = 0 ; $i < count($object->resources); $i++)
        if($object->resources[$i]["resourcename"] 
              == $resource["resourcename"] 
            &&   $object->resources[$i]["resourceurl"] 
                    == $resource["resourceurl"])
        {
          unset($object->resources[$i]);
          $i--;
          if($i < 0) $i = 0;
          $object->resources = array_values($object->resources);
        }
    }

    $object->put($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
                "Failed to remove resources from the entity",
                $object->_statusCode,
                "Entity URL:".$entityUrl."\n"
                ."Resources:".json_encode($resources));
    return true;
  }

  /**
   * Delete an entity
   *
   * @param string $entityUrl entity's URL
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   * @return mixed Returns TRUE, if the entity is available,
   *                FALSE otherwise.
   **/
  public static function deleteEntity($entityUrl, $username = null,
                                            $password = null){
    $object = new Fr_Zhou_Objects_Entity($entityUrl);
    $object->delete($username,$password);
    return $object->success();
  }
}
?>