<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 18:14:17 CET 2009
 * @copyright 2009, Tech-CICO, University of technology of Troyes
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link http://www.hypertopic.org
 * @package Fr_Zhou_Managers
 **/


/**
 * Attributes Library
 * 
 * This library allows you to:
 * <ul>
 * <li> Get the attributes list from a hypertopic server (see {@link
 * http://www.hypertopic.org/index.php/Attribute#Get_a_List_of_Attribute}). 
 * </li>
 * </ul>
 *
 * @package Fr_Zhou_Managers
 * @author Chao ZHOU <chao.zhou@utt.fr>
 **/
class Fr_Zhou_Managers_Attributes
{

  /**
   * Get attributes list
   *
   * @param string $serverUrl hypertopic server URL 
   *                    (e.g. http://foo.bar/attribute/)
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   *
   * @return objects see {@link Fr_Zhou_Objects_Attributes::$attributes}
   */
  public static function getAttributes($serverUrl, 
                                $username = null, $password = null)
  {
    $object = new Fr_Zhou_Objects_Attributes($serverUrl."/attribute/");
    $object->parseUri();
    $object->get($username,$password);
    $object->parseXml();
    return $object;
  }
}
?>