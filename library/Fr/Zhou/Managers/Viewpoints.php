<?php
/**
 * Licensed under <b>GNU Public License</b>
 * 
 * @author Chao ZHOU <chao.zhou@utt.fr>
 * @version v0.3 Wed Feb 18 15:27:26 CET 2009
 * @copyright 2009, Tech-CICO, University of technology of Troyes
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link http://www.hypertopic.org
 * @package Fr_Zhou_Managers
 **/


/**
 * Viewpoints library
 * 
 * Get a list of viewpoints
 *
 * @package Fr_Zhou_Managers
 * @author Chao ZHOU <chao.zhou@utt.fr>
 **/
class Fr_Zhou_Managers_Viewpoints
{

  /**
   * Get all viewpoints form a hypertopic server
   *
   * @param $serverUrl the hypertopic server URL
   * @param string $username the username for hypertopic server's
   *                         authentication
   * @param string $password the password for hypertopic server's
   *                         authentication
   * @return array viewpoint list, see
   *                  {@link Fr_Zhou_Objects_Viewpoints::$viewpoints}
   * @author Chao ZHOU <chao.zhou@utt.fr>
   **/
  public static function getViewpoints($serverUrl, $username = null,
                                                    $password = null){
    $object = new Fr_Zhou_Objects_Viewpoints($serverUrl."/viewpoint/");
    $object->parseUri();
    $object->get($username, $password);
    $object->parseXml();
    return $object->viewpoints;
  }
}
?>