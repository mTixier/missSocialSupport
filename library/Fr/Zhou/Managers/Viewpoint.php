<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 18:26:35 CET 2009
 * @copyright 2009, Tech-CICO, University of technology of Troyes
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link http://www.hypertopic.org
 * @package Fr_Zhou_Managers
 **/
 

/**
 * Viewpoint Library
 * 
 * <ul>
 * <li> Get a viewpoint by viewpoint's URL</li>
 * <li> Create a viewpoint by viewpoint's name</li>
 * <li> Delete a viewpoint by viewpoint's URL</li>
 * <li> Rename a viewpoint by viewpoint's URL</li>
 * </ul>
 *
 * @package Fr_Zhou_Managers
 * @author Chao ZHOU <chao.zhou@utt.fr>
 **/
class Fr_Zhou_Managers_Viewpoint
{
  /**
   * Create a new viewpoint
   *
   * @param string $serviceUrl Hypertopic server URL
   * @param string $name Viewpoint Name
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   *
   * @exception throw exception if create viewpoint failed
   * @return string new viewpoint's URL
   */
  public static function addViewpoint($serverUrl,$name, 
                                        $username = null, $password = null)
  {
    $uri = $serverUrl."/viewpoint/";
    $object = new Fr_Zhou_Objects_Viewpoint($uri);
    //$object->parseUri();
    $object->viewpointName = $name;
    /*if($username != null)
      $object->actors[] = array("actorid" => $username,
                              "uri" => $serviceUrl."/actor/".$username."/",
                              "role" => "creator",
                              "name" => "");*/
    $object->post($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
                "Failed to create viewpoint",
                $object->_statusCode,
                "Viewpoint Object:".json_encode($attributes));
    return $object->uri;
  }

  /**
   * Delete a specified viewpoint
   *
   * @param string $viewpointUrl viewpoint's URL
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   *
   * @return bool TRUE if delete it successfully
   * @exception throw exception if delete viewpoint failed
   */
  public static function deleteViewpoint($viewpointUrl, 
                                        $username = null, $password = null)
  {
    $object = new Fr_Zhou_Objects_Viewpoint($viewpointUrl);
    $object->delete($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
              "Failed to delete viewpoint",
              $object->_statusCode,
              "Viewpoint URL:".$viewpointUrl);
    return true;
  }
  
  /**
   * Rename a specified viewpoint
   *
   * @param string $viewpointUrl viewpoint's URL
   * @param string $name new viewpoint's name
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   *
   * @return bool TRUE if rename it successfully
   * @exception throw exception if rename viewpoint failed
   */
  public static function renameViewpoint($viewpointUrl, $name,  
                                        $username = null, $password = null)
  {
    $object = new Fr_Zhou_Objects_Viewpoint($viewpointUrl);
    $object->parseUri();
    $object->get($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
                      "Failed to fetch viewpoint from server",
                      $object->_statusCode,
                      "Viewpoint URL:".$viewpointUrl);
    $object->parseXml();
    $object->viewpointName = $name;
    $object->put($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
                "Failed to rename viewpoint",
                $object->_statusCode,
                "Viewpoint URL:".$viewpointUrl."\n"
                ."Viewpoint Object:".json_encode($object));
    
    return true;
  }

  
  /**
   * Get a specified viewpoint
   *
   * @param string $viewpointUrl viewpoint's URL
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   *
   * @return object Viewpoint see {@link Fr_Zhou_Objects_Viewpoint}
   * @exception throw exception if the viewpoint unavailable
   */
  public static function getViewpoint($viewpointUrl,  
                   $username = null, $password = null)
  {
    $object = new Fr_Zhou_Objects_Viewpoint($viewpointUrl);
    $object->parseUri();
    $object->get($username,$password);
    if(!$object->success())
      throw new Fr_Zhou_Exception(
                      "Failed to fetch viewpoint from server",
                      $object->_statusCode,
                      "Viewpoint URL:".$viewpointUrl);
    $object->parseXml();
    return $object;
  }
}
?>