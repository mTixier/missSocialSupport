<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 18:23:07 CET 2009
 * @copyright 2009, Tech-CICO, University of technology of Troyes
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link http://www.hypertopic.org
 * @package Fr_Zhou_Managers
 **/


/**
 * Resource Library
 * 
 * This library allows you to:
 * <ul>
 * <li> Get the list of entities which have this resource (see {@link
 *    http://www.hypertopic.org/index.php/Resource#Get_a_Resource}). </li>
 * </ul>
 *
 * @package Fr_Zhou_Managers
 * @author Chao ZHOU <chao.zhou@utt.fr>
 **/
class Fr_Zhou_Managers_Resource
{

  /**
   * Get the entities of a specified resource
   *
   * @param string $resourceUrl resource's URL
   *        e.g. http://foo.bar/resource/http%3A%2F%2Fexample.com%2Fres.htm/
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   *
   * @return object see {@link Fr_Zhou_Objects_Resource}
   * @exception throw exception if the resource doesn't exist.
   */
  public static function getResource($resourceUrl, 
                                $username = null, $password = null)
  {
    $object = new Fr_Zhou_Objects_Resource($resourceUrl);
    $object->parseUri();
    $object->get($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
                      "Failed to fetch resource from server",
                      $object->_statusCode,
                      "Resource URL:".$resourceUrl);
    $object->parseXml();
    return $object;
  }
}
?>