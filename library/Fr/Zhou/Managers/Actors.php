<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 15:53:21 CET 2009
 * @copyright 2009, Tech-CICO, University of technology of Troyes
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link http://www.hypertopic.org
 * @package Fr_Zhou_Managers
 **/

/**
 * Actors library
 * 
 * This library allows you to:
 * <ul>
 * <li> Get the actor list of a hypertopic server  (see {@link
 *    http://www.hypertopic.org/index.php/Actor#Get_List_of_Actor}). </li>
 * </ul>
 *
 * @package Fr_Zhou_Managers
 * @author Chao ZHOU <chao.zhou@utt.fr>
 **/
class Fr_Zhou_Managers_Actors
{
  /**
   * Get all actors form a hypertopic server
   *
   * @param $serverUrl the hypertopic server URL
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   * @return array() actors, see {@link Fr_Zhou_Objects_Actors::$actors}
   * @author Chao ZHOU <chao.zhou@utt.fr>
   **/
  public static function getActors($serverUrl, $username = null,
                                               $password = null){
    $object = new Fr_Zhou_Objects_Actors($serverUrl."/actor/");
    $object->parseUri();
    $object->get($username, $password);
    $object->parseXml();
    return $object->actors;
  }
}
?>