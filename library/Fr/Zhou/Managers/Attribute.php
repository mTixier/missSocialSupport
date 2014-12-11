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
 * Attribute Library
 * 
 * This library allows you to:
 * <ul>
 * <li> Get the attribute object from a hypertopic server (see {@link  
 *   http://www.hypertopic.org/index.php/Attribute#Get_an_Attribute}). </li>
 * </ul>
 *
 * @package Fr_Zhou_Managers
 * @author Chao ZHOU <chao.zhou@utt.fr>
 **/
class Fr_Zhou_Managers_Attribute
{

  /**
   * Get values of a specified attribute
   *
   * @param string $attributeUrl attribute's URL
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   *
   * @return object see {@link Fr_Zhou_Objects_Attribute}
   * @exception if the attribute doesn't exist
   */
  public static function getAttribute($attributeUrl, 
                                $username = null, $password = null)
  {
    $object = new Fr_Zhou_Objects_Attribute($attributeUrl);
    $object->parseUri();
    $object->get($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
                "Failed to fetch attribute from server",
                $object->_statusCode,
                "Attribute Object:".json_encode($object));
    $object->parseXml();
    return $object;
  }
}
?>