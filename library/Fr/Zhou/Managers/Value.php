<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 18:20:15 CET 2009
 * @copyright 2009, Tech-CICO, University of technology of Troyes
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link http://www.hypertopic.org
 * @package Fr_Zhou_Managers
 **/

/**
 * Value Library
 * 
 * This library allows you to:
 * <ul>
 * <li> Get the item list of a specified attribute value (see {@link
 * http://www.hypertopic.org/index.php/Value#Get_an_Attribute_Value}). 
 * </li>
 * </ul>
 *
 * @package Fr_Zhou_Managers
 * @author Chao ZHOU <chao.zhou@utt.fr>
 **/
class Fr_Zhou_Managers_Value
{

  /**
   * Get attribute value
   *
   * @param string $valueUrl value's URL 
   *                    (e.g. http://foo.bar/attribute/name/value/)
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   *
   * @return object see {@link Fr_Zhou_Objects_Value}
   * @exception throw exception if the value doesn't exist.
   */
  public static function getValue($valueUrl, 
                                $username = null, $password = null)
  {
    $object = new Fr_Zhou_Objects_Value($valueUrl);
    $object->parseUri();
    $object->get($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
                      "Failed to fetch attribute value from server",
                      $object->_statusCode,
                      "Attribute value URL:".$valueUrl);
    $object->parseXml();
    return $object;
  }
}
?>