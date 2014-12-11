<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 13:09:48 CET 2009
 * @copyright 2009, Tech-CICO, University of technology of Troyes
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link http://www.hypertopic.org
 * @package Fr_Zhou_Managers
 **/

/**
 * Actor Library
 * This library allows you to:
 * <ul>
 * <li> Get the viewpoints list of a specified actor. </li>
 * <li> Create a new actor. </li>
 * <li> Get a existed actor from hypertopic server. </li>
 * <li> Delete an actor. </li>
 * <li> Update an actor's full name. </li>
 * </ul>
 *
 * @package Fr_Zhou_Managers
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @todo delete actor, update actor's name
 **/
class Fr_Zhou_Managers_Actor
{
  /**
   * Create a new actor
   * 
   * If the actor's full name isn't provided, it will take
   * actor id as the full name.
   * 
   * @param string $serverUrl the hypertopic server URL
   * @param string $actorId the new actor's id
   * @param string $actorFullName the new actor's full name
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   * @return bool TRUE if the actor created successfully.
   * @exception create actor failed
   * @author Chao ZHOU <chao.zhou@utt.fr>
   **/
  public static function addActor($serverUrl, $actorId, 
                    $actorFullName = null, $username = null,
                    $password = null){
    $actorUrl = $serverUrl."/actor/".$actorId."/";
    $object = new Fr_Zhou_Objects_Actor($actorUrl);
    if($actorFullName == null)
      $actorFullName = $actorId;
    $object->actorName = $actorFullName;
    $object->put($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception("Failed to create actor",
                                  $object->_statusCode,
                                  "Actor Object:".json_encode($object));
    return true;
  }

  /**
   * Get a actor
   * 
   * Fetch an actor from a hypertopic server by the actor URL
   * 
   * @param string $actorUrl the hypertopic server URL
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   * @return mixed Returns the {@link Fr_Zhou_Objects_Actor} object
   *               if the actor is available, FALSE otherwise.
   * @author Chao ZHOU <chao.zhou@utt.fr>
   *
   **/
  public static function getActor($actorUrl, $username = null,
                    $password = null)
  {
    $object = new Fr_Zhou_Objects_Actor($actorUrl);
    $object->parseUri();
    $object->get($username,$password);
    if(!$object->success())
      return false;
    $object->parseXml();
    return $object;
  }
  
  /**
   * Get an actor's viewpoints and roles
   *
   * @param string $actorUrl Actor's URL
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   * @return mixed Returns the viewpoints array (see: 
   *                {@link Fr_Zhou_Objects_Actor::$viewpoints}) 
   *                if the actor is available,
   *                FALSE otherwise.
   *               
   **/
  public static function getViewpoints($actorUrl, $username = null,
                                            $password = null){
    $object = self::getActor($actorUrl,$username,$password);
    if(!$object) 
      return false;
    return $object->viewpoints;
  }
}
?>